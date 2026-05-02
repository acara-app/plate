<?php

declare(strict_types=1);

use App\Actions\AnalyzeFoodPhotoAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free AI food photo analyzer: snap a meal to instantly estimate calories, protein, carbs, and fat per ingredient. No signup required to try it.', 'metaKeywords' => 'food photo calorie counter, snap to track calories, AI food recognition, meal photo analyzer, instant macro breakdown, calorie tracking app, food image analysis, nutrition scanner, AI calorie counter from photo, photo macro tracker'])]
#[Title('AI Food Photo Analyzer | Free Calorie & Macro Counter')]
class extends Component
{
    use WithFileUploads {
        _startUpload as private startLivewireUpload;
    }

    public ?TemporaryUploadedFile $photo = null;

    public ?string $turnstileToken = null;

    public bool $loading = false;

    /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, totalCalories: float, totalProtein: float, totalCarbs: float, totalFat: float, confidence: int}|null */
    public ?array $result = null;

    public ?string $error = null;

    public function _startUpload($name, $fileInfo, $isMultiple): void
    {
        if ($name === 'photo') {
            $this->validateUploadChallenge();
            $this->hitUploadRateLimit();
            $this->rememberVerifiedUploadChallenge();
        }

        $this->startLivewireUpload($name, $fileInfo, $isMultiple);
    }

    public function analyze(AnalyzeFoodPhotoAction $action): void
    {
        $this->error = null;
        $this->result = null;

        if (RateLimiter::tooManyAttempts($this->analysisRateLimitKey(), 5)) {
            $this->error = 'Too many requests. Please try again later.';
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);

            return;
        }

        $rules = [
            'photo' => 'required|image|max:10240',
        ];

        if (app()->environment(['production', 'testing'])) {
            $rules['turnstileToken'] = ['required', 'string'];
        }

        $this->loading = true;

        try {
            $this->validate($rules);
            $this->validateVerifiedUploadChallenge();

            if (! $this->photo instanceof TemporaryUploadedFile) {
                $this->error = 'Please select a photo to analyze.'; // @codeCoverageIgnore

                return; // @codeCoverageIgnore
            }

            RateLimiter::hit($this->analysisRateLimitKey(), 3600);

            $imageContent = $this->photo->get();

            if ($imageContent === false) {
                throw new RuntimeException('Failed to read uploaded file.'); // @codeCoverageIgnore
            }

            $base64 = base64_encode($imageContent);
            $mimeType = $this->photo->getMimeType();

            if ($mimeType === null) { // @phpstan-ignore-line
                $mimeType = 'image/jpeg'; // @codeCoverageIgnore
            }

            $analysis = $action->handle($base64, $mimeType);

            /** @var array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}> $items */
            $items = $analysis->items->toArray();

            $this->result = [
                'items' => $items,
                'totalCalories' => $analysis->totalCalories,
                'totalProtein' => $analysis->totalProtein,
                'totalCarbs' => $analysis->totalCarbs,
                'totalFat' => $analysis->totalFat,
                'confidence' => $analysis->confidence,
            ];
        } catch (ValidationException $e) {
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);

            throw $e;
        } catch (Throwable $e) {
            $this->error = 'Something went wrong. Please try again.';
            report($e);
        } finally {
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);
            $this->loading = false;
        }
    }

    public function clearPhoto(): void
    {
        $this->deleteTemporaryPhoto(resetUploadChallenge: true);
        $this->result = null;
        $this->error = null;
    }

    private function validateUploadChallenge(): void
    {
        if (! app()->environment(['production', 'testing'])) {
            return;
        }

        $this->validateOnly('turnstileToken', [
            'turnstileToken' => ['required', new Turnstile],
        ]);
    }

    private function validateVerifiedUploadChallenge(): void
    {
        if (! app()->environment(['production', 'testing'])) {
            return;
        }

        if (Cache::pull($this->verifiedUploadChallengeKey()) === true) {
            return;
        }

        throw ValidationException::withMessages([
            'turnstileToken' => 'Please complete the verification before uploading a photo.',
        ]);
    }

    private function hitUploadRateLimit(): void
    {
        if (RateLimiter::tooManyAttempts($this->uploadRateLimitKey(), 5)) {
            throw ValidationException::withMessages([
                'photo' => 'Too many uploads. Please try again later.',
            ]);
        }

        RateLimiter::hit($this->uploadRateLimitKey(), 3600);
    }

    private function rememberVerifiedUploadChallenge(): void
    {
        Cache::put($this->verifiedUploadChallengeKey(), true, now()->addMinutes(10));
    }

    private function analysisRateLimitKey(): string
    {
        return 'snap-to-track:'.request()->ip();
    }

    private function uploadRateLimitKey(): string
    {
        return 'snap-to-track-upload:'.request()->ip();
    }

    private function verifiedUploadChallengeKey(): string
    {
        return 'snap-to-track-turnstile:'.sha1((string) $this->turnstileToken).':'.request()->ip();
    }

    private function deleteTemporaryPhoto(bool $resetUploadChallenge = false): void
    {
        if ($this->photo instanceof TemporaryUploadedFile && $this->photo->exists()) {
            $this->photo->delete();
        }

        $this->photo = null;

        if ($resetUploadChallenge) {
            $this->resetUploadChallenge();
        }
    }

    private function resetUploadChallenge(): void
    {
        if ($this->turnstileToken !== null) {
            Cache::forget($this->verifiedUploadChallengeKey());
        }

        $this->turnstileToken = null;
    }
};
?>

<x-slot:jsonLd>
    <x-json-ld.snap-to-track />
</x-slot:jsonLd>

@push('turnstile')
    @if (App::environment(['production', 'testing']))
        <x-turnstile.scripts />
    @endif
@endpush

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-blue-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-blue-300/20 blur-3xl dark:bg-blue-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-6 w-full max-w-md lg:mb-8">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Card --}}
    <main class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-blue-500/10 dark:bg-slate-800 dark:shadow-blue-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-2xl dark:bg-blue-900/50">📸</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Snap to Track: AI Food Photo Analyzer</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Track calories &amp; macros instantly with AI</p>
        </div>

        {{-- Definition / What is this? (AI-search extractable) --}}
        <section aria-labelledby="what-heading" class="speakable-definition rounded-xl bg-slate-50 p-4 text-center text-sm leading-relaxed text-slate-600 dark:bg-slate-900/40 dark:text-slate-300">
            <h2 id="what-heading" class="sr-only">What is Snap to Track?</h2>
            <p>
                <strong class="text-slate-900 dark:text-white">Snap to Track</strong> is a free AI food photo analyzer that estimates calories, protein, carbs, and fat for every ingredient in your meal. Upload one photo, get a per-item nutrition breakdown in about 5–15 seconds — no signup required.
            </p>
        </section>

        {{-- Upload + Analyze Form --}}
        @if ($result === null)
            <form wire:submit="analyze" class="space-y-4">
                @if (! $photo)
                    @if (App::environment(['production', 'testing']))
                        <div class="flex justify-center">
                            <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                        </div>
                        @error('turnstileToken')
                            <p class="text-center text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    @endif

                    {{-- Upload Area --}}
                    <div class="relative {{ App::environment(['production', 'testing']) && blank($turnstileToken) ? 'opacity-50' : '' }}">
                        <input
                            type="file"
                            wire:model="photo"
                            accept="image/*"
                            capture="environment"
                            class="hidden"
                            id="photo-upload"
                            @disabled($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))
                        >
                        <label
                            for="photo-upload"
                            class="flex min-h-40 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 transition-colors hover:border-blue-500 hover:bg-blue-50/50 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
                        >
                            <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tap to take photo or upload</span>
                            <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">JPG, PNG up to 10MB</span>
                        </label>
                        @error('photo')
                            <p class="mt-2 text-center text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tips for best results --}}
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-lg">💡</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">Tips for best results</span>
                        </div>
                        <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                            <li>• Take photo in good lighting</li>
                            <li>• Make sure all food is visible</li>
                            <li>• Capture from directly above</li>
                            <li>• Include a reference for scale (optional)</li>
                        </ul>
                    </div>
                @else
                    {{-- Photo Preview --}}
                    <div class="relative overflow-hidden rounded-xl">
                        <img
                            src="{{ $photo->temporaryUrl() }}"
                            alt="Food photo preview"
                            class="h-48 w-full object-cover"
                        >
                        <button
                            type="button"
                            wire:click="clearPhoto"
                            class="absolute right-2 top-2 rounded-full bg-slate-900/70 p-2 text-white transition-colors hover:bg-slate-900"
                            title="Remove photo"
                            @disabled($loading)
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if (App::environment(['production', 'testing']))
                        <div class="flex justify-center">
                            <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                        </div>
                        @error('turnstileToken')
                            <p class="text-center text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    @endif

                    {{-- Analyze Button --}}
                    <button
                        type="submit"
                        class="w-full min-h-14 rounded-xl bg-blue-600 py-4 text-center font-bold text-white transition-all hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                        @disabled($loading)
                    >
                        <span wire:loading.remove wire:target="analyze" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Analyze Food
                        </span>
                        <span wire:loading wire:target="analyze" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Analyzing your meal...
                        </span>
                    </button>

                    {{-- Loading progress hint --}}
                    <div wire:loading wire:target="analyze" class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-900/50">
                        <p class="text-xs text-slate-500 dark:text-slate-400">This usually takes 5–15 seconds. Hang tight.</p>
                    </div>
                @endif

                @if ($error)
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-center dark:border-rose-900/50 dark:bg-rose-900/20">
                        <p class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ $error }}</p>
                    </div>
                @endif
            </form>
        @else
            {{-- Real Results --}}
            <div
                x-data
                x-init="$el.classList.remove('opacity-0', 'translate-y-4')"
                class="overflow-hidden rounded-2xl border border-slate-100 bg-white opacity-0 translate-y-4 shadow-lg transition-all duration-500 ease-out dark:border-slate-700 dark:bg-slate-800"
            >
                {{-- Total Macros Header --}}
                <div class="bg-slate-50 p-6 dark:bg-slate-800/50">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Nutrition</span>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            {{ $result['confidence'] }}% confident
                        </span>
                    </div>

                    {{-- Calorie Display --}}
                    <div class="mb-4 text-center">
                        <span class="text-5xl font-black text-slate-900 dark:text-white">{{ number_format($result['totalCalories'], 0) }}</span>
                        <span class="ml-1 text-lg font-medium text-slate-400">kcal</span>
                        <div class="mt-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">~{{ round($result['totalCalories'] / 2000 * 100) }}% of a 2,000 kcal daily goal</p>
                            <div class="mx-auto mt-1 h-1.5 w-3/4 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, round($result['totalCalories'] / 2000 * 100)) }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Macro Bars --}}
                    <div class="grid grid-cols-3 gap-4">
                        {{-- Protein --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, ($result['totalProtein'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($result['totalProtein'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Protein</p>
                        </div>
                        {{-- Carbs --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, ($result['totalCarbs'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($result['totalCarbs'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Carbs</p>
                        </div>
                        {{-- Fat --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-rose-500" style="width: {{ min(100, ($result['totalFat'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ number_format($result['totalFat'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Fat</p>
                        </div>
                    </div>
                </div>

                {{-- Individual Items --}}
                <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Food Items Detected</h3>
                    @if (count($result['items']) === 0)
                        <p class="text-center text-sm text-slate-500 dark:text-slate-400">No food items were detected. Try a clearer photo with better lighting.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($result['items'] as $item)
                                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ $item['name'] }}</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item['portion'] }}</p>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ number_format($item['calories'], 0) }} kcal</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                            P {{ number_format($item['protein'], 1) }}g
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            C {{ number_format($item['carbs'], 1) }}g
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            F {{ number_format($item['fat'], 1) }}g
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sharper-analysis upsell --}}
                <div class="space-y-3 border-t border-slate-100 p-4 dark:border-slate-700">
                    <div class="flex items-start gap-2">
                        <span class="text-lg" aria-hidden="true">💬</span>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">Did the AI guess on a few items?</p>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                Mixed dishes, sauces, and oils are tough for a quick scan. Sign up free to unlock sharper analysis — and save every meal to your history.
                            </p>
                        </div>
                    </div>
                    <a
                        href="{{ route('register') }}"
                        class="block w-full rounded-xl bg-blue-600 py-3.5 text-center text-sm font-bold text-white transition-all hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Sign up for sharper analysis →
                    </a>
                    <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                        Already a member? <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Log in</a>
                    </p>
                </div>
            </div>

            {{-- Analyze Another --}}
            <button
                type="button"
                wire:click="clearPhoto"
                class="w-full rounded-xl border-2 border-slate-200 py-3 text-center text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                Analyze another photo
            </button>
        @endif

        {{-- How It Works Section --}}
        <section id="how-it-works" class="space-y-3 speakable-how-it-works">
            <h2 class="text-center text-sm font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">How it works</h2>
            <ol class="grid grid-cols-3 gap-3 text-center">
                <li class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">1</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Snap a photo of your meal</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">2</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">AI identifies each food item</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">3</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Get instant macro breakdown</p>
                </li>
            </ol>
        </section>

        {{-- Sign Up CTA (only when no result is shown) --}}
        @if ($result === null)
            <a
                href="{{ route('register') }}"
                class="block w-full rounded-xl bg-blue-600 py-3.5 text-center text-sm font-bold text-white transition-all hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98]"
            >
                Sign up to start analyzing →
            </a>
            <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                Already have an account? <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Log in</a>
            </p>
        @endif

        <div class="space-y-1 text-center text-xs text-slate-400 dark:text-slate-500">
            <p>
                <strong>Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.
            </p>
            <p>
                AI vision analysis · USDA-aligned nutrition references
            </p>
            <p>
                <time datetime="{{ now()->toDateString() }}">Last updated: {{ now()->format('F Y') }}</time>
            </p>
        </div>

    </main>

    {{-- FAQ Section --}}
    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Frequently Asked Questions
        </h2>

        <div class="space-y-3" x-data="{ openFaq: null }">
            {{-- FAQ 1 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 1 ? null : 1"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How does the AI food photo analyzer work?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Upload a photo of your meal and our AI vision model identifies each food item, estimates portion size, and calculates calories, protein, carbs, and fat for every item plus the full meal. Nutrition values are derived from USDA FoodData Central reference data, and you get a confidence score so you know how reliable each estimate is.</p>
                </div>
            </div>

            {{-- FAQ 2 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 2 ? null : 2"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How accurate are calorie estimates from food photos?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Accuracy depends on photo clarity, lighting, and how visible each ingredient is. In good conditions, AI photo estimates land within roughly 10–20% of actual values for whole foods, and the tool returns a confidence score (0–100%) for each meal so you can judge reliability. Mixed dishes, sauces, and oils are harder to estimate than visible whole foods.</p>
                </div>
            </div>

            {{-- FAQ 3 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 3 ? null : 3"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>What types of food can the AI recognize?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>The analyzer recognizes most common foods: fruits, vegetables, grains, meats, fish, dairy, packaged snacks, drinks, and prepared dishes from many cuisines. It works best when each item is clearly visible from above with good lighting. Hidden ingredients (oils, sauces, dressings, broths) are harder to detect, so single-ingredient and well-lit plate shots produce the most reliable results.</p>
                </div>
            </div>

            {{-- FAQ 4 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 4 ? null : 4"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Is my food photo kept private?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Yes. Your photo is used only to generate the nutrition analysis. Livewire stores it as a temporary upload while the scan runs, then we delete that temporary file as soon as the result or error is returned. We do not retain images, share them with third parties, or use them to train AI models. Authenticated users can opt to log meals with photos to their personal history; on this public tool, no image is saved.</p>
                </div>
            </div>

            {{-- FAQ 5 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 5 ? null : 5"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How do I use Snap to Track?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Open this page on your phone or laptop, tap the upload area to take a new photo or pick one from your gallery, then tap Analyze Food. In about 5–15 seconds you get a per-item breakdown of calories, protein, carbs, and fat plus meal totals. No signup is required to try it; create a free account to save and track meals over time.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main App Promo --}}
    <section class="relative z-10 mt-8 w-full max-w-md">
        <div class="overflow-hidden rounded-2xl bg-slate-900 px-6 py-8 text-center shadow-xl shadow-slate-900/10 dark:bg-slate-800 dark:ring-1 dark:ring-white/10">
            <div class="mb-4 flex justify-center">
                <span class="text-4xl">🥗</span>
            </div>
            <h2 class="mb-3 text-xl font-bold text-white">
                Need more than just tracking?
            </h2>
            <p class="mb-6 text-sm leading-relaxed text-slate-300">
                Get personalized meal plans tailored to your glucose levels and taste preferences.
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex w-full items-center justify-center rounded-xl bg-white py-3.5 text-sm font-bold text-slate-900 transition-transform hover:scale-[1.02] hover:bg-slate-50">
                Get Started
            </a>
        </div>
    </section>

    {{-- More Free Tools --}}
    <section class="relative z-10 mt-12 mb-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Explore More Tools
        </h2>
        <a href="{{ route('tools.index') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
            <span class="mb-2 text-3xl">🛠️</span>
            <h3 class="font-bold text-slate-900 dark:text-white">View All Tools</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Discover health trackers, calculators, and nutrition tools</p>
        </a>
    </section>

    <x-footer />
</div>
