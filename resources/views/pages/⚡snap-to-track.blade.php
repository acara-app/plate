<?php

declare(strict_types=1);

use App\Actions\FailPhotoAnalysis;
use App\Actions\PrepareSnapToTrackUpgrade;
use App\Actions\RetryPhotoAnalysis;
use App\Actions\StartPhotoAnalysis;
use App\Contracts\Billing\ManagesPhotoPreview;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\SnapFunnelStage;
use App\Models\AnalysisDraft;
use App\Models\PhotoAnalysisRequest;
use App\Services\Billing\SnapFunnel;
use App\Utilities\Base64Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Async;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
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

    public bool $keepPhoto = false;

    public bool $loading = false;

    public ?string $error = null;

    #[\Livewire\Attributes\Locked]
    public ?string $analysisRequestId = null;

    #[\Livewire\Attributes\Locked]
    public bool $hasRecoverableResult = false;

    #[\Livewire\Attributes\Locked]
    public bool $canRetryAnalysis = false;

    protected FailPhotoAnalysis $failAnalysis;

    protected SnapFunnel $funnel;

    public function boot(FailPhotoAnalysis $failAnalysis, SnapFunnel $funnel): void
    {
        $this->failAnalysis = $failAnalysis;
        $this->funnel = $funnel;
    }

    public function mount(): void
    {
        $this->restoreLatestAnalysis();
        if (auth()->guest() && ! session()->has('snap_to_track.intent')) {
            session()->put('snap_to_track.intent', 'scan');
            if (! session()->has('url.intended')) {
                session()->put('url.intended', route('snap-to-track.index', absolute: false));
            }
        }
    }

    public function startPreview(ManagesPhotoPreview $preview): void
    {
        abort_unless($this->photoAllowance->preview?->status === 'available', 403);
        session()->put('snap_to_track.intent', 'preview');
        session()->put('url.intended', route('snap-to-track', absolute: false));
        if (! auth()->check()) {
            $this->redirect(route('register'));

            return;
        }
        if (! auth()->user()->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));

            return;
        }
        $preview->activate(auth()->user(), $this->analysisGuestId());
        session()->forget(['snap_to_track.preview_draft', 'snap_to_track.intent']);
        unset($this->photoAllowance);
    }

    #[\Livewire\Attributes\Computed]
    public function photoAllowance(): \App\Data\Billing\PhotoEntitlement
    {
        return resolve(\App\Contracts\Billing\ManagesPhotoAnalyses::class)->entitlement(auth()->user(), $this->analysisGuestId());
    }

    public function upgrade(PrepareSnapToTrackUpgrade $prepareUpgrade, string $placement = 'wall'): void
    {
        $placement = in_array($placement, ['wall', 'preview_upload'], true) ? $placement : 'wall';
        $this->funnel->recordOffer($placement, $placement, $this->photoAllowance->offer?->name, clicked: true);

        $this->redirect($prepareUpgrade->handle($this->photoAllowance->offer));
    }

    #[Async]
    #[Renderless]
    public function offerViewed(string $placement): void
    {
        $allowance = $this->photoAllowance;

        if (! in_array($placement, ['wall', 'preview_upload'], true) || ! $allowance->canUpgrade || $allowance->offer === null || ! ($allowance->exhausted || $allowance->preview !== null)) {
            return;
        }

        $this->funnel->recordOffer($placement, $placement, $allowance->offer->name);
    }

    public function _startUpload($name, $fileInfo, $isMultiple): void
    {
        if ($name === 'photo') {
            abort_if($this->loading, 409, 'Your photo is still being analyzed.');
            $this->hasRecoverableResult = false;
            $this->canRetryAnalysis = false;
            $this->analysisRequestId = (string) \Illuminate\Support\Str::uuid();
            $this->funnel->record(SnapFunnelStage::UploadSelected, $this->analysisRequestId);
            $rejection = 'verification';
            try {
                $this->validateUploadChallenge();
                $rejection = 'rate_limited';
                $this->hitUploadRateLimit();
            } catch (ValidationException $e) {
                $this->funnel->record(SnapFunnelStage::UploadRejected, $this->analysisRequestId, ['reason' => $rejection]);
                $this->dispatch('upload:errored', name: $name)->self();

                throw $e;
            }
            $this->rememberVerifiedUploadChallenge();
        }

        $this->startLivewireUpload($name, $fileInfo, $isMultiple);
    }

    public function updatedPhoto(): void
    {
        try {
            $this->validateOnly('photo', $this->photoRules(), $this->photoMessages());
        } catch (ValidationException $e) {
            $this->funnel->record(SnapFunnelStage::UploadRejected, (string) $this->analysisRequestId, ['reason' => 'photo']);
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);

            throw $e;
        }
    }

    public function analyze(StartPhotoAnalysis $start): void
    {
        $existing = $this->ownedAnalyses()->where('request_id', $this->analysisRequestId)->first();
        if ($existing !== null) {
            $this->showAnalysis($existing, redirect: true);

            return;
        }

        $this->error = null;
        if ($this->photoAllowance->exhausted) {
            $this->error = 'Your photo allowance is used up. Choose a plan below to continue.';

            return;
        }

        if (RateLimiter::tooManyAttempts($this->analysisRateLimitKey(), 5)) {
            $this->error = 'Too many attempts in a short time. Please try again later; this is separate from your scan allowance.';
            $this->funnel->record(SnapFunnelStage::UploadRejected, (string) $this->analysisRequestId, ['reason' => 'rate_limited']);
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);

            return;
        }

        $rules = $this->photoRules();
        if (app()->environment(['production', 'testing'])) {
            $rules['turnstileToken'] = ['required', 'string'];
        }

        try {
            $this->validate($rules, $this->photoMessages());
            $this->validateVerifiedUploadChallenge();

            if (! $this->photo instanceof TemporaryUploadedFile) {
                return;
            }

            $imageContent = $this->photo->get();
            $base64 = base64_encode($imageContent ?? '');
            $image = Base64Image::decode($base64);
            if (! $image instanceof Base64Image) {
                throw ValidationException::withMessages(['photo' => $this->photoMessages()['photo.mimes']]);
            }

            RateLimiter::hit($this->analysisRateLimitKey(), 3600);
            $this->analysisRequestId ??= (string) Str::uuid();
            $analysis = $start->handle($this->analysisRequestId, $base64, $image->mimeType, new PhotoAnalysisContext(
                auth()->user(), $this->analysisGuestId(), $this->analysisRequestId,
                'public_snap_to_track', hash('sha256', $base64),
            ), keepPhoto: auth()->guest() && $this->keepPhoto);
            $this->funnel->record(SnapFunnelStage::AnalysisRequested, $analysis->request_id);
            $this->showAnalysis($analysis);
        } catch (ValidationException $e) {
            $this->funnel->record(SnapFunnelStage::UploadRejected, (string) $this->analysisRequestId, [
                'reason' => array_key_exists('turnstileToken', $e->errors()) ? 'verification' : 'photo',
            ]);

            throw $e;
        } catch (Throwable $e) {
            $this->funnel->record(SnapFunnelStage::AnalysisFailed, (string) $this->analysisRequestId, ['failure' => 'start_failed']);
            $this->error = 'We could not start the analysis. Please try again.';
            $saved = $this->ownedAnalyses()->where('request_id', $this->analysisRequestId)->first();
            if ($saved !== null) {
                $this->showAnalysis($saved);
            }
            report($e);
        } finally {
            $this->deleteTemporaryPhoto(resetUploadChallenge: true);
        }
    }

    public function refreshAnalysis(): void
    {
        $analysis = $this->ownedAnalyses()->where('request_id', $this->analysisRequestId)->first();
        if ($analysis === null) {
            $this->loading = false;
            $this->hasRecoverableResult = false;
            $this->canRetryAnalysis = false;
            $this->error = 'This analysis has expired. Please upload a photo to start again.';

            return;
        }

        $this->showAnalysis($analysis, redirect: true);
    }

    public function retryAnalysis(RetryPhotoAnalysis $retryAnalysis): void
    {
        $previous = $this->ownedAnalyses()->where('request_id', $this->analysisRequestId)->first();
        if ($previous?->retry_request_id !== null) {
            $retry = $this->ownedAnalyses()->where('request_id', $previous->retry_request_id)->first();
            if ($retry !== null) {
                $this->showAnalysis($retry, redirect: true);

                return;
            }
        }

        if ($previous === null || ! $previous->canRetry()) {
            $this->refreshAnalysis();

            return;
        }

        if (RateLimiter::tooManyAttempts($this->analysisRateLimitKey(), 5)) {
            $this->error = 'Too many attempts in a short time. Please try again later; this is separate from your scan allowance.';

            return;
        }

        $this->error = null;
        try {
            RateLimiter::hit($this->analysisRateLimitKey(), 3600);
            $analysis = $retryAnalysis->handle($previous, auth()->user(), $this->analysisGuestId());
            $this->funnel->record(SnapFunnelStage::AnalysisRequested, $analysis->request_id, ['retry' => true]);
            $this->showAnalysis($analysis);
        } catch (Throwable $e) {
            $this->error = 'We could not restart the analysis. Please try again.';
            $this->restoreLatestAnalysis();
            report($e);
        }
    }

    private function restoreLatestAnalysis(): void
    {
        $analysis = $this->ownedAnalyses()->latest('id')->first();
        if ($analysis !== null) {
            $this->showAnalysis($analysis);
        }
    }

    private function showAnalysis(PhotoAnalysisRequest $analysis, bool $redirect = false): void
    {
        $failureCode = $analysis->staleFailureCode();
        if ($failureCode !== null) {
            $this->failAnalysis->handle($analysis, $failureCode, onlyIfStale: true);
        }

        $this->analysisRequestId = $analysis->request_id;
        $this->loading = $analysis->isPending();
        $this->hasRecoverableResult = $analysis->draft_token !== null && ! $analysis->isExpired()
            && AnalysisDraft::query()->where('token_hash', AnalysisDraft::hashToken($analysis->draft_token))
                ->whereNull('consumed_at')->where('expires_at', '>', now())->exists();
        $this->canRetryAnalysis = $analysis->canRetry();
        unset($this->photoAllowance);

        if ($analysis->hasFailed()) {
            $this->error = $analysis->failureMessage();
        }

        if ($redirect && $this->hasRecoverableResult) {
            if ($analysis->user_id !== null) {
                $this->redirect(route('snap-to-track.save', ['draft' => $analysis->draft_token]));

                return;
            }

            session()->put('snap_to_track.result', ['draft' => $analysis->draft_token, 'request_id' => $analysis->request_id]);
            $this->redirect(route('snap-to-track.result'));
        }
    }

    private function analysisGuestId(): string
    {
        $guestId = PhotoAnalysisContext::guestId(request());
        if ($guestId !== null) {
            return $guestId;
        }

        $guestId = session()->get('snap_to_track.analysis_guest_id');
        if (! is_string($guestId)) {
            $guestId = (string) Str::uuid();
            session()->put('snap_to_track.analysis_guest_id', $guestId);
        }

        return $guestId;
    }

    /** @return Builder<PhotoAnalysisRequest> */
    private function ownedAnalyses(): Builder
    {
        $guestId = $this->analysisGuestId();
        $query = PhotoAnalysisRequest::query()->where('expires_at', '>', now())
            ->where(function (Builder $query) use ($guestId): void {
                $query->whereRaw('1 = 0');
                if (auth()->id() !== null) {
                    $query->orWhere('user_id', auth()->id());
                }
                if ($guestId !== null) {
                    $query->orWhere(function (Builder $guest) use ($guestId): void {
                        $guest->whereNull('user_id')->where('guest_hash', hash('sha256', $guestId));
                    });
                }
            });

        return $query->metadata();
    }

    public function clearPhoto(): void
    {
        if ($this->loading || $this->photoAllowance->exhausted) {
            return;
        }
        $this->deleteTemporaryPhoto(resetUploadChallenge: true);
        $this->error = null;

        $this->redirect(route('snap-to-track'));
    }

    public function limitReachedGate(): ?string
    {
        if ($this->loading) {
            return null;
        }

        if ($this->photoAllowance->exhausted) {
            return 'daily';
        }

        if (RateLimiter::tooManyAttempts($this->uploadRateLimitKey(), 5)) {
            return 'upload';
        }

        if (RateLimiter::tooManyAttempts($this->analysisRateLimitKey(), 5)) {
            return 'analysis';
        }

        return null;
    }

    public function continueInApp(string $intent): void
    {
        if (! in_array($intent, ['register', 'login'], true) || auth()->check()) {
            return;
        }

        $previewAvailable = $this->photoAllowance->preview?->status === 'available';
        session()->put('url.intended', route($previewAvailable ? 'snap-to-track' : 'snap-to-track.index', absolute: false));
        session()->put('snap_to_track.auth_path', $intent);
        session()->put('snap_to_track.intent', $previewAvailable ? 'preview' : 'scan');

        $this->redirect(route($intent));
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    private function photoRules(): array
    {
        return ['photo' => config()->array('livewire.temporary_file_upload.rules')];
    }

    /**
     * @return array<string, string>
     */
    private function photoMessages(): array
    {
        return [
            'photo.mimes' => 'We can’t read this photo format. Please use a JPG or PNG photo.',
            'photo.max' => 'This photo is over 10MB. Please choose a smaller one.',
        ];
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
                'photo' => 'Too many uploads in a short time. Please try again later; your unused scan allowance is preserved.',
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

    <div class="px-4 py-5 md:py-12">
    @php
        $photoAllowance = $this->photoAllowance;
    @endphp
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
        <section class="mx-auto mt-6 max-w-3xl lg:px-8">
            @if ($error)
                <div role="alert" class="mb-4 flex items-start gap-3 border border-[#B5482E] bg-[#B5482E]/5 p-4 text-sm leading-relaxed text-[#893E20]">
                    <svg class="mt-0.5 size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.84L2.82 17a2 2 0 001.74 3h14.88a2 2 0 001.74-3L13.7 3.84a2 2 0 00-3.4 0z" />
                    </svg>
                    <p>{{ $error }}</p>
                </div>
            @endif
            @if ($loading)
                <div
                    wire:poll.2s="refreshAnalysis"
                    x-data="{
                        photo: null,
                        startedAt: Date.now(),
                        now: Date.now(),
                        scanning: false,
                        ticker: null,
                        init() {
                            this.photo = window.acaraRecallSnapPhoto?.(@js($analysisRequestId)) ?? null;
                            setTimeout(() => this.scanning = true, 50);
                            this.$nextTick(() => {
                                if (this.$el.getBoundingClientRect().top < 0) {
                                    this.$el.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                                }
                            });
                            this.ticker = setInterval(() => {
                                this.now = Date.now();
                                this.scanning = ! this.scanning;
                                if (! this.photo && this.seconds < 6) {
                                    this.photo = window.acaraRecallSnapPhoto?.(@js($analysisRequestId)) ?? null;
                                }
                            }, 1800);
                        },
                        destroy() {
                            clearInterval(this.ticker);
                        },
                        get seconds() {
                            return (this.now - this.startedAt) / 1000;
                        },
                        get step() {
                            return this.seconds < 3 ? 1 : (this.seconds < 7 ? 2 : 3);
                        },
                    }"
                    class="mb-6 scroll-mt-24 border border-[#D9CFBC] bg-[#EBE2D0] p-6 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-300 sm:p-8"
                >
                    <div class="flex items-center justify-between gap-4 pb-5">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Step 2 · Analyzing</p>
                        <span class="inline-flex items-center gap-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                            <span class="size-1.5 rounded-full bg-[#C4623A]" aria-hidden="true"></span>
                            Live
                        </span>
                    </div>

                    <div class="grid gap-6 border-t border-[#D9CFBC] pt-6 sm:grid-cols-[13rem_1fr] sm:items-center">
                        <div class="relative aspect-[16/10] overflow-hidden border border-[#D9CFBC] bg-[#F2EBDD] sm:aspect-square">
                            <img x-show="photo" x-bind:src="photo" alt="Your meal photo" class="size-full object-cover" style="display: none;">
                            <div x-show="! photo" class="flex size-full items-center justify-center text-[#6E665C]">
                                <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div x-show="photo" class="pointer-events-none absolute inset-0 bg-[#1A1814]/10" style="display: none;" aria-hidden="true">
                                @foreach (['left-3 top-3 border-l-2 border-t-2', 'right-3 top-3 border-r-2 border-t-2', 'bottom-3 left-3 border-b-2 border-l-2', 'bottom-3 right-3 border-b-2 border-r-2'] as $corner)
                                    <span wire:key="scan-corner-{{ $loop->index }}" class="absolute size-5 border-[#F2EBDD] {{ $corner }}"></span>
                                @endforeach
                            </div>
                            <div
                                x-bind:class="scanning ? 'translate-y-[calc(100%_-_2px)]' : 'translate-y-0'"
                                class="pointer-events-none absolute inset-0 transition-transform duration-[1800ms] ease-in-out motion-reduce:hidden"
                                aria-hidden="true"
                            >
                                <div class="h-0.5 w-full bg-[#C4623A] shadow-[0_0_18px_4px_rgba(196,98,58,0.55)]"></div>
                            </div>
                        </div>

                        <div>
                            <div role="status">
                                <h2 class="text-2xl font-bold leading-tight tracking-[-0.02em] text-[#1A1814]">Analyzing your meal</h2>
                                <p
                                    x-text="seconds < 15 ? 'Most meals take 5–15 seconds.' : (seconds < 45 ? 'Taking a little longer than usual. Busy plates need a few extra seconds.' : 'Still working on it. You can leave this page — come back in this browser and your result will be waiting.')"
                                    class="mt-2 text-sm leading-relaxed text-[#3D3833]"
                                >Most meals take 5–15 seconds.</p>
                            </div>
                            <ol class="mt-5 grid gap-3">
                                @foreach (['Photo received', 'Spotting each food', 'Estimating portion sizes', 'Calculating calories & macros'] as $stepLabel)
                                    <li wire:key="analysis-step-{{ $loop->index }}" x-bind:aria-current="step === {{ $loop->index }} && 'step'" class="flex items-center gap-3 text-sm">
                                        <span
                                            x-bind:class="step > {{ $loop->index }} ? 'border-[#1A1814] bg-[#1A1814] text-[#F2EBDD]' : (step === {{ $loop->index }} ? 'border-[#C4623A] bg-[#C4623A] text-[#F2EBDD]' : 'border-[#D9CFBC]')"
                                            class="flex size-5 shrink-0 items-center justify-center border transition-colors duration-300"
                                            aria-hidden="true"
                                        >
                                            <svg x-show="step > {{ $loop->index }}" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg x-show="step === {{ $loop->index }}" class="size-3 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" style="display: none;">
                                                <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                                                <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                            </svg>
                                        </span>
                                        <span x-bind:class="step === {{ $loop->index }} ? 'font-semibold text-[#1A1814]' : 'text-[#3D3833]'">
                                            <span x-show="step > {{ $loop->index }}" class="sr-only" style="display: none;">Done: </span>{{ $stepLabel }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                    <p class="mt-6 text-balance border-t border-[#D9CFBC] pt-5 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                        Safe to refresh · Won’t use another scan
                    </p>
                </div>
            @elseif ($hasRecoverableResult)
                <div role="status" class="mb-6 flex flex-col gap-5 border border-[#D9CFBC] bg-[#EBE2D0] p-6 motion-safe:animate-in motion-safe:fade-in motion-safe:duration-300 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div class="flex items-start gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center bg-[#1A1814] text-[#F2EBDD]" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold leading-tight tracking-[-0.01em] text-[#1A1814]">Your last scan is ready</h2>
                            <p class="mt-1 text-sm leading-relaxed text-[#3D3833]">Opening it won’t use another scan.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="refreshAnalysis"
                        wire:loading.attr="disabled"
                        wire:target="refreshAnalysis"
                        class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 border border-[#1A1814] bg-[#1A1814] px-6 text-sm font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1A1814] disabled:cursor-wait disabled:opacity-60 sm:w-auto"
                    >
                        View result
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </button>
                </div>
            @elseif ($canRetryAnalysis)
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
                    <button
                        type="button"
                        wire:click="retryAnalysis"
                        wire:loading.attr="disabled"
                        wire:target="retryAnalysis"
                        class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 border border-[#1A1814] bg-[#1A1814] px-6 text-sm font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1A1814] disabled:cursor-wait disabled:opacity-60 sm:w-auto"
                    >
                        <svg wire:loading.remove wire:target="retryAnalysis" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <svg wire:loading wire:target="retryAnalysis" class="size-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Try this photo again
                    </button>
                    <p class="text-sm leading-relaxed text-[#3D3833]">No need to upload it again. Your photo stays available for an hour after upload.</p>
                </div>
            @endif
            @php
                $limitGate = $this->limitReachedGate();
            @endphp
            @if ($limitGate !== null && ! $photoAllowance->enabled)
                {{-- Limit recovery (dark inverse) --}}
                <article
                    x-data
                    x-init="window.acaraTrack?.('snap_to_track_limit_reached', { source: 'public_snap_to_track', gate: @js($limitGate), authenticated: @js(auth()->check()) })"
                    class="mb-6 border border-[#1A1814] bg-[#1A1814] p-6 sm:p-8 text-[#F2EBDD]"
                >
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Free scans used</p>
                    <h3 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em]">You're on a roll.</h3>
                    @auth
                        <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">
                            You've maxed the public scanner. Your account has its own — pick up right where you left off.
                        </p>
                        <a
                            href="{{ route('snap-to-track.index') }}"
                            class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                        >
                            Open Snap to Track
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    @else
                        <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">
                            That's this hour's free scans from this network. Create a free account and keep analyzing in the app — your results become meals you can save.
                        </p>
                        <button
                            type="button"
                            wire:click="continueInApp('register')"
                            x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { auth_path: 'register' })"
                            class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                        >
                            Keep analyzing — free
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>
                        <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-[#F2EBDD]/70">
                            Already a member?
                            <button
                                type="button"
                                wire:click="continueInApp('login')"
                                x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { auth_path: 'login' })"
                                class="underline decoration-[#C4623A] underline-offset-4 transition hover:text-[#F2EBDD]"
                            >Log in</button>
                        </p>
                    @endauth
                </article>
            @endif
            @if (! $loading && $photoAllowance->exhausted())
                <x-snap-conversion-offer :allowance="$photoAllowance" placement="wall" />
            @endif
            @unless ($loading || $photoAllowance->exhausted())
            <form wire:submit="analyze" class="space-y-6">
                <div
                    x-data="{
                        uploading: false,
                        uploadFailed: false,
                        progress: 0,
                        fileName: '',
                        pickPhoto(fromCamera) {
                            const input = this.$refs.photoUpload;
                            fromCamera ? input.setAttribute('capture', 'environment') : input.removeAttribute('capture');
                            input.click();
                        },
                        async sendPhoto(input) {
                            const picked = input.files[0];
                            input.value = '';
                            if (! picked) {
                                return;
                            }
                            this.fileName = picked.name;
                            this.uploading = true;
                            this.uploadFailed = false;
                            this.progress = 0;
                            window.acaraTrack?.('snap_to_track_upload_selected', { source: 'public_snap_to_track' });
                            window.acaraKeepSnapPhoto?.(picked);
                            const photo = (await window.acaraPrepareSnapPhoto?.(picked)) ?? picked;
                            this.$wire.$upload(
                                'photo',
                                photo,
                                () => { this.uploading = false; this.progress = 0; },
                                () => { this.uploading = false; this.uploadFailed = true; this.progress = 0; },
                                (event) => { this.progress = event.detail.progress; },
                                () => { this.uploading = false; this.progress = 0; },
                            );
                        },
                    }"
                    x-bind:aria-busy="uploading"
                    class="border border-[#D9CFBC] bg-[#EBE2D0] p-6 sm:p-8"
                >
                    <div class="flex items-center justify-between gap-4 pb-5">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                            @if ($photo)
                                Step 2 · Analyze
                            @else
                                <span x-show="! uploading">Step 1 · Upload</span>
                                <span x-show="uploading" style="display: none;">Step 1 · Uploading</span>
                            @endif
                        </p>
                        <span class="inline-flex items-center gap-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                            <span class="size-1.5 rounded-full bg-[#C4623A]" aria-hidden="true"></span>
                            Live
                        </span>
                    </div>

                    @if ($photoAllowance->enabled && $photoAllowance->mode === 'trial')
                        <p id="scan-terms" class="mb-4 text-center text-sm font-semibold text-[#3D3833]">{!! e(strtr(__('common.photo_plans.free.terms_'.($photoAllowance->limit === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $photoAllowance->limit])) !!}</p>
                        @if ($photoAllowance->used > 0)
                            <p class="mb-4 text-center text-sm">{{ $photoAllowance->remaining }} free scan remaining</p>
                        @endif
                    @elseif ($photoAllowance->preview?->status === 'active')
                        <p class="mb-4 text-sm">Your daily preview scan is ready. Preview ends <time x-data x-text="new Date(@js($photoAllowance->preview->expiresAt)).toLocaleString()">{{ $photoAllowance->preview->expiresAt }}</time>.</p>
                    @endif
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
                                accept="image/*"
                                class="hidden"
                                id="photo-upload"
                                x-ref="photoUpload"
                                x-on:change="sendPhoto($event.target)"
                                @disabled($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))
                            >
                            <button
                                type="button"
                                aria-label="Take a food photo or upload an image"
                                x-on:click="pickPhoto(false)"
                                x-bind:disabled="uploading || @js($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))"
                                class="group flex min-h-44 w-full cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1A1814] disabled:cursor-not-allowed flex-col items-center justify-center border-2 border-dashed border-[#D9CFBC] bg-[#F2EBDD] p-8 text-center transition hover:border-[#1A1814] hover:bg-[#F2EBDD]/70"
                            >
                                <div x-show="! uploading && ! uploadFailed" class="flex flex-col items-center">
                                    <div class="mb-5 flex size-14 items-center justify-center border border-[#1A1814] bg-[#F2EBDD] text-[#1A1814] transition group-hover:bg-[#1A1814] group-hover:text-[#F2EBDD]">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-lg tracking-[-0.01em] text-[#1A1814]">Tap to take photo or upload</span>
                                    <span class="mt-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">JPG, PNG up to 10MB</span>
                                </div>

                                <div
                                    x-show="uploading"
                                    style="display: none;"
                                    class="flex w-full max-w-sm flex-col items-center"
                                >
                                    <div class="mb-5 flex size-14 items-center justify-center border border-[#C4623A] bg-[#C4623A] text-[#F2EBDD]">
                                        <svg class="size-6 motion-safe:animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 15v3a2 2 0 002 2h10a2 2 0 002-2v-3" />
                                        </svg>
                                    </div>
                                    <span
                                        x-text="progress < 100 ? 'Uploading photo · ' + progress + '%' : 'Preparing preview…'"
                                        class="font-bold text-lg tracking-[-0.01em] text-[#1A1814]"
                                    >Uploading photo…</span>
                                    <span
                                        x-text="progress < 100 ? 'Uploading photo. Please wait.' : 'Upload complete. Preparing preview.'"
                                        class="sr-only"
                                        role="status"
                                        aria-live="polite"
                                    ></span>
                                    <span x-show="fileName" x-text="fileName" class="mt-2 max-w-full truncate font-mono text-[10px] uppercase tracking-[0.14em] text-[#6E665C]"></span>
                                    <div
                                        role="progressbar"
                                        aria-label="Photo upload progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        x-bind:aria-valuenow="progress"
                                        class="mt-5 h-1.5 w-full overflow-hidden bg-[#D9CFBC]"
                                    >
                                        <div
                                            x-bind:style="'width: ' + progress + '%'"
                                            class="h-full bg-[#C4623A] transition-[width] duration-200 motion-reduce:transition-none"
                                        ></div>
                                    </div>
                                    <span class="mt-3 text-sm leading-relaxed text-[#3D3833]">Keep this tab open. Your preview is next.</span>
                                </div>

                                <div x-show="uploadFailed" style="display: none;" class="flex flex-col items-center" role="alert">
                                    <div class="mb-5 flex size-14 items-center justify-center border border-[#B5482E] bg-[#B5482E]/5 text-[#B5482E]">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.84L2.82 17a2 2 0 001.74 3h14.88a2 2 0 001.74-3L13.7 3.84a2 2 0 00-3.4 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-lg tracking-[-0.01em] text-[#1A1814]">Upload didn’t finish</span>
                                    <span class="mt-2 text-sm leading-relaxed text-[#3D3833]">Tap here and choose the photo again to retry.</span>
                                </div>
                            </button>
                            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    x-on:click="pickPhoto(true)"
                                    x-bind:disabled="uploading || @js($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 border border-[#1A1814] bg-[#1A1814] px-4 text-sm font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1A1814] disabled:cursor-not-allowed disabled:opacity-60 sm:flex-1"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
                                        <circle cx="12" cy="13" r="3" />
                                    </svg>
                                    Take photo
                                </button>
                                <button
                                    type="button"
                                    x-on:click="pickPhoto(false)"
                                    x-bind:disabled="uploading || @js($loading || (App::environment(['production', 'testing']) && blank($turnstileToken)))"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 border border-[#1A1814] bg-transparent px-4 text-sm font-semibold text-[#1A1814] transition hover:bg-[#1A1814] hover:text-[#F2EBDD] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1A1814] disabled:cursor-not-allowed disabled:opacity-60 sm:flex-1"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M16 5h6" />
                                        <path d="M19 2v6" />
                                        <path d="M21 11.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7.5" />
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                                        <circle cx="9" cy="9" r="2" />
                                    </svg>
                                    Choose photo
                                </button>
                            </div>
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
                                    class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C] transition hover:text-[#B5482E]"
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

                        @guest
                            <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                                <label class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-[#3D3833]">
                                    <input
                                        type="checkbox"
                                        wire:model="keepPhoto"
                                        class="mt-1 size-4 shrink-0 cursor-pointer accent-[#1A1814]"
                                        @disabled($loading)
                                    >
                                    <span>
                                        Keep my photo to help improve food recognition (optional).
                                        <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-[#1A1814]">Privacy Policy</a>
                                    </span>
                                </label>
                            </div>
                        @endguest

                        <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                            <button
                                type="submit"
                                x-on:click="window.acaraTagSnapPhoto?.($wire.analysisRequestId)"
                                data-umami-event="snap_to_track_analyze_click"
                                data-umami-event-location="main_form"
                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#1A1814] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus:outline-none focus:ring-2 focus:ring-[#1A1814] focus:ring-offset-2 focus:ring-offset-[#EBE2D0] disabled:cursor-wait disabled:bg-[#1A1814] disabled:text-[#F2EBDD]"
                                @disabled($loading)
                            >
                                <span wire:loading.remove wire:target="analyze" class="inline-flex items-center gap-2">
                                    Analyze Food
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </span>
                                <span wire:loading.inline-flex wire:target="analyze" class="items-center gap-2">
                                    <svg class="size-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
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

                </div>

                {{-- Authority footer --}}
                <div class="flex flex-col gap-1 px-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                    <p><strong class="font-bold text-[#3D3833]">Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.</p>
                    <p>AI vision analysis · Informed by standard nutrition references</p>
                    <p><time datetime="{{ now()->toDateString() }}">Last updated: {{ now()->format('F Y') }}</time></p>
                </div>
            </form>
            @if ($photoAllowance->preview?->status === 'active')
                <x-snap-conversion-offer :allowance="$photoAllowance" placement="preview_upload" />
            @endif
            @endunless
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
                        Our vision model recognizes individual ingredients, estimates portion sizes, and works out nutrition from standard references like USDA FoodData Central. These are AI estimates, not database lookups.
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
                <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                    Calculators · Trackers · Planners
                </span>
            </a>
        </section>
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
