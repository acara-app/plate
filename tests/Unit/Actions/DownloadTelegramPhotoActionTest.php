<?php

declare(strict_types=1);

use App\Actions\DownloadTelegramPhotoAction;
use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\DTO\Photo;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Telegraph;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Files\Base64Image;

covers(DownloadTelegramPhotoAction::class);

it('downloads a photo and returns a Base64Image', function (): void {
    $imageContent = 'fake-jpeg-content';

    Http::fake([
        '*/file/bot*' => Http::response($imageContent, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $telegraphResponse = Mockery::mock(TelegraphResponse::class);
    $telegraphResponse->shouldReceive('telegraphError')->andReturn(false);
    $telegraphResponse->shouldReceive('json')->with('result.file_path')->andReturn('photos/file_123.jpg');

    $telegraph = Mockery::mock(Telegraph::class);
    $telegraph->shouldReceive('send')->once()->andReturn($telegraphResponse);
    $telegraph->shouldReceive('getFilesUrl')->once()->andReturn('https://api.telegram.org/file/botTEST_TOKEN');

    $bot = Mockery::mock(TelegraphBot::class);
    $bot->shouldReceive('getFileInfo')->with('test_file_id')->once()->andReturn($telegraph);

    $photo = Photo::fromArray([
        'file_id' => 'test_file_id',
        'width' => 800,
        'height' => 600,
    ]);

    $result = (new DownloadTelegramPhotoAction)->handle($bot, $photo);

    expect($result)->toBeInstanceOf(Base64Image::class)
        ->and($result->mime)->toBe('image/jpeg')
        ->and(base64_decode($result->base64))->toBe($imageContent);
});

it('throws exception when file info retrieval fails', function (): void {
    $telegraphResponse = Mockery::mock(TelegraphResponse::class);
    $telegraphResponse->shouldReceive('telegraphError')->andReturn(true);

    $telegraph = Mockery::mock(Telegraph::class);
    $telegraph->shouldReceive('send')->once()->andReturn($telegraphResponse);

    $bot = Mockery::mock(TelegraphBot::class);
    $bot->shouldReceive('getFileInfo')->with('bad_file_id')->once()->andReturn($telegraph);

    $photo = Photo::fromArray([
        'file_id' => 'bad_file_id',
        'width' => 100,
        'height' => 100,
    ]);

    (new DownloadTelegramPhotoAction)->handle($bot, $photo);
})->throws(RuntimeException::class, 'Failed to retrieve file info for Telegram photo: bad_file_id');

it('throws exception when file download fails', function (): void {
    Http::fake([
        '*/file/bot*' => Http::response('', 500),
    ]);

    $telegraphResponse = Mockery::mock(TelegraphResponse::class);
    $telegraphResponse->shouldReceive('telegraphError')->andReturn(false);
    $telegraphResponse->shouldReceive('json')->with('result.file_path')->andReturn('photos/file_456.jpg');

    $telegraph = Mockery::mock(Telegraph::class);
    $telegraph->shouldReceive('send')->once()->andReturn($telegraphResponse);
    $telegraph->shouldReceive('getFilesUrl')->once()->andReturn('https://api.telegram.org/file/botTEST_TOKEN');

    $bot = Mockery::mock(TelegraphBot::class);
    $bot->shouldReceive('getFileInfo')->with('fail_file_id')->once()->andReturn($telegraph);

    $photo = Photo::fromArray([
        'file_id' => 'fail_file_id',
        'width' => 100,
        'height' => 100,
    ]);

    (new DownloadTelegramPhotoAction)->handle($bot, $photo);
})->throws(RuntimeException::class, 'Failed to download Telegram photo: fail_file_id');

it('resolves mime type from file extension when content-type is missing', function (): void {
    $imageContent = 'fake-png-content';

    Http::fake([
        '*/file/bot*' => Http::response($imageContent, 200, ['Content-Type' => 'application/octet-stream']),
    ]);

    $telegraphResponse = Mockery::mock(TelegraphResponse::class);
    $telegraphResponse->shouldReceive('telegraphError')->andReturn(false);
    $telegraphResponse->shouldReceive('json')->with('result.file_path')->andReturn('photos/file_789.png');

    $telegraph = Mockery::mock(Telegraph::class);
    $telegraph->shouldReceive('send')->once()->andReturn($telegraphResponse);
    $telegraph->shouldReceive('getFilesUrl')->once()->andReturn('https://api.telegram.org/file/botTEST_TOKEN');

    $bot = Mockery::mock(TelegraphBot::class);
    $bot->shouldReceive('getFileInfo')->with('png_file_id')->once()->andReturn($telegraph);

    $photo = Photo::fromArray([
        'file_id' => 'png_file_id',
        'width' => 400,
        'height' => 300,
    ]);

    $result = (new DownloadTelegramPhotoAction)->handle($bot, $photo);

    expect($result->mime)->toBe('image/png');
});

it('resolves mime type for $extension extension via file path fallback', function (string $extension, string $expectedMime): void {
    Http::fake([
        '*/file/bot*' => Http::response('fake-content', 200, ['Content-Type' => 'application/octet-stream']),
    ]);

    $fileId = $extension.'_file_id';

    $telegraphResponse = Mockery::mock(TelegraphResponse::class);
    $telegraphResponse->shouldReceive('telegraphError')->andReturn(false);
    $telegraphResponse->shouldReceive('json')->with('result.file_path')->andReturn('photos/file.'.$extension);

    $telegraph = Mockery::mock(Telegraph::class);
    $telegraph->shouldReceive('send')->once()->andReturn($telegraphResponse);
    $telegraph->shouldReceive('getFilesUrl')->once()->andReturn('https://api.telegram.org/file/botTEST_TOKEN');

    $bot = Mockery::mock(TelegraphBot::class);
    $bot->shouldReceive('getFileInfo')->with($fileId)->once()->andReturn($telegraph);

    $photo = Photo::fromArray(['file_id' => $fileId, 'width' => 100, 'height' => 100]);

    $result = (new DownloadTelegramPhotoAction)->handle($bot, $photo);

    expect($result->mime)->toBe($expectedMime);
})->with([
    'gif' => ['gif', 'image/gif'],
    'webp' => ['webp', 'image/webp'],
    'unknown extension falls back to jpeg' => ['bmp', 'image/jpeg'],
]);
