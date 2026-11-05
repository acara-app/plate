<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Pest\Browser\Api\AwaitableWebpage;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

function waitForPublicSnapToTrack(AwaitableWebpage $page, string $condition): void
{
    $timeoutMessage = json_encode('Timed out waiting for '.$condition, JSON_THROW_ON_ERROR);

    $page->script(<<<JS
        new Promise((resolve, reject) => {
            const startedAt = Date.now();
            const check = () => {
                if ({$condition}) {
                    resolve(true);
                } else if (Date.now() - startedAt > 15000) {
                    reject(new Error({$timeoutMessage}));
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        })
        JS);
}

function openPublicSnapToTrackForUpload(): AwaitableWebpage
{
    Turnstile::fake();

    $page = visit(route('snap-to-track', absolute: false))->assertSee('Choose photo');

    $page->script(<<<'JS'
        () => {
            window.snapToTrackUploads = [];
            const send = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (body) {
                if (body instanceof FormData && body.has('files[]')) {
                    const file = body.get('files[]');
                    createImageBitmap(file).then((bitmap) => {
                        window.snapToTrackUploads.push({ name: file.name, type: file.type, width: bitmap.width, height: bitmap.height });
                    });
                    return;
                }
                return send.call(this, body);
            };
        }
        JS);

    $page->script(sprintf(
        "Livewire.getByName('pages::snap-to-track')[0].\$set('turnstileToken', '%s').then(() => true)",
        ClientInterface::RESPONSE_DUMMY_TOKEN,
    ));
    waitForPublicSnapToTrack($page, "! document.querySelector('#photo-upload').disabled");

    return $page;
}

it('converts an AVIF photo to JPEG before uploading it for analysis', function (): void {
    $page = openPublicSnapToTrackForUpload();

    $page->attach('#photo-upload', base_path('tests/Fixtures/meal.avif'));
    waitForPublicSnapToTrack($page, 'window.snapToTrackUploads.length === 1');

    $page->assertScript('window.snapToTrackUploads[0].name', 'meal.jpg')
        ->assertScript('window.snapToTrackUploads[0].type', 'image/jpeg')
        ->assertScript('window.snapToTrackUploads[0].width', 320)
        ->assertScript('window.snapToTrackUploads[0].height', 240);
});

it('uploads a PNG photo without converting it', function (): void {
    $photo = UploadedFile::fake()->image('meal.png', 320, 240);
    $page = openPublicSnapToTrackForUpload();

    $page->attach('#photo-upload', $photo->getPathname());
    waitForPublicSnapToTrack($page, 'window.snapToTrackUploads.length === 1');

    $page->assertScript('window.snapToTrackUploads[0].type', 'image/png')
        ->assertScript('window.snapToTrackUploads[0].width', 320);
});
