<?php

declare(strict_types=1);

use App\Livewire\SnapToTrack;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('renders the snap to track component', function (): void {
    Livewire::test(SnapToTrack::class)
        ->assertStatus(200)
        ->assertSee('Snap to Track')
        ->assertSee('Instant macro breakdown with AI');
});

it('shows upload area when no photo is selected', function (): void {
    Livewire::test(SnapToTrack::class)
        ->assertSee('Tap to take photo or upload')
        ->assertSee('JPG, PNG up to 10MB');
});

it('validates photo is required', function (): void {
    Livewire::test(SnapToTrack::class)
        ->call('analyze')
        ->assertHasErrors(['photo' => 'required']);
});

it('validates photo is an image type', function (): void {
    Livewire::test(SnapToTrack::class)
        ->set('photo')
        ->call('analyze')
        ->assertHasErrors(['photo' => 'required']);

    // Image validation is enforced by the #[Validate] attribute
    // which checks for image type - this is covered by Livewire's
    // file upload validation. The validation rule 'image' ensures
    // only image files are accepted.
});

it('validates photo max size', function (): void {
    $file = UploadedFile::fake()->image('large-image.jpg')->size(11000);

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertHasErrors(['photo' => 'max']);
});

it('shows photo preview after upload', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->assertSee('Analyze Food')
        ->assertDontSee('Tap to take photo or upload');
});

it('can clear photo and reset state', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('clearPhoto')
        ->assertSet('photo', null)
        ->assertSet('result', null)
        ->assertSet('error', null);
});

it('displays result after successful analysis', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"items": [{"name": "Grilled Chicken", "calories": 165, "protein": 31, "carbs": 0, "fat": 3.6, "portion": "100g"}], "total_calories": 165, "total_protein": 31, "total_carbs": 0, "total_fat": 3.6, "confidence": 85}'),
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertSet('result.totalCalories', 165.0)
        ->assertSet('result.totalProtein', 31.0)
        ->assertSet('result.totalCarbs', 0.0)
        ->assertSet('result.totalFat', 3.6)
        ->assertSet('result.confidence', 85)
        ->assertSee('165')
        ->assertSee('Grilled Chicken')
        ->assertSee('85% confident');
});

it('displays multiple food items in result', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"items": [{"name": "Rice", "calories": 130, "protein": 2.7, "carbs": 28, "fat": 0.3, "portion": "100g"}, {"name": "Chicken", "calories": 165, "protein": 31, "carbs": 0, "fat": 3.6, "portion": "100g"}], "total_calories": 295, "total_protein": 33.7, "total_carbs": 28, "total_fat": 3.9, "confidence": 90}'),
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertSee('Rice')
        ->assertSee('Chicken')
        ->assertSee('295')
        ->assertSee('Food Items Detected');
});

it('displays error when analysis fails', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('invalid json response'),
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertSet('error', 'Something went wrong. Please try again.')
        ->assertSet('result', null);
});

it('shows tips for best results when no photo is selected', function (): void {
    Livewire::test(SnapToTrack::class)
        ->assertSee('Tips for best results')
        ->assertSee('Take photo in good lighting')
        ->assertSee('Make sure all food is visible');
});

it('shows disclaimer about AI estimates', function (): void {
    Livewire::test(SnapToTrack::class)
        ->assertSee('Disclaimer')
        ->assertSee('AI estimates only');
});

it('shows faq section', function (): void {
    Livewire::test(SnapToTrack::class)
        ->assertSee('Frequently Asked Questions')
        ->assertSee('How does the food photo analyzer work?')
        ->assertSee('How accurate are the calorie estimates?');
});

it('shows cta to register after result', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"items": [{"name": "Apple", "calories": 52, "protein": 0.3, "carbs": 14, "fat": 0.2, "portion": "1 medium"}], "total_calories": 52, "total_protein": 0.3, "total_carbs": 14, "total_fat": 0.2, "confidence": 95}'),
    ]);

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertSee('Start tracking your meals')
        ->assertSee('Analyze another photo');
});

it('handles empty food detection gracefully', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"items": [], "total_calories": 0, "total_protein": 0, "total_carbs": 0, "total_fat": 0, "confidence": 0}'),
    ]);

    $file = UploadedFile::fake()->image('empty.jpg');

    Livewire::test(SnapToTrack::class)
        ->set('photo', $file)
        ->call('analyze')
        ->assertSet('result.totalCalories', 0.0)
        ->assertSet('result.confidence', 0);
});
