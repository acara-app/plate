<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AnalyzeFoodPhotoAction;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

#[Layout('layouts.mini-app')]
final class SnapToTrack extends Component
{
    use WithFileUploads;

    #[Validate('required|image|max:10240')]
    public ?TemporaryUploadedFile $photo = null;

    public bool $loading = false;

    /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, totalCalories: float, totalProtein: float, totalCarbs: float, totalFat: float, confidence: int}|null */
    public ?array $result = null;

    public ?string $error = null;

    public function analyze(AnalyzeFoodPhotoAction $action): void
    {
        $this->error = null;
        $this->result = null;

        $this->validate();

        if (! $this->photo instanceof TemporaryUploadedFile) {
            $this->error = 'Please select a photo to analyze.'; // @codeCoverageIgnore

            return; // @codeCoverageIgnore
        }

        $this->loading = true;

        try {
            $imageContent = $this->photo->get();

            if ($imageContent === false) {
                throw new RuntimeException('Failed to read uploaded file.'); // @codeCoverageIgnore
            }

            $base64 = base64_encode($imageContent);
            $mimeType = $this->photo->getMimeType() ?? 'image/jpeg';

            $analysis = $action->handle($base64, $mimeType);

            $this->result = [
                'items' => $analysis->items->toArray(),
                'totalCalories' => $analysis->totalCalories,
                'totalProtein' => $analysis->totalProtein,
                'totalCarbs' => $analysis->totalCarbs,
                'totalFat' => $analysis->totalFat,
                'confidence' => $analysis->confidence,
            ];
        } catch (Throwable $e) {
            $this->error = 'Something went wrong. Please try again.';
            report($e);
        } finally {
            $this->loading = false;
        }
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->result = null;
        $this->error = null;
    }

    public function render(): View
    {
        return view('livewire.snap-to-track');
    }
}
