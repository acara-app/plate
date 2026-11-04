<?php

declare(strict_types=1);

use App\Models\AnalysisDraft;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Pest\Browser\Api\PendingAwaitablePage;

function openGuestSnapResult(): PendingAwaitablePage
{
    $token = Str::random(64);
    AnalysisDraft::factory()->create(['token_hash' => AnalysisDraft::hashToken($token)]);

    Route::middleware('web')->get('/testing/guest-snap-result', function () use ($token) {
        session()->put('snap_to_track.result', ['draft' => $token, 'request_id' => 'analysis-request-1']);

        return to_route('snap-to-track.result');
    });

    return visit('/testing/guest-snap-result');
}

function keepMealPhotoInThisTab(PendingAwaitablePage $page, string $requestId, int $minutesAgo = 0): void
{
    $page->script("(() => { const canvas = document.createElement('canvas'); canvas.width = 320; canvas.height = 240; canvas.getContext('2d').fillRect(0, 0, 320, 240); sessionStorage.setItem('snap_to_track.photo', JSON.stringify({ requestId: '{$requestId}', dataUrl: canvas.toDataURL('image/jpeg', 0.8), storedAt: Date.now() - {$minutesAgo} * 60000 })); return true; })()");
    $page->script('setTimeout(() => location.reload(), 0)');
    $page->wait(2);
}

it('shows the visitor their own meal photo kept in this browser tab', function (): void {
    $page = openGuestSnapResult();

    $page->assertSee('Preview · not saved yet')
        ->assertMissing('img[alt="Your meal photo"]');

    keepMealPhotoInThisTab($page, 'analysis-request-1');

    $page->assertVisible('img[alt="Your meal photo"]')
        ->assertNoJavaScriptErrors();
});

it('never shows a photo kept for a different analysis', function (): void {
    $page = openGuestSnapResult();

    keepMealPhotoInThisTab($page, 'another-analysis');

    $page->assertSee('Preview · not saved yet')
        ->assertMissing('img[alt="Your meal photo"]');
});

it('drops a photo kept longer than the preview lasts', function (): void {
    $page = openGuestSnapResult();

    keepMealPhotoInThisTab($page, 'analysis-request-1', minutesAgo: 61);

    $page->assertSee('Preview · not saved yet')
        ->assertMissing('img[alt="Your meal photo"]');

    expect($page->script("sessionStorage.getItem('snap_to_track.photo')"))->toBeNull();
});
