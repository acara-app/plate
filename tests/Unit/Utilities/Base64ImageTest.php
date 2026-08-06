<?php

declare(strict_types=1);

use App\Rules\ValidBase64Image;
use App\Utilities\Base64Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

covers(Base64Image::class);

function rawJpegBytes(int $width = 32, int $height = 32): string
{
    $file = UploadedFile::fake()->image('meal.jpg', $width, $height);

    return (string) file_get_contents((string) $file->getRealPath());
}

it('decodes a data url payload and reports the detected mime type', function (): void {
    $image = Base64Image::decode('data:image/jpeg;base64,'.base64_encode(rawJpegBytes()));

    expect($image)->not->toBeNull()
        ->and($image->mimeType)->toBe('image/jpeg')
        ->and($image->byteLength())->toBeGreaterThan(0);
});

it('decodes a bare base64 payload without a data url prefix', function (): void {
    $image = Base64Image::decode(base64_encode(rawJpegBytes()));

    expect($image)->not->toBeNull()
        ->and($image->mimeType)->toBe('image/jpeg');
});

it('trusts the decoded bytes over a mismatched data url mime type', function (): void {
    $image = Base64Image::decode('data:image/png;base64,'.base64_encode(rawJpegBytes()));

    expect($image?->mimeType)->toBe('image/jpeg');
});

it('round-trips the payload back to base64', function (): void {
    $bytes = rawJpegBytes();
    $image = Base64Image::decode(base64_encode($bytes));

    expect($image?->base64())->toBe(base64_encode($bytes))
        ->and($image?->byteLength())->toBe(mb_strlen($bytes, '8bit'));
});

it('rejects payloads that are not decodable images', function (string $payload): void {
    expect(Base64Image::decode($payload))->toBeNull();
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'not base64' => ['@@@@@'],
    'base64 of plain text' => [fn (): string => base64_encode('definitely not an image')],
    'data url with no payload' => ['data:image/jpeg;base64,'],
    'data url with no comma' => ['data:image/jpeg;base64'],
]);

it('passes the validation rule for an image within the size cap', function (): void {
    $validator = Validator::make(
        ['photo' => 'data:image/jpeg;base64,'.base64_encode(rawJpegBytes())],
        ['photo' => [new ValidBase64Image(10240)]],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails the validation rule when the decoded image exceeds the size cap', function (): void {
    $validator = Validator::make(
        ['photo' => 'data:image/jpeg;base64,'.base64_encode(rawJpegBytes(600, 600))],
        ['photo' => [new ValidBase64Image(1)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('photo'))->toContain('1 kilobytes');
});

it('fails the validation rule for a non-image payload', function (): void {
    $validator = Validator::make(
        ['photo' => base64_encode('not an image')],
        ['photo' => [new ValidBase64Image(10240)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('photo'))->toContain('must be an image');
});
