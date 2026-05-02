<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

function fakeTurnstileForSnapToTrack(bool $success = true): void
{
    if ($success) {
        Turnstile::fake();
    } else {
        Turnstile::fake()->fail();
    }
}

function verifiedTurnstileTokenForSnapToTrack(): string
{
    $token = ClientInterface::RESPONSE_DUMMY_TOKEN;

    Cache::put('snap-to-track-turnstile:'.sha1($token).':127.0.0.1', true, now()->addMinutes(10));

    return $token;
}

beforeEach(function (): void {
    RateLimiter::clear('snap-to-track:127.0.0.1');
    RateLimiter::clear('snap-to-track-upload:127.0.0.1');
    Cache::forget('snap-to-track-turnstile:'.sha1(ClientInterface::RESPONSE_DUMMY_TOKEN).':127.0.0.1');
});

it('renders the snap to track page', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertStatus(200)
        ->assertSee('Snap to Track')
        ->assertSee('Track calories & macros instantly with AI');
});

it('shows upload area when no photo is selected', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Tap to take photo or upload')
        ->assertSee('JPG, PNG up to 10MB');
});

it('validates photo is required', function (): void {
    fakeTurnstileForSnapToTrack();

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertHasErrors(['photo' => 'required']);
});

it('validates photo max size', function (): void {
    fakeTurnstileForSnapToTrack();

    $file = UploadedFile::fake()->image('large-image.jpg')->size(11000);

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertHasErrors(['photo']);
});

it('shows analyze button after photo is uploaded', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->assertSee('Analyze Food')
        ->assertDontSee('Tap to take photo or upload');
});

it('can clear photo and reset state', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', $file);

    $photo = $component->instance()->photo;

    expect($photo)
        ->toBeInstanceOf(TemporaryUploadedFile::class)
        ->and(file_exists($photo->getRealPath()))->toBeTrue();

    $component
        ->call('clearPhoto')
        ->assertSet('photo', null)
        ->assertSet('result', null)
        ->assertSet('error', null);

    expect(file_exists($photo->getRealPath()))->toBeFalse();
});

it('displays result after successful analysis', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Grilled Chicken', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 165,
            'total_protein' => 31,
            'total_carbs' => 0,
            'total_fat' => 3.6,
            'confidence' => 85,
        ],
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSet('result.totalCalories', 165.0)
        ->assertSet('result.totalProtein', 31.0)
        ->assertSet('result.totalCarbs', 0.0)
        ->assertSet('result.totalFat', 3.6)
        ->assertSet('result.confidence', 85)
        ->assertSet('photo', null)
        ->assertSee('165')
        ->assertSee('Grilled Chicken')
        ->assertSee('85% confident');
});

it('displays multiple food items in result', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Rice', 'calories' => 130, 'protein' => 2.7, 'carbs' => 28, 'fat' => 0.3, 'portion' => '100g'],
                ['name' => 'Chicken', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 295,
            'total_protein' => 33.7,
            'total_carbs' => 28,
            'total_fat' => 3.9,
            'confidence' => 90,
        ],
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSee('Rice')
        ->assertSee('Chicken')
        ->assertSee('295')
        ->assertSee('Food Items Detected');
});

it('displays error when analysis fails', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('AI analysis failed');
    });

    $file = UploadedFile::fake()->image('food.jpg');

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', $file);

    $photo = $component->instance()->photo;

    expect($photo)
        ->toBeInstanceOf(TemporaryUploadedFile::class)
        ->and(file_exists($photo->getRealPath()))->toBeTrue();

    $component
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSet('error', 'Something went wrong. Please try again.')
        ->assertSet('photo', null)
        ->assertSet('result', null);

    expect(file_exists($photo->getRealPath()))->toBeFalse();
});

it('shows tips for best results when no photo is selected', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Tips for best results')
        ->assertSee('Take photo in good lighting')
        ->assertSee('Make sure all food is visible');
});

it('shows disclaimer about AI estimates', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Disclaimer')
        ->assertSee('These are AI estimates');
});

it('shows faq section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Frequently Asked Questions')
        ->assertSee('How does the AI food photo analyzer work?')
        ->assertSee('How accurate are calorie estimates from food photos?')
        ->assertSee('Is my food photo kept private?');
});

it('renders the seo definition block', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('What is Snap to Track?')
        ->assertSee('is a free AI food photo analyzer that estimates calories, protein, carbs, and fat');
});

it('renders authority and freshness signals', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('AI vision analysis')
        ->assertSee('USDA-aligned nutrition references')
        ->assertSee('Last updated:');
});

it('shows sharper-analysis upsell after result', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Apple', 'calories' => 52, 'protein' => 0.3, 'carbs' => 14, 'fat' => 0.2, 'portion' => '1 medium'],
            ],
            'total_calories' => 52,
            'total_protein' => 0.3,
            'total_carbs' => 14,
            'total_fat' => 0.2,
            'confidence' => 95,
        ],
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSee('Did the AI guess on a few items?')
        ->assertSee('Mixed dishes, sauces, and oils are tough for a quick scan')
        ->assertSee('Sign up for sharper analysis')
        ->assertSee('save every meal to your history')
        ->assertSee('Analyze another photo')
        ->assertDontSee('Altani');
});

it('handles empty food detection gracefully', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [],
            'total_calories' => 0,
            'total_protein' => 0,
            'total_carbs' => 0,
            'total_fat' => 0,
            'confidence' => 0,
        ],
    ]);

    $file = UploadedFile::fake()->image('empty.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSet('result.totalCalories', 0.0)
        ->assertSet('result.confidence', 0)
        ->assertSee('No food items were detected');
});

it('validates turnstile token is required in testing environment', function (): void {
    fakeTurnstileForSnapToTrack();

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->call('analyze')
        ->assertHasErrors(['turnstileToken' => 'required']);
});

it('validates turnstile token before generating a temporary upload URL', function (): void {
    fakeTurnstileForSnapToTrack(success: false);

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('_startUpload', 'photo', [['name' => 'food.jpg', 'size' => 1024, 'type' => 'image/jpeg']], false)
        ->assertHasErrors(['turnstileToken']);
});

it('blocks temporary upload URL generation once the upload limit is exhausted', function (): void {
    fakeTurnstileForSnapToTrack();

    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('_startUpload', 'photo', [['name' => 'food.jpg', 'size' => 1024, 'type' => 'image/jpeg']], false)
        ->assertHasErrors(['photo']);
});

it('configures Livewire temporary uploads for photo-only hourly throttling', function (): void {
    expect(config('livewire.temporary_file_upload.rules'))
        ->toBe(['required', 'image', 'max:10240'])
        ->and(config('livewire.temporary_file_upload.middleware'))
        ->toBe('throttle:5,60');
});

it('blocks the analyze call once the per-IP rate limit is exhausted', function (): void {
    fakeTurnstileForSnapToTrack();

    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Agent should not have been called when rate limited');
    });

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertSet('error', 'Too many requests. Please try again later.')
        ->assertSet('result', null);
});

it('shows how it works section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('How it works')
        ->assertSee('Snap a photo of your meal')
        ->assertSee('AI identifies each food item')
        ->assertSee('Get instant macro breakdown');
});

it('shows main app promo section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Need more than just tracking?')
        ->assertSee('Get Started');
});

it('shows explore more tools section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Explore More Tools')
        ->assertSee('View All Tools');
});
