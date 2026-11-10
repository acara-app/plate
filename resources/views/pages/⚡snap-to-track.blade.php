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
#[Layout('layouts.mini-app', ['bodyClass' => 'bg-black', 'viewport' => 'width=device-width, initial-scale=1, viewport-fit=cover', 'metaDescription' => 'Free photo calorie counter and AI food analyzer: snap a meal to estimate calories, protein, carbs, and fat per ingredient. No signup required to try it.', 'metaKeywords' => 'photo calorie counter, ai food analyzer, photo calorie counter app free, meal scanner free, food photo calorie counter, snap to track calories, AI food recognition, meal photo analyzer, instant macro breakdown, calorie tracking app, food image analysis, nutrition scanner, AI calorie counter from photo, photo macro tracker'])]
#[Title('AI Food Analyzer | Free Photo Calorie Counter & Meal Scanner')]
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
            ), keepPhoto: $this->keepPhoto);
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

<div class="bg-black text-white [-webkit-tap-highlight-color:transparent] [-webkit-text-size-adjust:100%]">
    @php
        $photoAllowance = $this->photoAllowance;
        $limitGate = $this->limitReachedGate();
        $needsVerification = App::environment(['production', 'testing']);
        $uploadsLocked = $loading || ($needsVerification && blank($turnstileToken));
        $gutter = 'pr-[max(1.25rem,env(safe-area-inset-right))] pl-[max(1.25rem,env(safe-area-inset-left))] sm:pr-[max(2rem,env(safe-area-inset-right))] sm:pl-[max(2rem,env(safe-area-inset-left))]';
        $press = 'touch-manipulation select-none transition duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none';
        $ring = 'outline-none focus-visible:ring-[3px] focus-visible:ring-white/70';
        $ringOnLight = 'outline-none focus-visible:ring-[3px] focus-visible:ring-neutral-900/40';
        $pill = 'inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full px-6 text-center text-base font-semibold '.$press.' '.$ring;
        $frameBox = 'mx-auto aspect-square w-full max-w-[max(15rem,calc(100svh_-_19rem))] lg:max-w-none';
        $frame = 'relative overflow-hidden rounded-[2rem] bg-neutral-900 '.$frameBox;
        $corners = [
            'top-0 left-0 rounded-tl-2xl border-t-4 border-l-4',
            'top-0 right-0 rounded-tr-2xl border-t-4 border-r-4',
            'bottom-0 left-0 rounded-bl-2xl border-b-4 border-l-4',
            'right-0 bottom-0 rounded-br-2xl border-r-4 border-b-4',
        ];
    @endphp

    <header class="{{ $gutter }} pt-[env(safe-area-inset-top)]">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 sm:h-20">
            <a
                href="{{ route('home') }}"
                aria-label="Acara Plate home"
                class="-ml-2 inline-flex min-h-11 items-center gap-2 rounded-full px-2 {{ $press }} {{ $ring }}"
            >
                <span aria-hidden="true" class="text-xl leading-none">🍓</span>
                <span class="text-[17px] font-bold tracking-tight">Acara Plate</span>
            </a>
            <nav aria-label="Site" class="-mr-3 flex items-center">
                <a
                    href="{{ route('tools.index') }}"
                    class="inline-flex min-h-11 items-center rounded-full px-3 text-[15px] font-medium text-white/70 hover:text-white {{ $press }} {{ $ring }}"
                >
                    Tools
                </a>
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex min-h-11 items-center rounded-full px-3 text-[15px] font-medium text-white/70 hover:text-white {{ $press }} {{ $ring }}"
                    >
                        Dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center rounded-full px-3 text-[15px] font-medium text-white/70 hover:text-white {{ $press }} {{ $ring }}"
                    >
                        Log in
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="{{ $gutter }} pt-1 pb-12 sm:pt-8 sm:pb-20 lg:pt-12 lg:pb-28">
        <div class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-[minmax(0,1fr)_28rem] lg:items-center lg:gap-20">
            <div class="mx-auto w-full max-w-md lg:mx-0 lg:max-w-none">
                <h1 class="text-[2.5rem] leading-none font-extrabold tracking-[-0.04em] text-balance sm:text-6xl lg:text-[5.75rem] lg:leading-[0.95]">
                    Snap to Track
                </h1>
                <p class="speakable-intro mt-3 text-[15px] leading-snug text-pretty text-white/70 sm:mt-5 sm:text-lg sm:leading-relaxed lg:max-w-md lg:text-xl">
                    Track calories &amp; macros instantly with AI. A&nbsp;free photo calorie counter for any meal — no signup required.
                </p>
            </div>

            <div class="mx-auto flex w-full max-w-md flex-col gap-5">
                @if ($error)
                    <div role="alert" class="flex items-start gap-3 rounded-3xl bg-red-500/15 p-4 text-[15px] leading-relaxed text-red-100">
                        <x-snap-result.icon name="triangle-alert" class="mt-0.5 size-5 shrink-0" />
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
                        class="flex scroll-mt-4 flex-col gap-6 motion-safe:animate-in motion-safe:fade-in motion-safe:duration-300"
                    >
                        <div class="{{ $frame }}">
                            <img x-show="photo" x-bind:src="photo" alt="Your meal photo" class="size-full object-contain" style="display: none;">
                            <div x-show="! photo" class="absolute inset-0" aria-hidden="true">
                                <span class="absolute inset-[14%] rounded-full bg-linear-to-br from-white/[0.17] to-white/[0.03] shadow-[0_24px_48px_rgba(0,0,0,0.55)]"></span>
                                <span class="absolute inset-[25%] rounded-full bg-linear-to-tl from-white/[0.1] to-black/25 inset-shadow-[0_2px_10px_rgba(0,0,0,0.45)]"></span>
                            </div>
                            <div class="pointer-events-none absolute inset-4" aria-hidden="true">
                                @foreach ($corners as $corner)
                                    <span wire:key="scan-corner-{{ $loop->index }}" class="absolute size-10 border-primary {{ $corner }}"></span>
                                @endforeach
                            </div>
                            <div
                                x-bind:class="scanning ? 'translate-y-[calc(100%_-_2px)]' : 'translate-y-0'"
                                class="pointer-events-none absolute inset-0 transition-transform duration-[1800ms] ease-in-out motion-reduce:hidden"
                                aria-hidden="true"
                            >
                                <div class="h-0.5 w-full bg-primary shadow-[0_0_18px_4px_rgba(16,185,129,0.55)]"></div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-5">
                            <div role="status">
                                <h2 class="text-2xl font-bold tracking-tight">Analyzing your meal</h2>
                                <p
                                    x-text="seconds < 15 ? 'Most meals take 5–15 seconds.' : (seconds < 45 ? 'Taking a little longer than usual. Busy plates need a few extra seconds.' : 'Still working on it. You can leave this page — come back in this browser and your result will be waiting.')"
                                    class="mt-1.5 text-[15px] leading-relaxed text-white/70"
                                >Most meals take 5–15 seconds.</p>
                            </div>
                            <ol class="grid gap-3.5">
                                @foreach (['Photo received', 'Spotting each food', 'Estimating portion sizes', 'Calculating calories & macros'] as $stepLabel)
                                    <li wire:key="analysis-step-{{ $loop->index }}" x-bind:aria-current="step === {{ $loop->index }} && 'step'" class="flex items-center gap-3 text-[15px]">
                                        <span
                                            x-bind:class="step > {{ $loop->index }} ? 'bg-primary text-emerald-950' : 'bg-white/10 text-primary'"
                                            class="flex size-6 shrink-0 items-center justify-center rounded-full transition-colors duration-300"
                                            aria-hidden="true"
                                        >
                                            <x-snap-result.icon name="check" x-show="step > {{ $loop->index }}" class="size-3.5 stroke-3" style="display: none;" />
                                            <x-snap-result.icon name="loader" x-show="step === {{ $loop->index }}" class="size-3.5 stroke-3 motion-safe:animate-spin" style="display: none;" />
                                        </span>
                                        <span x-bind:class="step === {{ $loop->index }} ? 'font-semibold text-white' : (step > {{ $loop->index }} ? 'text-white/70' : 'text-white/50')">
                                            <span x-show="step > {{ $loop->index }}" class="sr-only" style="display: none;">Done: </span>{{ $stepLabel }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="text-sm text-white/55">Safe to refresh. It won’t use another scan.</p>
                        </div>
                    </div>
                @elseif ($hasRecoverableResult)
                    <div role="status" class="flex flex-col gap-5 rounded-[2rem] bg-white/10 p-6 motion-safe:animate-in motion-safe:fade-in motion-safe:duration-300">
                        <div class="flex items-center gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary text-emerald-950" aria-hidden="true">
                                <x-snap-result.icon name="check" class="size-5 stroke-3" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight">Your last scan is ready</h2>
                                <p class="mt-0.5 text-[15px] leading-snug text-white/70">Opening it won’t use another scan.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="refreshAnalysis"
                            wire:loading.attr="disabled"
                            wire:target="refreshAnalysis"
                            class="{{ $pill }} bg-white text-black disabled:cursor-wait disabled:opacity-60"
                        >
                            View result
                        </button>
                    </div>
                @elseif ($canRetryAnalysis)
                    <div class="flex flex-col gap-3">
                        <button
                            type="button"
                            wire:click="retryAnalysis"
                            wire:loading.attr="disabled"
                            wire:target="retryAnalysis"
                            class="{{ $pill }} bg-white text-black disabled:cursor-wait disabled:opacity-60"
                        >
                            <x-snap-result.icon name="rotate-ccw" wire:loading.remove wire:target="retryAnalysis" class="size-4" />
                            <x-snap-result.icon name="loader" wire:loading wire:target="retryAnalysis" class="size-4 motion-safe:animate-spin" />
                            Try this photo again
                        </button>
                        <p class="text-center text-sm leading-relaxed text-white/65">No need to upload it again. Your photo stays available for an hour after upload.</p>
                    </div>
                @endif

                @if ($limitGate !== null && ! $photoAllowance->enabled)
                    <article
                        x-data
                        x-init="window.acaraTrack?.('snap_to_track_limit_reached', { source: 'public_snap_to_track', gate: @js($limitGate), authenticated: @js(auth()->check()) })"
                        class="rounded-[2rem] bg-white/10 p-6"
                    >
                        <p class="text-sm font-medium text-primary">Free scans used</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight">You're on a roll.</h2>
                        @auth
                            <p class="mt-2 text-[15px] leading-relaxed text-white/75">
                                You've maxed the public scanner. Your account has its own — pick up right where you left off.
                            </p>
                            <a href="{{ route('snap-to-track.index') }}" class="{{ $pill }} mt-5 bg-primary text-emerald-950">
                                Open Snap to Track
                            </a>
                        @else
                            <p class="mt-2 text-[15px] leading-relaxed text-white/75">
                                That's this hour's free scans from this network. Create a free account and keep analyzing in the app — your results become meals you can save.
                            </p>
                            <button
                                type="button"
                                wire:click="continueInApp('register')"
                                x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { auth_path: 'register' })"
                                class="{{ $pill }} mt-5 bg-primary text-emerald-950"
                            >
                                Keep analyzing — free
                            </button>
                            <p class="mt-2 text-center text-sm text-white/65">
                                Already a member?
                                <button
                                    type="button"
                                    wire:click="continueInApp('login')"
                                    x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { auth_path: 'login' })"
                                    class="min-h-11 rounded-full px-1 font-medium text-white underline underline-offset-4 {{ $ring }}"
                                >Log in</button>
                            </p>
                        @endauth
                    </article>
                @endif

                @if (! $loading && $photoAllowance->exhausted())
                    <x-snap-conversion-offer :allowance="$photoAllowance" placement="wall" />
                @endif

                @unless ($loading || $photoAllowance->exhausted())
                    <form wire:submit="analyze" class="flex flex-col gap-5">
                        <div
                            x-data="{
                                uploading: false,
                                uploadFailed: false,
                                progress: 0,
                                pickPhoto() {
                                    this.$refs.photoUpload.click();
                                },
                                sendPhoto(input) {
                                    const picked = input.files[0];
                                    input.value = '';
                                    this.sendFile(picked);
                                },
                                async sendFile(picked) {
                                    if (! picked || this.uploading) {
                                        return;
                                    }
                                    if (this.$refs.photoUpload?.disabled) {
                                        this.uploadFailed = true;
                                        return;
                                    }
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
                            x-on:snap-photo-picked="sendFile($event.detail.photo)"
                            class="flex flex-col gap-5"
                        >
                            <div>
                                <x-snap-scanner />

                                @if ($needsVerification)
                                    <div wire:key="snap-verification" class="flex justify-center [&_.cf-turnstile>*]:mb-4">
                                        <x-turnstile wire:model="turnstileToken" data-theme="dark" data-appearance="interaction-only" />
                                    </div>
                                @endif

                                @if (! $photo)
                                    <div class="relative">
                                        <div class="flex flex-col gap-5 {{ $needsVerification && blank($turnstileToken) ? 'opacity-50' : '' }}">
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                id="photo-upload"
                                                x-ref="photoUpload"
                                                x-on:change="sendPhoto($event.target)"
                                                @disabled($uploadsLocked)
                                            >
                                            <button
                                                type="button"
                                                aria-label="Take a food photo or upload an image"
                                                aria-haspopup="dialog"
                                                x-on:click="uploadFailed ? pickPhoto() : $dispatch('snap-scanner-open')"
                                                x-bind:disabled="uploading || @js($uploadsLocked)"
                                                class="{{ $frame }} group @container block cursor-pointer touch-manipulation select-none [-webkit-touch-callout:none] transition duration-150 ease-out active:scale-[0.985] disabled:cursor-not-allowed disabled:active:scale-100 motion-reduce:transition-none {{ $ring }}"
                                            >
                                                <span aria-hidden="true" class="absolute inset-[14%] rounded-full bg-linear-to-br from-white/[0.17] to-white/[0.03] shadow-[0_24px_48px_rgba(0,0,0,0.55)] transition duration-300 ease-out group-hover:from-white/[0.22] motion-reduce:transition-none @max-[18rem]:inset-[17%]"></span>
                                                <span aria-hidden="true" class="absolute inset-[25%] rounded-full bg-linear-to-tl from-white/[0.1] to-black/25 inset-shadow-[0_2px_10px_rgba(0,0,0,0.45)] @max-[18rem]:inset-[27%]"></span>
                                                <svg
                                                    x-show="uploading"
                                                    viewBox="0 0 100 100"
                                                    fill="none"
                                                    class="absolute top-[14%] left-[14%] size-[72%] -rotate-90 @max-[18rem]:top-[17%] @max-[18rem]:left-[17%] @max-[18rem]:size-[66%]"
                                                    style="display: none;"
                                                    aria-hidden="true"
                                                >
                                                    <circle
                                                        cx="50"
                                                        cy="50"
                                                        r="49"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        pathLength="100"
                                                        stroke-dasharray="100"
                                                        x-bind:stroke-dashoffset="100 - progress"
                                                        class="stroke-primary transition-[stroke-dashoffset] duration-200 motion-reduce:transition-none"
                                                    />
                                                </svg>
                                                <span aria-hidden="true" class="pointer-events-none absolute inset-4 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-110 motion-safe:duration-700">
                                                    @foreach ($corners as $corner)
                                                        <span wire:key="frame-corner-{{ $loop->index }}" class="absolute size-10 border-primary {{ $corner }}"></span>
                                                    @endforeach
                                                </span>

                                                <span x-show="! uploading && ! uploadFailed" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center">
                                                    <x-snap-result.icon name="camera" class="size-7" />
                                                    <span class="max-w-36 text-[15px] leading-tight font-semibold text-balance">Tap to take photo or upload</span>
                                                </span>
                                                <span x-show="! uploading && ! uploadFailed" class="absolute inset-x-0 bottom-5 text-center text-xs text-white/55">JPG, PNG up to 10MB</span>

                                                <span x-show="uploading" style="display: none;" class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                                    <span x-text="progress + '%'" class="text-4xl font-bold tracking-tight tabular-nums">0%</span>
                                                    <span x-text="progress < 100 ? 'Uploading photo' : 'Preparing preview…'" class="mt-1 text-sm font-medium text-white/70">Uploading photo</span>
                                                    <span
                                                        x-text="progress < 100 ? 'Uploading photo. Please wait.' : 'Upload complete. Preparing preview.'"
                                                        class="sr-only"
                                                        role="status"
                                                        aria-live="polite"
                                                    ></span>
                                                    <span
                                                        role="progressbar"
                                                        aria-label="Photo upload progress"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        x-bind:aria-valuenow="progress"
                                                        class="sr-only"
                                                    ></span>
                                                </span>
                                                <span x-show="uploading" style="display: none;" class="absolute inset-x-0 bottom-5 text-center text-xs text-white/55">Keep this tab open.</span>

                                                <span x-show="uploadFailed" style="display: none;" role="alert" class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-center">
                                                    <x-snap-result.icon name="triangle-alert" class="size-7 text-amber-300" />
                                                    <span class="text-[15px] font-semibold">Upload didn’t finish</span>
                                                    <span class="max-w-40 text-sm leading-snug text-white/70">Tap here and choose the photo again to retry.</span>
                                                </span>
                                            </button>

                                            <div class="grid grid-cols-3 justify-items-center">
                                                <button
                                                    type="button"
                                                    aria-haspopup="dialog"
                                                    x-on:click="$dispatch('snap-scanner-open')"
                                                    x-bind:disabled="uploading || @js($uploadsLocked)"
                                                    class="group/shutter col-start-2 row-start-1 flex touch-manipulation flex-col items-center gap-2 rounded-3xl select-none disabled:cursor-not-allowed disabled:opacity-60 {{ $ring }}"
                                                >
                                                    <span class="flex size-20 items-center justify-center rounded-full border-4 border-white p-1 transition duration-150 ease-out group-active/shutter:scale-95 group-disabled/shutter:scale-100 motion-reduce:transition-none">
                                                        <span class="size-full rounded-full bg-primary"></span>
                                                    </span>
                                                    <span class="text-sm font-medium text-white/80">Take photo</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    x-on:click="pickPhoto()"
                                                    x-bind:disabled="uploading || @js($uploadsLocked)"
                                                    class="group/library col-start-1 row-start-1 flex touch-manipulation flex-col items-center gap-2 rounded-3xl select-none disabled:cursor-not-allowed disabled:opacity-60 {{ $ring }}"
                                                >
                                                    <span class="flex h-20 items-center">
                                                        <span class="flex size-14 items-center justify-center rounded-full bg-white/10 transition duration-150 ease-out group-hover/library:bg-white/20 group-active/library:scale-95 group-disabled/library:scale-100 motion-reduce:transition-none">
                                                            <x-snap-result.icon name="image-plus" class="size-6" />
                                                        </span>
                                                    </span>
                                                    <span class="text-sm font-medium text-white/80">Choose photo</span>
                                                </button>
                                            </div>
                                        </div>

                                        @if ($needsVerification && blank($turnstileToken))
                                            <div class="pointer-events-none absolute inset-x-0 top-0 {{ $frameBox }}">
                                                <p class="absolute inset-x-16 top-5 text-center text-xs text-white/80 motion-safe:animate-in motion-safe:fade-in motion-safe:fill-mode-both motion-safe:delay-[2500ms] motion-safe:duration-500">
                                                    Running a quick security check…
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="{{ $frame }}">
                                        <img
                                            src="{{ $photo->temporaryUrl() }}"
                                            alt="Food photo preview"
                                            class="size-full object-contain"
                                        >
                                        <button
                                            type="button"
                                            wire:click="clearPhoto"
                                            wire:loading.attr="disabled"
                                            wire:target="clearPhoto, analyze"
                                            aria-label="Remove photo"
                                            title="Remove photo"
                                            class="absolute top-3 right-3 flex size-11 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md hover:bg-black/75 disabled:opacity-50 {{ $press }} {{ $ring }}"
                                            @disabled($loading)
                                        >
                                            <x-snap-result.icon name="x" class="size-5" />
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @error('turnstileToken')
                                <p role="alert" class="text-center text-sm text-red-300">{{ $message }}</p>
                            @enderror

                            @if (! $photo)
                                @error('photo')
                                    <p role="alert" class="text-center text-sm text-red-300">{{ $message }}</p>
                                @enderror

                                @if ($photoAllowance->enabled && $photoAllowance->mode === 'trial')
                                    <div class="flex flex-col gap-1 text-center text-sm">
                                        @if ($photoAllowance->used > 0)
                                            <p class="font-medium text-white">{!! e(strtr(__('common.photo_plans.free.remaining_'.($photoAllowance->remaining === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $photoAllowance->remaining])) !!}</p>
                                        @endif
                                        <p id="scan-terms" class="text-balance text-white/60">{!! e(strtr(__('common.photo_plans.free.terms_'.($photoAllowance->limit === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $photoAllowance->limit])) !!}</p>
                                    </div>
                                @elseif ($photoAllowance->preview?->status === 'active')
                                    <p class="text-center text-sm text-white/70">Your daily preview scan is ready. Preview ends <time x-data x-text="new Date(@js($photoAllowance->preview->expiresAt)).toLocaleString()">{{ $photoAllowance->preview->expiresAt }}</time>.</p>
                                @endif
                            @else
                                @guest
                                    <label class="flex cursor-pointer touch-manipulation items-center gap-4 rounded-3xl bg-white/10 py-4 pr-4 pl-5 text-[15px] leading-snug text-white/80">
                                        <span class="min-w-0 flex-1">
                                            Keep my photo to help improve food recognition (optional).
                                            <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="font-medium text-white underline underline-offset-2">Privacy Policy</a>
                                        </span>
                                        <input
                                            type="checkbox"
                                            role="switch"
                                            wire:model="keepPhoto"
                                            class="peer sr-only"
                                            @disabled($loading)
                                        >
                                        <span aria-hidden="true" class="relative h-8 w-13 shrink-0 rounded-full bg-white/20 transition-colors duration-200 after:absolute after:top-0.5 after:left-0.5 after:size-7 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 after:ease-out peer-checked:bg-primary peer-checked:after:translate-x-5 peer-focus-visible:ring-[3px] peer-focus-visible:ring-white/70 motion-reduce:transition-none motion-reduce:after:transition-none"></span>
                                    </label>
                                @endguest

                                <button
                                    type="submit"
                                    x-on:click="window.acaraTagSnapPhoto?.($wire.analysisRequestId)"
                                    data-umami-event="snap_to_track_analyze_click"
                                    data-umami-event-location="main_form"
                                    class="{{ $pill }} bg-primary text-emerald-950 disabled:cursor-wait"
                                    @disabled($loading)
                                >
                                    <span wire:loading.remove wire:target="analyze" class="inline-flex items-center gap-2">
                                        <x-snap-result.icon name="sparkles" class="size-4" />
                                        Analyze Food
                                    </span>
                                    <span wire:loading.inline-flex wire:target="analyze" class="items-center gap-2">
                                        <x-snap-result.icon name="loader" class="size-4 stroke-3 motion-safe:animate-spin" />
                                        Analyzing your meal...
                                    </span>
                                </button>

                                <p wire:loading wire:target="analyze" class="text-center text-sm text-white/60">
                                    This usually takes 5–15 seconds. Hang tight.
                                </p>
                            @endif
                        </div>
                    </form>

                    @if ($photoAllowance->preview?->status === 'active')
                        <x-snap-conversion-offer :allowance="$photoAllowance" placement="preview_upload" />
                    @endif
                @endunless
            </div>
        </div>
    </div>

    <div class="rounded-[2rem] bg-background text-foreground sm:rounded-[2.5rem]">
        <div class="{{ $gutter }} py-12 sm:py-20">
            <div class="mx-auto flex max-w-6xl flex-col gap-16 sm:gap-24">
                <div class="flex flex-col gap-6">
                    @unless ($loading || $photoAllowance->exhausted() || $photo)
                        <section aria-labelledby="tips-heading">
                            <h2 id="tips-heading" class="text-xl font-semibold tracking-tight">Tips for best results</h2>
                            <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                                @foreach ([
                                    'sun-medium' => 'Take photo in good lighting',
                                    'scan' => 'Make sure all food is visible',
                                    'arrow-down-to-dot' => 'Capture from directly above',
                                    'ruler' => 'Include a reference for scale (optional)',
                                ] as $icon => $tip)
                                    <li wire:key="tip-{{ $icon }}" class="flex items-center gap-4 lg:flex-col lg:items-start lg:gap-3">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-neutral-100" aria-hidden="true">
                                            <x-snap-result.icon :name="$icon" class="size-5" />
                                        </span>
                                        <span class="text-base leading-snug text-neutral-700">{{ $tip }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endunless

                    <div class="flex flex-col gap-1 text-sm leading-relaxed text-neutral-500">
                        <p><strong class="font-semibold text-neutral-700">Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.</p>
                        <p>
                            AI vision analysis. Informed by standard nutrition references.
                            <time datetime="{{ now()->toDateString() }}">Last updated: {{ now()->format('F Y') }}</time>
                        </p>
                    </div>
                </div>

                <section aria-labelledby="what-heading" class="speakable-definition">
                    <h2 id="what-heading" class="sr-only">What is Snap to Track?</h2>
                    <p class="max-w-4xl text-2xl leading-snug font-semibold tracking-tight text-pretty sm:text-4xl sm:leading-[1.2]">
                        <strong class="font-semibold">Snap to Track</strong> is a free AI food photo analyzer that estimates calories, protein, carbs, and fat for every ingredient in your meal.
                        <span class="text-neutral-500">Use it as a free meal scanner: upload one photo, get a per-item nutrition breakdown in about 5–15 seconds — no signup required.</span>
                    </p>
                </section>

                <section id="how-it-works" aria-labelledby="how-it-works-heading" class="scroll-mt-8">
                    <h2 id="how-it-works-heading" class="text-3xl font-bold tracking-tight sm:text-4xl">How it works</h2>
                    <p class="mt-3 max-w-2xl text-lg leading-relaxed text-pretty text-neutral-600">
                        Three steps from photo to plate. No journaling, no manual entry, no guess-the-portion games.
                    </p>

                    <ol class="mt-10 grid gap-8 md:grid-cols-3 md:gap-10">
                        <li class="flex gap-5 md:flex-col">
                            <span class="relative size-20 shrink-0 overflow-hidden rounded-3xl bg-black md:size-28 md:rounded-[1.75rem]" aria-hidden="true">
                                <span class="absolute inset-[20%] rounded-full bg-linear-to-br from-white/35 to-white/5"></span>
                                <span class="absolute inset-[32%] rounded-full bg-linear-to-tl from-white/15 to-black/30"></span>
                                <span class="absolute top-[11%] left-[11%] size-[16%] rounded-tl-md border-t-2 border-l-2 border-primary"></span>
                                <span class="absolute top-[11%] right-[11%] size-[16%] rounded-tr-md border-t-2 border-r-2 border-primary"></span>
                                <span class="absolute bottom-[11%] left-[11%] size-[16%] rounded-bl-md border-b-2 border-l-2 border-primary"></span>
                                <span class="absolute right-[11%] bottom-[11%] size-[16%] rounded-br-md border-r-2 border-b-2 border-primary"></span>
                            </span>
                            <div>
                                <h3 class="text-xl font-semibold tracking-tight">Snap a photo of your meal</h3>
                                <p class="speakable-how-it-works mt-2 text-base leading-relaxed text-neutral-600">
                                    Take a clear, well-lit photo of your food from above. The AI will scan the image for recognizable ingredients.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-5 md:flex-col">
                            <span class="relative size-20 shrink-0 overflow-hidden rounded-3xl bg-black md:size-28 md:rounded-[1.75rem]" aria-hidden="true">
                                <span class="absolute inset-[14%] rounded-full bg-linear-to-br from-white/35 to-white/5"></span>
                                <span class="absolute inset-[27%] rounded-full bg-linear-to-tl from-white/15 to-black/30"></span>
                                <span class="absolute top-[30%] left-[36%] size-[13%] rounded-full bg-rose-400 ring-2 ring-black"></span>
                                <span class="absolute top-[44%] left-[58%] size-[13%] rounded-full bg-amber-400 ring-2 ring-black"></span>
                                <span class="absolute top-[60%] left-[34%] size-[13%] rounded-full bg-green-400 ring-2 ring-black"></span>
                            </span>
                            <div>
                                <h3 class="text-xl font-semibold tracking-tight">AI identifies each food item</h3>
                                <p class="speakable-how-it-works mt-2 text-base leading-relaxed text-neutral-600">
                                    Our vision model recognizes individual ingredients, estimates portion sizes, and works out nutrition from standard references like USDA FoodData Central. These are AI estimates, not database lookups.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-5 md:flex-col">
                            <span class="grid size-20 shrink-0 grid-cols-2 grid-rows-2 gap-1.5 rounded-3xl bg-black p-3 md:size-28 md:gap-2 md:rounded-[1.75rem] md:p-4" aria-hidden="true">
                                <span class="flex items-center justify-center rounded-lg bg-rose-100 text-rose-500 md:rounded-xl"><x-snap-result.icon name="drumstick" class="size-4 md:size-5" /></span>
                                <span class="flex items-center justify-center rounded-lg bg-amber-100 text-amber-500 md:rounded-xl"><x-snap-result.icon name="wheat" class="size-4 md:size-5" /></span>
                                <span class="flex items-center justify-center rounded-lg bg-orange-100 text-orange-500 md:rounded-xl"><x-snap-result.icon name="droplet" class="size-4 md:size-5" /></span>
                                <span class="flex items-center justify-center rounded-lg bg-green-100 text-green-600 md:rounded-xl"><x-snap-result.icon name="salad" class="size-4 md:size-5" /></span>
                            </span>
                            <div>
                                <h3 class="text-xl font-semibold tracking-tight">Get instant macro breakdown</h3>
                                <p class="speakable-how-it-works mt-2 text-base leading-relaxed text-neutral-600">
                                    See per-item calories, protein, carbs, and fat — plus meal totals and a confidence score for the analysis.
                                </p>
                            </div>
                        </li>
                    </ol>
                </section>

                <section class="rounded-[2rem] bg-neutral-100 p-7 sm:p-12">
                    <div class="grid gap-6 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-10">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Need more than just tracking?</h2>
                            <p class="mt-3 max-w-xl text-base leading-relaxed text-pretty text-neutral-600">
                                Acara Cloud can use saved meals, logged history, and stable preferences to make future meal planning feel less generic.
                            </p>
                        </div>
                        <a
                            href="{{ route('register') }}"
                            data-umami-event="signup_cta_click"
                            data-umami-event-location="snap_to_track_bottom_promo"
                            class="inline-flex min-h-14 w-full items-center justify-center rounded-full bg-primary px-8 text-base font-semibold text-emerald-950 sm:w-auto {{ $press }} {{ $ringOnLight }}"
                        >
                            Get Started
                        </a>
                    </div>
                </section>

                <section aria-labelledby="faq-heading" class="grid gap-8 lg:grid-cols-[1fr_2fr] lg:gap-16">
                    <div>
                        <h2 id="faq-heading" class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">Frequently Asked Questions</h2>
                        <p class="mt-4 text-base leading-relaxed text-pretty text-neutral-600">
                            Quick context for using the analyzer as a planning aid—not a replacement for a CGM, scale, or your clinician.
                            <a href="{{ route('ai-accuracy') }}" class="font-medium text-foreground underline underline-offset-4">Read the full accuracy &amp; limitations breakdown</a>.
                        </p>
                    </div>

                    <div x-data="{ openFaq: 1 }" class="flex flex-col gap-2">
                        @foreach (App\Services\AiTransparency::snapToTrackFaqs() as $index => $faq)
                            @php $position = $index + 1; @endphp
                            <div wire:key="faq-{{ $position }}" class="rounded-3xl bg-neutral-100">
                                <h3>
                                    <button
                                        type="button"
                                        x-on:click="openFaq = openFaq === {{ $position }} ? null : {{ $position }}"
                                        x-bind:aria-expanded="openFaq === {{ $position }} ? 'true' : 'false'"
                                        class="flex min-h-16 w-full touch-manipulation items-center justify-between gap-4 rounded-3xl px-5 py-4 text-left text-[17px] leading-snug font-semibold tracking-tight select-none sm:px-7 {{ $ringOnLight }}"
                                    >
                                        <span>{{ $faq['q'] }}</span>
                                        <x-snap-result.icon
                                            name="chevron-down"
                                            x-bind:class="openFaq === {{ $position }} && 'rotate-180'"
                                            class="size-5 shrink-0 text-neutral-500 transition-transform duration-200 ease-out motion-reduce:transition-none"
                                        />
                                    </button>
                                </h3>
                                <div x-show="openFaq === {{ $position }}" x-collapse @if ($position !== 1) style="display: none;" @endif>
                                    <p class="max-w-prose px-5 pb-6 text-base leading-relaxed text-neutral-600 sm:px-7">
                                        {{ $faq['a'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <a
                    href="{{ route('tools.index') }}"
                    class="group flex items-center justify-between gap-6 rounded-[2rem] bg-neutral-100 p-7 sm:p-12 {{ $press }} {{ $ringOnLight }}"
                >
                    <span>
                        <span class="block text-base font-medium text-neutral-600">Explore More Tools</span>
                        <span class="mt-1 block text-2xl font-bold tracking-tight sm:text-3xl">View All Tools</span>
                    </span>
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-foreground text-background transition-transform duration-200 ease-out group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">
                        <x-snap-result.icon name="chevron-right" class="size-5" />
                    </span>
                </a>
            </div>
        </div>
    </div>

    <div class="dark pr-[env(safe-area-inset-right)] pb-[env(safe-area-inset-bottom)] pl-[env(safe-area-inset-left)]">
        <x-footer class="border-transparent! bg-black!" />
    </div>
</div>
