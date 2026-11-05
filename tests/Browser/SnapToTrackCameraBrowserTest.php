<?php

declare(strict_types=1);

use App\Actions\Billing\ResolveSnapBurstCap;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Pest\Browser\Api\AwaitableWebpage;

function fakeMealCamera(AwaitableWebpage $page, int $width = 640, int $height = 480): void
{
    $page->script("window.mealCameraDimensions = { width: {$width}, height: {$height} }");
    $page->script(<<<'JS'
        window.mealCameraRequests = [];
        window.mealCameraStreams = [];
        window.mealCameraCanvases = [];
        navigator.mediaDevices.getUserMedia = async (constraints) => {
            window.mealCameraRequests.push(constraints);
            if (window.mealCameraFailure) {
                throw new DOMException('Camera unavailable', window.mealCameraFailure);
            }
            const canvas = document.createElement('canvas');
            canvas.width = window.mealCameraDimensions.width;
            canvas.height = window.mealCameraDimensions.height;
            const context = canvas.getContext('2d');
            context.fillStyle = '#f0b67f';
            context.fillRect(0, 0, canvas.width, canvas.height);
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            context.fillStyle = '#ffffff';
            context.beginPath();
            context.arc(centerX, centerY, 48, 0, Math.PI * 2);
            context.fill();
            for (const [color, x, y] of [
                ['#0000ff', centerX - 140, centerY - 16],
                ['#ff0000', centerX + 108, centerY - 16],
                ['#00ff00', centerX - 16, centerY - 140],
                ['#ff00ff', centerX - 16, centerY + 108],
            ]) {
                context.fillStyle = color;
                context.fillRect(x, y, 32, 32);
            }
            const stream = canvas.captureStream(10);
            window.mealCameraCanvases.push(canvas);
            window.mealCameraStreams.push(stream);
            return stream;
        };
        void 0;
        JS);
}

it('captures the displayed standard frame without stretching the meal photo', function (int $sourceWidth, int $sourceHeight, int $viewportWidth, int $viewportHeight, int $photoWidth, int $photoHeight): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))
        ->resize($viewportWidth, $viewportHeight)
        ->assertSee('Take photo');
    fakeMealCamera($page, $sourceWidth, $sourceHeight);

    $page->press('Take photo')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript(<<<JS
            () => {
                const frame = document.querySelector('[data-camera-frame]').getBoundingClientRect();
                const video = document.querySelector('video').getBoundingClientRect();
                return Math.abs(frame.width - frame.height * {$photoWidth} / {$photoHeight}) < 1
                    && Math.abs(video.width - frame.width) < 1
                    && Math.abs(video.height - frame.height) < 1;
            }
            JS)
        ->assertScript(<<<'JS'
            () => {
                const frame = document.querySelector('[data-camera-frame]').getBoundingClientRect();
                const header = document.querySelector('[data-camera-header]').getBoundingClientRect();
                const controls = document.querySelector('[data-camera-controls]').getBoundingClientRect();
                const dialog = document.querySelector('[role="dialog"]');
                const dialogBounds = dialog.getBoundingClientRect();
                const capture = document.querySelector('button[aria-label="Capture photo"]').getBoundingClientRect();
                const controlsClear = frame.right <= controls.left + 1
                    || frame.bottom <= controls.top + 1;
                return controlsClear && header.bottom <= frame.top + 1
                    && frame.width > 0 && frame.height > 0
                    && capture.left >= 0 && capture.right <= innerWidth + 1
                    && capture.top >= 0 && capture.bottom <= innerHeight + 1
                    && dialogBounds.left >= -1 && dialogBounds.right <= innerWidth + 1
                    && dialogBounds.top >= -1 && dialogBounds.bottom <= innerHeight + 1
                    && dialog.scrollWidth <= dialog.clientWidth + 1
                    && dialog.scrollHeight <= dialog.clientHeight + 1
                    && document.documentElement.scrollWidth <= innerWidth + 1;
            }
            JS)
        ->press('button[aria-label="Capture photo"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', $photoWidth)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', $photoHeight)
        ->assertScript(<<<'JS'
            () => {
                const image = document.querySelector('img[alt="Meal photo preview"]');
                const bounds = image.getBoundingClientRect();
                return Math.abs(bounds.width - bounds.height * image.naturalWidth / image.naturalHeight) < 1;
            }
            JS);
    $pixels = $page->script(<<<'JS'
        () => {
            const image = document.querySelector('img[alt="Meal photo preview"]');
            const canvas = document.createElement('canvas');
            canvas.width = image.naturalWidth;
            canvas.height = image.naturalHeight;
            const context = canvas.getContext('2d');
            context.drawImage(image, 0, 0);
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const colorAt = (x, y) => Array.from(context.getImageData(x, y, 1, 1).data).slice(0, 3);
            const isWhite = (x, y) => colorAt(x, y).every((channel) => channel > 235);
            let circleWidth = 0;
            let circleHeight = 0;
            for (let offset = -60; offset <= 60; offset++) {
                circleWidth += Number(isWhite(centerX + offset, centerY));
                circleHeight += Number(isWhite(centerX, centerY + offset));
            }
            return {
                left: colorAt(centerX - 124, centerY),
                right: colorAt(centerX + 124, centerY),
                top: colorAt(centerX, centerY - 124),
                bottom: colorAt(centerX, centerY + 124),
                circleWidth,
                circleHeight,
            };
        }
        JS);

    expect($pixels['left'])->toEqualWithDelta([0, 0, 255], 25);
    expect($pixels['right'])->toEqualWithDelta([255, 0, 0], 25);
    expect($pixels['top'])->toEqualWithDelta([0, 255, 0], 25);
    expect($pixels['bottom'])->toEqualWithDelta([255, 0, 255], 25);
    expect($pixels['circleWidth'])->toBeBetween(94, 98);
    expect($pixels['circleHeight'])->toBeBetween(94, 98);
    $page->assertNoJavaScriptErrors();
})->with([
    'landscape camera on a portrait phone' => [640, 480, 390, 844, 360, 480],
    'odd camera dimensions on a portrait phone' => [641, 481, 390, 844, 360, 480],
    'landscape camera on a small portrait phone' => [640, 480, 320, 568, 360, 480],
    'landscape camera on a square viewport' => [640, 480, 600, 600, 360, 480],
    'portrait camera on a portrait phone' => [480, 640, 390, 844, 480, 640],
    'landscape camera on a landscape phone' => [640, 480, 844, 390, 640, 480],
    'portrait camera on a landscape phone' => [480, 640, 844, 390, 480, 360],
    'landscape camera on a desktop' => [640, 480, 1440, 900, 640, 480],
]);

it('updates the camera frame when the phone rotates and keeps the captured photo unchanged', function (): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))
        ->resize(390, 844)
        ->assertSee('Take photo');
    fakeMealCamera($page);

    $page->press('Take photo')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->resize(844, 390)
        ->assertScript(<<<'JS'
            () => {
                const frame = document.querySelector('[data-camera-frame]').getBoundingClientRect();
                return Math.abs(frame.width - frame.height * 4 / 3) < 1;
            }
            JS)
        ->assertScript('window.mealCameraRequests.length', 1)
        ->press('button[aria-label="Capture photo"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', 640)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', 480)
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            () => {
                const image = document.querySelector('img[alt="Meal photo preview"]');
                const bounds = image.getBoundingClientRect();
                return image.naturalWidth === 640 && image.naturalHeight === 480
                    && Math.abs(bounds.width - bounds.height * 4 / 3) < 1;
            }
            JS)
        ->press('Retake')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript(<<<'JS'
            () => {
                const frame = document.querySelector('[data-camera-frame]').getBoundingClientRect();
                return Math.abs(frame.width - frame.height * 3 / 4) < 1;
            }
            JS)
        ->press('button[aria-label="Capture photo"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', 360)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', 480)
        ->assertNoJavaScriptErrors();
});

it('previews a captured meal and waits for confirmation before submitting it', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit(route('snap-to-track.index', absolute: false))->on()->mobile()
        ->assertSee('Take photo');
    fakeMealCamera($page);
    $page->script(<<<'JS'
        () => {
            window.mealPhotoSubmissions = [];
            const send = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (body) {
                if (body instanceof FormData && body.has('analysis_request_id')) {
                    const photo = body.get('photo');
                    window.mealPhotoSubmissions.push({
                        isFile: photo instanceof File,
                        type: photo?.type,
                        size: photo?.size,
                        requestId: body.get('analysis_request_id'),
                    });
                    return;
                }
                return send.call(this, body);
            };
        }
        JS);
    $requestId = $page->value('input[name="analysis_request_id"]');
    $page->assertDisabled('Analyze photo')
        ->assertScript('window.mealCameraRequests.length', 0)
        ->press('Keep the whole meal in frame.')
        ->assertVisible('[role="dialog"]')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraRequests[0].audio', false)
        ->assertScript('window.mealCameraRequests[0].video.facingMode.ideal', 'environment')
        ->press('button[aria-label="Capture photo"]')
        ->assertMissing('[role="dialog"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.activeElement.textContent.includes("Retake")')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', 360)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', 480)
        ->assertEnabled('Analyze photo')
        ->assertScript('window.mealPhotoSubmissions.length', 0)
        ->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended');

    expect(AnalysisDraft::query()->count())->toBe(0);

    $page->press('Retake')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->press('button[aria-label="Close camera"]')
        ->assertMissing('[role="dialog"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('window.mealCameraStreams[1].getTracks()[0].readyState', 'ended')
        ->press('Analyze photo')
        ->assertDisabled('Analyzing…')
        ->assertDisabled('Retake')
        ->assertScript('window.mealPhotoSubmissions.length', 1)
        ->assertScript('window.mealPhotoSubmissions[0].isFile')
        ->assertScript('window.mealPhotoSubmissions[0].type', 'image/jpeg')
        ->assertScript('window.mealPhotoSubmissions[0].size > 0')
        ->assertScript('window.mealPhotoSubmissions[0].requestId', $requestId)
        ->assertNoJavaScriptErrors();
    $page->script('document.querySelector("button[type=\"submit\"]").click()');
    $page->assertScript('window.mealPhotoSubmissions.length', 1);
});

it('opens the camera from the photo area with the keyboard and restores focus when dismissed', function (string $key): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Take photo');
    fakeMealCamera($page);

    $page->assertVisible('button[aria-label="Take a meal photo"]')
        ->assertScript('window.mealCameraRequests.length', 0)
        ->keys('button[aria-label="Take a meal photo"]', $key)
        ->assertVisible('[role="dialog"]')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraRequests.length', 1)
        ->keys('[role="dialog"]', 'Escape')
        ->assertMissing('[role="dialog"]')
        ->assertScript('document.activeElement.getAttribute("aria-label")', 'Take a meal photo')
        ->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended')
        ->assertDisabled('Analyze photo')
        ->assertNoJavaScriptErrors();
})->with([
    'Enter' => 'Enter',
    'Space' => 'Space',
]);

it('previews a gallery photo and preserves it when replacement is cancelled', function (bool $fromCamera, int $width, int $height): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg', $width, $height);
    $replacement = UploadedFile::fake()->image('replacement.jpg', $height, $width);
    $page = visit(route('snap-to-track.index', absolute: false))
        ->resize(390, 844)
        ->assertSee('Take photo');
    fakeMealCamera($page);
    $page->script(<<<'JS'
        () => {
            window.revokedMealPhotoUrls = [];
            const revoke = URL.revokeObjectURL;
            URL.revokeObjectURL = (url) => {
                window.revokedMealPhotoUrls.push(url);
                revoke.call(URL, url);
            };
        }
        JS);

    if ($fromCamera) {
        $page->press('Take photo')
            ->assertEnabled('button[aria-label="Capture photo"]')
            ->press('button[aria-label="Choose photo"]');
    } else {
        $page->press('Choose photo');
    }

    $page->attach('#snap-photo', $photo->getPathname())
        ->assertMissing('[role="dialog"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', $width)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', $height)
        ->assertScript(<<<'JS'
            () => {
                const image = document.querySelector('img[alt="Meal photo preview"]');
                const bounds = image.getBoundingClientRect();
                const figure = image.closest('figure').getBoundingClientRect();
                return Math.abs(bounds.width - bounds.height * image.naturalWidth / image.naturalHeight) < 1
                    && Math.abs(bounds.left + bounds.width / 2 - figure.left - figure.width / 2) < 1;
            }
            JS)
        ->assertEnabled('Analyze photo')
        ->assertScript('window.mealCameraRequests.length', $fromCamera ? 1 : 0);

    if ($fromCamera) {
        $page->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended');
    }

    expect(AnalysisDraft::query()->count())->toBe(0);
    $preview = $page->script('document.querySelector("img[alt=\"Meal photo preview\"]").src');
    $page->press('Replace photo');
    $page->script('document.querySelector("#snap-photo").dispatchEvent(new Event("cancel", { bubbles: true }))');
    $page->assertAttribute('img[alt="Meal photo preview"]', 'src', $preview)
        ->attach('#snap-photo', $replacement->getPathname())
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', $height)
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight', $width)
        ->assertScript(<<<'JS'
            () => {
                const image = document.querySelector('img[alt="Meal photo preview"]');
                const bounds = image.getBoundingClientRect();
                return Math.abs(bounds.width - bounds.height * image.naturalWidth / image.naturalHeight) < 1;
            }
            JS)
        ->assertScript('window.revokedMealPhotoUrls[0]', $preview)
        ->assertNoJavaScriptErrors();
})->with([
    'landscape photo from the page' => [false, 640, 480],
    'portrait photo from the camera' => [true, 480, 640],
    'square photo from the page' => [false, 640, 640],
    'panoramic photo from the page' => [false, 1200, 400],
]);

it('keeps the photo picker available when camera access fails', function (string $failure, string $message, int $viewportWidth, int $viewportHeight): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg');
    $page = visit(route('snap-to-track.index', absolute: false))
        ->resize($viewportWidth, $viewportHeight)
        ->assertSee('Take photo');
    fakeMealCamera($page);

    if ($failure === 'unsupported') {
        $page->script('navigator.mediaDevices.getUserMedia = undefined');
    } else {
        $page->script('window.mealCameraFailure = '.json_encode($failure, JSON_THROW_ON_ERROR));
    }

    $page->press('Take photo')
        ->assertSee($message)
        ->assertDisabled('button[aria-label="Capture photo"]')
        ->assertVisible('button:has-text("Try camera again")')
        ->assertScript(<<<'JS'
            () => {
                const retry = Array.from(document.querySelectorAll('button'))
                    .find((button) => button.textContent.includes('Try camera again'))
                    .getBoundingClientRect();
                return retry.left >= 0 && retry.right <= innerWidth + 1
                    && retry.top >= 0 && retry.bottom <= innerHeight + 1;
            }
            JS)
        ->press('Try camera again')
        ->assertSee($message)
        ->press('button[aria-label="Choose photo"]')
        ->attach('#snap-photo', $photo->getPathname())
        ->assertMissing('[role="dialog"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertEnabled('Analyze photo')
        ->assertNoJavaScriptErrors();
})->with([
    'permission denied' => ['NotAllowedError', 'Camera access is blocked.'],
    'camera missing' => ['NotFoundError', 'The camera is unavailable.'],
    'unsupported browser' => ['unsupported', 'Your browser cannot open the camera here.'],
])->with([
    'portrait phone' => [390, 844],
    'short landscape phone' => [844, 390],
]);

it('releases camera access granted after the camera has been closed', function (): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Take photo');
    fakeMealCamera($page);
    $page->script(<<<'JS'
        () => {
            const getUserMedia = navigator.mediaDevices.getUserMedia;
            navigator.mediaDevices.getUserMedia = (constraints) => new Promise((resolve) => {
                window.resolveMealCamera = async () => resolve(await getUserMedia(constraints));
            });
        }
        JS);

    $page->press('Take photo')
        ->assertSee('Allow camera access to photograph your meal…')
        ->assertDisabled('button[aria-label="Capture photo"]')
        ->press('button[aria-label="Close camera"]')
        ->assertMissing('[role="dialog"]');
    $page->script('() => { void window.resolveMealCamera(); }');
    $page->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended')
        ->assertScript('document.activeElement.textContent.includes("Take photo")')
        ->assertDisabled('Analyze photo')
        ->assertNoJavaScriptErrors();
});

it('pauses the camera in the background and releases camera and preview resources on navigation', function (): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg');
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Take photo');
    fakeMealCamera($page);
    $page->script(<<<'JS'
        () => {
            window.revokedMealPhotoUrls = [];
            const revoke = URL.revokeObjectURL;
            URL.revokeObjectURL = (url) => {
                window.revokedMealPhotoUrls.push(url);
                revoke.call(URL, url);
            };
        }
        JS);
    $page->attach('#snap-photo', $photo->getPathname())
        ->assertVisible('img[alt="Meal photo preview"]');
    $preview = $page->script('document.querySelector("img[alt=\"Meal photo preview\"]").src');

    $page->press('Retake')->assertEnabled('button[aria-label="Capture photo"]');
    $page->script(<<<'JS'
        () => {
            Object.defineProperty(document, 'hidden', { configurable: true, value: true });
            document.dispatchEvent(new Event('visibilitychange'));
        }
        JS);
    $page->assertSee('Camera paused. Return to this screen to continue.')
        ->assertDisabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended');
    $page->script(<<<'JS'
        () => {
            Object.defineProperty(document, 'hidden', { configurable: true, value: false });
            document.dispatchEvent(new Event('visibilitychange'));
        }
        JS);
    $page->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraRequests.length', 2);
    $page->script('document.querySelector(\'a[href="/health-entries"]\').click()');
    $page->assertPathIs('/health-entries')
        ->assertScript('window.mealCameraStreams[1].getTracks()[0].readyState', 'ended')
        ->assertScript('window.revokedMealPhotoUrls[0]', $preview)
        ->assertNoJavaScriptErrors();
});

it('preserves the photo after a server validation error and clears the error when it is replaced', function (): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg');
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Take photo');
    $page->script(<<<'JS'
        () => {
            const send = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (body) {
                if (body instanceof FormData && body.has('analysis_request_id')) {
                    body.delete('photo');
                }
                return send.call(this, body);
            };
        }
        JS);
    $page->attach('#snap-photo', $photo->getPathname())
        ->assertVisible('img[alt="Meal photo preview"]');
    $preview = $page->script('document.querySelector("img[alt=\"Meal photo preview\"]").src');

    $page->press('Analyze photo')
        ->assertSee('The photo field is required.')
        ->assertAttribute('#snap-photo', 'aria-invalid', 'true')
        ->assertAttribute('img[alt="Meal photo preview"]', 'src', $preview)
        ->assertEnabled('Analyze photo')
        ->attach('#snap-photo', $photo->getPathname())
        ->assertDontSee('The photo field is required.')
        ->assertAttribute('#snap-photo', 'aria-invalid', 'false')
        ->assertNoJavaScriptErrors();

    expect(AnalysisDraft::query()->count())->toBe(0);
});

it('allows another camera attempt when a photo cannot be captured', function (): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Take photo');
    fakeMealCamera($page);
    $page->script(<<<'JS'
        () => {
            window.mealPhotoToBlob = HTMLCanvasElement.prototype.toBlob;
            HTMLCanvasElement.prototype.toBlob = (callback) => callback(null);
        }
        JS);

    $page->press('Take photo')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->press('button[aria-label="Capture photo"]')
        ->assertSee('We could not take the photo. Try again or choose a photo.')
        ->assertDisabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraStreams[0].getTracks()[0].readyState', 'ended');
    $page->script('() => { HTMLCanvasElement.prototype.toBlob = window.mealPhotoToBlob; }');
    $page->press('Try camera again')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->press('button[aria-label="Capture photo"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('window.mealCameraStreams[1].getTracks()[0].readyState', 'ended')
        ->assertNoJavaScriptErrors();
});

it('keeps camera and gallery actions hidden while the scan limit is reached', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    config()->set('plate.snap_to_track.burst_caps.default', 1);
    RateLimiter::hit(ResolveSnapBurstCap::cacheKeyFor($user, '127.0.0.1'), 60);

    visit(route('snap-to-track.index', absolute: false))
        ->assertSee("You've hit this hour's scan limit.")
        ->assertMissing('button:has-text("Take photo")')
        ->assertMissing('button:has-text("Choose photo")')
        ->assertMissing('#snap-photo')
        ->assertNoJavaScriptErrors();
});
