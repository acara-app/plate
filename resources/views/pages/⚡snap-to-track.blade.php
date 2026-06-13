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

    /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string, provenance: string}>, totalCalories: float, totalProtein: float, totalCarbs: float, totalFat: float, confidence: int}|null */
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

<div class="min-h-screen bg-[#F2EBDD] text-[#1A1814]">
    <x-tools-header theme="cream" />

    <div class="px-4 py-8 md:py-12">
        {{-- Editorial breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="mx-auto flex max-w-7xl items-center gap-2 font-mono text-[11px] uppercase tracking-[0.14em] text-[#6E665C] lg:px-8">
            <a href="/" aria-label="Home" class="inline-flex items-center transition hover:text-[#1A1814]">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="size-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('tools.index') }}" class="transition hover:text-[#1A1814]">Tools</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="text-[#1A1814]">Snap to Track</span>
        </nav>

        {{-- Hero --}}
        <header class="mx-auto mt-6 max-w-7xl lg:px-8">
            <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                <span class="size-1.5 rounded-full bg-[#C4623A] shadow-[0_0_0_3px_rgba(196,98,58,0.18)]" aria-hidden="true"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">AI Food Photo Analyzer</span>
            </div>

            <h1 class="mt-5 max-w-4xl text-balance font-bold text-[clamp(40px,5vw,68px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                Snap to Track
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                Track calories &amp; macros instantly with AI. One photo, 5–15 seconds, a per-ingredient breakdown — no signup required.
            </p>
        </header>

        {{-- Tool --}}
        <section class="mx-auto mt-8 max-w-3xl lg:px-8">
            @if ($result === null)
                <form wire:submit="analyze" class="space-y-6">
                    <div class="border border-[#D9CFBC] bg-[#EBE2D0] p-6 sm:p-8">
                        <div class="flex items-center justify-between gap-4 pb-5">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                {{ $photo ? 'Step 2 · Analyze' : 'Step 1 · Upload' }}
                            </p>
                            <span class="inline-flex items-center gap-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                <span class="size-1.5 rounded-full bg-emerald-600" aria-hidden="true"></span>
                                Live
                            </span>
                        </div>

                        @if (! $photo)
                            @if (App::environment(['production', 'testing']))
                                <div class="border-t border-[#D9CFBC] pt-6">
                                    <div class="flex justify-center">
                                        <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                                    </div>
                                    @error('turnstileToken')
                                        <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-[#B5482E]">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Upload Area --}}
                            <div class="relative border-t border-[#D9CFBC] pt-6 {{ App::environment(['production', 'testing']) && blank($turnstileToken) ? 'opacity-50' : '' }}">
                                <input
                                    type="file"
                                    wire:model="photo"
                                    accept="image/*"
                                    class="hidden"
                                    id="photo-upload"
                                    @disabled($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))
                                >
                                <label
                                    for="photo-upload"
                                    data-umami-event="snap_to_track_upload_click"
                                    data-umami-event-location="main_form"
                                    class="group flex min-h-60 cursor-pointer flex-col items-center justify-center border-2 border-dashed border-[#D9CFBC] bg-[#F2EBDD] p-8 text-center transition hover:border-[#1A1814] hover:bg-[#F2EBDD]/70"
                                >
                                    <div class="mb-5 flex size-14 items-center justify-center border border-[#1A1814] bg-[#F2EBDD] text-[#1A1814] transition group-hover:bg-[#1A1814] group-hover:text-[#F2EBDD]">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-lg tracking-[-0.01em] text-[#1A1814]">Tap to take photo or upload</span>
                                    <span class="mt-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">JPG, PNG up to 10MB</span>
                                </label>
                                @error('photo')
                                    <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-[#B5482E]">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tips --}}
                            <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Tips for best results</p>
                                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        'Take photo in good lighting',
                                        'Make sure all food is visible',
                                        'Capture from directly above',
                                        'Include a reference for scale (optional)',
                                    ] as $tip)
                                        <li class="flex items-start gap-3 text-sm leading-relaxed text-[#3D3833]">
                                            <span class="mt-2 size-1 shrink-0 bg-[#C4623A]" aria-hidden="true"></span>
                                            <span>{{ $tip }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            {{-- Photo preview --}}
                            <div class="border-t border-[#D9CFBC] pt-6">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Photo loaded</p>
                                    <button
                                        type="button"
                                        wire:click="clearPhoto"
                                        class="font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C] transition hover:text-[#B5482E]"
                                        title="Remove photo"
                                        @disabled($loading)
                                    >
                                        Remove ×
                                    </button>
                                </div>
                                <div class="mt-3 border border-[#D9CFBC] bg-[#F2EBDD] p-2">
                                    <img
                                        src="{{ $photo->temporaryUrl() }}"
                                        alt="Food photo preview"
                                        class="h-72 w-full object-cover"
                                    >
                                </div>
                            </div>

                            @if (App::environment(['production', 'testing']))
                                <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                                    <div class="flex justify-center">
                                        <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                                    </div>
                                    @error('turnstileToken')
                                        <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-[#B5482E]">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                                <button
                                    type="submit"
                                    data-umami-event="snap_to_track_analyze_click"
                                    data-umami-event-location="main_form"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#1A1814] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus:outline-none focus:ring-2 focus:ring-[#1A1814] focus:ring-offset-2 focus:ring-offset-[#EBE2D0] disabled:cursor-not-allowed disabled:opacity-50"
                                    @disabled($loading)
                                >
                                    <span wire:loading.remove wire:target="analyze" class="inline-flex items-center gap-2">
                                        Analyze Food
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="analyze" class="inline-flex items-center gap-2">
                                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Analyzing your meal...
                                    </span>
                                </button>

                                <p wire:loading wire:target="analyze" class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                                    This usually takes 5–15 seconds. Hang tight.
                                </p>
                            </div>
                        @endif

                        @if ($error)
                            <div class="mt-6 border border-[#B5482E]/40 bg-[#B5482E]/5 p-4">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#B5482E]">Hit a snag</p>
                                <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">{{ $error }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Authority footer --}}
                    <div class="flex flex-col gap-1 px-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                        <p><strong class="font-bold text-[#3D3833]">Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.</p>
                        <p>AI vision analysis · USDA-aligned nutrition references</p>
                        <p><time datetime="{{ now()->toDateString() }}">Last updated: {{ now()->format('F Y') }}</time></p>
                    </div>
                </form>
            @else
                {{-- Result --}}
                @php
                    $totalMacroWeight = max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat']);
                @endphp
                <div
                    x-data
                    x-init="window.acaraTrack?.('snap_to_track_result_viewed', { confidence: @js($result['confidence']), items_count: @js(count($result['items'])) }); $el.classList.remove('opacity-0', 'translate-y-2')"
                    class="opacity-0 translate-y-2 transition-all duration-500 motion-reduce:transition-none motion-reduce:opacity-100 motion-reduce:translate-y-0"
                >
                    <div class="flex flex-col gap-4">
                        {{-- Total nutrition --}}
                        <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-6 sm:p-8">
                            <div class="flex items-baseline justify-between gap-4">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Total Nutrition</p>
                                <span class="border border-[#C4623A] px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-[0.18em] text-[#C4623A]">
                                    {{ $result['confidence'] }}% confident
                                </span>
                            </div>

                            <div class="mt-5 flex items-end gap-3">
                                <span class="font-bold text-[clamp(56px,7vw,96px)] leading-[1] tracking-[-0.03em] text-[#1A1814]">{{ number_format($result['totalCalories'], 0) }}</span>
                                <span class="mb-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">kcal</span>
                            </div>
                            <p class="mt-3 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                                ~{{ round($result['totalCalories'] / 2000 * 100) }}% of a 2,000 kcal daily goal
                            </p>
                            <div class="mt-2 h-1.5 w-full overflow-hidden bg-[#D9CFBC]">
                                <div class="h-full bg-[#C4623A] transition-[width] duration-700" style="width: {{ min(100, round($result['totalCalories'] / 2000 * 100)) }}%"></div>
                            </div>

                            {{-- Macro grid --}}
                            <div class="mt-7 grid grid-cols-3 gap-6 border-t border-[#D9CFBC] pt-6">
                                @foreach ([
                                    ['label' => 'Protein', 'value' => $result['totalProtein']],
                                    ['label' => 'Carbs', 'value' => $result['totalCarbs']],
                                    ['label' => 'Fat', 'value' => $result['totalFat']],
                                ] as $macro)
                                    <div>
                                        <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">{{ $macro['label'] }}</p>
                                        <p class="mt-1 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814]">
                                            {{ number_format($macro['value'], 1) }}<span class="ml-1 font-mono text-xs uppercase tracking-[0.18em] text-[#6E665C]">g</span>
                                        </p>
                                        <div class="mt-2 h-1 w-full overflow-hidden bg-[#D9CFBC]">
                                            <div class="h-full bg-[#1A1814] transition-[width] duration-700" style="width: {{ min(100, ($macro['value'] / $totalMacroWeight) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-5 border-t border-[#D9CFBC] pt-4 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                                {{ App\Services\AiTransparency::carbBoundaryNotice() }}
                            </p>
                        </article>

                        {{-- Items list --}}
                        <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-6 sm:p-8">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Food Items Detected</p>
                            @if (count($result['items']) === 0)
                                <p class="mt-4 text-sm leading-relaxed text-[#3D3833]">No food items were detected. Try a clearer photo with better lighting.</p>
                            @else
                                <ul class="mt-4 divide-y divide-[#D9CFBC]">
                                    @foreach ($result['items'] as $i => $item)
                                        <li class="flex items-start gap-4 py-4 first:pt-0 last:pb-0">
                                            <span class="pt-1 font-mono text-[10px] uppercase tracking-[0.18em] text-[#C4623A]" aria-hidden="true">
                                                {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-baseline justify-between gap-3">
                                                    <h4 class="font-bold text-base leading-tight tracking-[-0.01em] text-[#1A1814]">{{ $item['name'] }}</h4>
                                                    <span class="shrink-0 font-bold text-sm tracking-[-0.01em] text-[#1A1814]">{{ number_format($item['calories'], 0) }} kcal</span>
                                                </div>
                                                <p class="mt-1 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">{{ $item['portion'] }}</p>
                                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">
                                                    <span>P {{ number_format($item['protein'], 1) }}g</span>
                                                    <span aria-hidden="true">·</span>
                                                    <span>C {{ number_format($item['carbs'], 1) }}g</span>
                                                    <span aria-hidden="true">·</span>
                                                    <span>F {{ number_format($item['fat'], 1) }}g</span>
                                                </div>
                                                @if (($item['provenance'] ?? 'model') === 'reference')
                                                    <p class="mt-2 inline-flex items-center gap-1 font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">
                                                        <span class="text-[#C4623A]" aria-hidden="true">◆</span> USDA reference
                                                    </p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>

                        {{-- Sharper analysis upsell (dark inverse) --}}
                        <article class="border border-[#1A1814] bg-[#1A1814] p-6 sm:p-8 text-[#F2EBDD]">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Sharper readings</p>
                            <h3 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em]">Did the AI guess on a few items?</h3>
                            <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">
                                Mixed dishes, sauces, and oils are tough for a quick scan. Sign up to save meals, build meal history, and help Acara remember what you actually eat.
                            </p>
                            <a
                                href="{{ route('register') }}"
                                data-umami-event="signup_cta_click"
                                data-umami-event-location="snap_to_track_result"
                                class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                            >
                                Sign up for sharper analysis
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                            <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.16em] text-[#F2EBDD]/70">
                                Already a member?
                                <a href="{{ route('login') }}" class="underline decoration-[#C4623A] underline-offset-4 transition hover:text-[#F2EBDD]">Log in</a>
                            </p>
                        </article>

                        {{-- Analyze another --}}
                        <button
                            type="button"
                            wire:click="clearPhoto"
                            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none border border-[#1A1814] bg-[#F2EBDD] px-6 text-sm font-semibold text-[#1A1814] transition hover:bg-[#1A1814] hover:text-[#F2EBDD]"
                        >
                            Analyze another photo
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>

                        {{-- Disclaimer --}}
                        <div class="flex flex-col gap-1 px-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                            <p><strong class="font-bold text-[#3D3833]">Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.</p>
                            <p>AI vision analysis · USDA-aligned nutrition references</p>
                            <p><time datetime="{{ now()->toDateString() }}">Last updated: {{ now()->format('F Y') }}</time></p>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- Field manual / definition (placed below the tool so the action stays above the fold) --}}
        <section aria-labelledby="what-heading" class="mx-auto mt-20 max-w-7xl lg:px-8">
            <div class="speakable-definition grid gap-6 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[1fr_2fr] sm:gap-12">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Field manual</p>
                    <h2 id="what-heading" class="sr-only">What is Snap to Track?</h2>
                </div>
                <p class="max-w-3xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                    <strong class="font-bold text-[#1A1814]">Snap to Track</strong> is a free AI food photo analyzer that estimates calories, protein, carbs, and fat for every ingredient in your meal. Upload one photo, get a per-item nutrition breakdown in about 5–15 seconds — no signup required.
                </p>
            </div>
        </section>

        {{-- Method --}}
        <section id="how-it-works" class="mx-auto mt-16 max-w-7xl lg:px-8" aria-labelledby="how-it-works-heading">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">How it works</p>
            <h2 id="how-it-works-heading" class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                Three steps from photo to plate.
            </h2>
            <p class="mt-5 max-w-3xl text-base leading-relaxed text-[#3D3833]">
                From a single photo to a full nutrition breakdown. No journaling, no manual entry, no guess-the-portion games.
            </p>

            <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-3 sm:divide-x sm:divide-[#D9CFBC]">
                <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">A</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Snap a photo of your meal</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        Take a clear, well-lit photo of your food from above. The AI will scan the image for recognizable ingredients.
                    </p>
                </article>
                <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">B</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">AI identifies each food item</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        Our vision model recognizes individual ingredients, estimates portion sizes, and cross-references USDA nutrition data.
                    </p>
                </article>
                <article class="px-2 pt-8 pb-10 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">C</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Get instant macro breakdown</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        See per-item calories, protein, carbs, and fat — plus meal totals and a confidence score for the analysis.
                    </p>
                </article>
            </div>
        </section>

        {{-- More than tracking promo --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="border border-[#D9CFBC] bg-[#EBE2D0] p-8 sm:p-12">
                <div class="grid gap-8 sm:grid-cols-[2fr_1fr] sm:items-center">
                    <div>
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Beyond tracking</p>
                        <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                            Need more than just tracking?
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#3D3833] sm:text-base">
                            Acara Cloud can use saved meals, logged history, and stable preferences to make future meal planning feel less generic.
                        </p>
                    </div>
                    <a
                        href="{{ route('register') }}"
                        data-umami-event="signup_cta_click"
                        data-umami-event-location="snap_to_track_bottom_promo"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                    >
                        Get Started
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8" aria-labelledby="faq-heading">
            <div class="grid gap-12 sm:grid-cols-[1fr_2fr]">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">FAQ</p>
                    <h2 id="faq-heading" class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">
                        Quick context for using the analyzer as a planning aid—not a replacement for a CGM, scale, or your clinician.
                        <a href="{{ route('ai-accuracy') }}" class="underline decoration-[#C4623A] underline-offset-4 transition hover:text-[#1A1814]">Read the full accuracy &amp; limitations breakdown</a>.
                    </p>
                </div>

                <div x-data="{ openFaq: 1 }">
                    @php
                    $faqs = App\Services\AiTransparency::snapToTrackFaqs();
                    @endphp

                    @foreach ($faqs as $index => $faq)
                        @php $position = $index + 1; @endphp
                        <div class="border-t {{ $loop->first ? 'border-[#1A1814]' : 'border-[#D9CFBC]' }} {{ $loop->last ? 'border-b border-[#D9CFBC]' : '' }}">
                            <button
                                type="button"
                                @click="openFaq = openFaq === {{ $position }} ? null : {{ $position }}"
                                :aria-expanded="openFaq === {{ $position }} ? 'true' : 'false'"
                                class="flex w-full items-baseline justify-between gap-4 py-5 text-left transition hover:text-[#1A1814]"
                            >
                                <div class="flex items-baseline gap-4">
                                    <span class="font-mono text-[11px] tracking-[0.14em] text-[#6E665C]" aria-hidden="true">
                                        {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814] sm:text-xl">
                                        {{ $faq['q'] }}
                                    </span>
                                </div>
                                <svg
                                    class="mt-1 size-5 shrink-0 text-[#C4623A] transition-transform duration-200"
                                    :class="{ 'rotate-45': openFaq === {{ $position }} }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <div x-show="openFaq === {{ $position }}" x-collapse class="overflow-hidden">
                                <p class="mb-6 max-w-prose pl-10 text-sm leading-relaxed text-[#3D3833]">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- More tools strip --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <a
                href="{{ route('tools.index') }}"
                class="group flex flex-col gap-4 border-t border-[#1A1814] pt-8 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Explore More Tools</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.01em] text-[#1A1814] transition-colors group-hover:text-[#C4623A]">
                        View All Tools →
                    </h2>
                </div>
                <span class="font-mono text-[11px] uppercase tracking-[0.16em] text-[#6E665C]">
                    Calculators · Trackers · Planners
                </span>
            </a>
        </section>
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
