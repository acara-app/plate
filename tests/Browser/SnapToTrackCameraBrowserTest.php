<?php

declare(strict_types=1);

use App\Actions\Billing\ResolveSnapBurstCap;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Pest\Browser\Api\AwaitableWebpage;

function fakeMealCamera(AwaitableWebpage $page): void
{
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
            canvas.width = 640;
            canvas.height = 480;
            const context = canvas.getContext('2d');
            context.fillStyle = '#f0b67f';
            context.fillRect(0, 0, canvas.width, canvas.height);
            const stream = canvas.captureStream(10);
            window.mealCameraCanvases.push(canvas);
            window.mealCameraStreams.push(stream);
            return stream;
        };
        void 0;
        JS);
}

it('previews a captured meal and waits for confirmation before submitting it', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit(route('snap-to-track.index', absolute: false))->on()->mobile()
        ->assertSee('Analyze a meal photo');
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
        ->press('Take photo')
        ->assertVisible('[role="dialog"]')
        ->assertEnabled('button[aria-label="Capture photo"]')
        ->assertScript('window.mealCameraRequests[0].audio', false)
        ->assertScript('window.mealCameraRequests[0].video.facingMode.ideal', 'environment')
        ->press('button[aria-label="Capture photo"]')
        ->assertMissing('[role="dialog"]')
        ->assertVisible('img[alt="Meal photo preview"]')
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalHeight > document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth')
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

it('previews a gallery photo and preserves it when replacement is cancelled', function (bool $fromCamera): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg', 640, 480);
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
        ->assertScript('document.querySelector("img[alt=\"Meal photo preview\"]").naturalWidth', 640)
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
        ->attach('#snap-photo', $photo->getPathname())
        ->assertScript('window.revokedMealPhotoUrls[0]', $preview)
        ->assertNoJavaScriptErrors();
})->with([
    'from the page' => false,
    'from the camera' => true,
]);

it('keeps the photo picker available when camera access fails', function (string $failure, string $message): void {
    $this->actingAs(User::factory()->create());
    $photo = UploadedFile::fake()->image('meal.jpg');
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
]);

it('releases camera access granted after the camera has been closed', function (): void {
    $this->actingAs(User::factory()->create());
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
    $page = visit(route('snap-to-track.index', absolute: false))->assertSee('Analyze a meal photo');
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
