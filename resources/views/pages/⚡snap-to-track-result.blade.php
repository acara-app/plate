<?php

declare(strict_types=1);

use App\Actions\Billing\StartGuestCheckout;
use App\Actions\PrepareSnapToTrackUpgrade;
use App\Actions\RestoreAnalysisDraftAction;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Data\DailyNutritionGoalsData;
use App\Enums\AnalysisDraftStatus;
use App\Enums\DietType;
use App\Enums\PhotoPlan;
use App\Enums\SnapFunnelStage;
use App\Services\Billing\SnapFunnel;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['bodyClass' => 'bg-muted text-foreground', 'viewport' => 'width=device-width, initial-scale=1, viewport-fit=cover', 'themeColor' => '#f5f5f5', 'metaDescription' => 'Your Snap to Track meal breakdown, shown the way it looks in your Acara Plate food log.'])]
#[Title('Your meal | Snap to Track')]
class extends Component
{
    private const int REFERENCE_DAILY_CALORIES = 2000;

    /** @var array{items: array<int, array<string, mixed>>, total_calories: float, total_protein: float, total_carbs: float, total_fat: float, total_fiber: float|null, confidence: int}|null */
    #[Locked]
    public ?array $analysis = null;

    #[Locked]
    public ?string $unavailableReason = null;

    #[Locked]
    public ?string $analyzedAt = null;

    #[Locked]
    public ?string $analysisRequestId = null;

    #[Locked]
    public ?string $draftToken = null;

    public function mount(RestoreAnalysisDraftAction $restore, SnapFunnel $funnel): void
    {
        $token = $this->resultDraftToken();

        if ($token === null) {
            $this->redirect(route('snap-to-track'));

            return;
        }

        $resolution = $restore->handle($token, null);

        if (in_array($resolution->status, [AnalysisDraftStatus::Expired, AnalysisDraftStatus::Consumed], true)) {
            $this->unavailableReason = $resolution->status->value;

            return;
        }

        $analysis = $resolution->status === AnalysisDraftStatus::Restored ? $resolution->analysis() : null;

        if ($analysis === null) {
            session()->forget('snap_to_track.result');
            $this->redirect(route('snap-to-track'));

            return;
        }

        $requestId = session('snap_to_track.result.request_id');
        $this->draftToken = $token;
        $this->analysis = $analysis->toArray();
        $this->analysisRequestId = is_string($requestId) ? $requestId : null;
        $this->analyzedAt = $resolution->draft?->created_at->toIso8601String();
        $funnel->record(SnapFunnelStage::ResultViewed, $this->resultKey());
    }

    #[Computed]
    public function photoAllowance(): PhotoEntitlement
    {
        return resolve(ManagesPhotoAnalyses::class)->entitlement(auth()->user(), $this->guestId());
    }

    #[Computed]
    public function goals(): DailyNutritionGoalsData
    {
        return DailyNutritionGoalsData::forCalories(self::REFERENCE_DAILY_CALORIES, DietType::Balanced);
    }

    public function save(string $intent, SnapFunnel $funnel): void
    {
        if ($this->draftToken === null || ! in_array($intent, ['register', 'login'], true)) {
            return;
        }

        $funnel->record(SnapFunnelStage::SaveClicked, $this->resultKey(), ['auth_path' => $intent]);

        $saveUrl = route('snap-to-track.save', ['draft' => $this->draftToken], absolute: false);

        if (auth()->check()) {
            $this->redirect($saveUrl);

            return;
        }

        session()->put('url.intended', $saveUrl);
        session()->put('snap_to_track.auth_path', $intent);
        session()->put('snap_to_track.intent', 'save');

        $this->redirect(route($intent));
    }

    public function upgrade(PrepareSnapToTrackUpgrade $prepareUpgrade, StartGuestCheckout $startCheckout, SnapFunnel $funnel, string $placement = 'result'): void
    {
        $offer = $this->offerForPlacement($placement);

        if ($offer === null) {
            return;
        }

        $freeScansUsed = $this->photoAllowance->freeScansUsed();
        $funnel->recordOffer("{$placement}:{$this->resultKey()}", $placement, $offer->name, $freeScansUsed, clicked: true);
        $guestId = $this->guestId();

        if (auth()->check() || $guestId === null) {
            $this->redirect($prepareUpgrade->handle($offer));

            return;
        }

        try {
            $this->redirect($startCheckout->handle($guestId, $placement, $freeScansUsed));
        } catch (Throwable $exception) {
            report($exception);
            $funnel->record(SnapFunnelStage::CheckoutFailed, "{$placement}:{$this->resultKey()}", ['reason' => 'error']);
            $this->addError('checkout', 'We could not open checkout just now. Please try again.');
        }
    }

    public function signInForPlan(PrepareSnapToTrackUpgrade $prepareUpgrade): void
    {
        $offer = $this->photoAllowance->offer;

        if (auth()->check() || $offer === null) {
            return;
        }

        $prepareUpgrade->handle($offer);

        $this->redirect(route('checkout.start', ['product' => $offer->productId, 'returning' => 1]));
    }

    #[Async]
    #[Renderless]
    public function offerViewed(string $placement, SnapFunnel $funnel): void
    {
        $offer = $this->offerForPlacement($placement);

        if ($offer !== null) {
            $funnel->recordOffer("{$placement}:{$this->resultKey()}", $placement, $offer->name, $this->photoAllowance->freeScansUsed());
        }
    }

    private function offerForPlacement(string $placement): ?PhotoOffer
    {
        $allowance = $this->photoAllowance;

        if ($this->draftToken === null || ! $allowance->canUpgrade || ! in_array($placement, ['result', 'result_bar'], true)) {
            return null;
        }

        if ($allowance->exhausted) {
            return $allowance->offer;
        }

        return $placement === 'result_bar' && $allowance->offer?->name === PhotoPlan::Starter->productName() ? $allowance->offer : null;
    }

    private function guestId(): ?string
    {
        $guestId = PhotoAnalysisContext::guestId(request()) ?? session('snap_to_track.analysis_guest_id');

        return is_string($guestId) ? $guestId : null;
    }

    private function resultKey(): string
    {
        return $this->analysisRequestId ?? hash('sha256', (string) $this->draftToken);
    }

    private function resultDraftToken(): ?string
    {
        $token = session('snap_to_track.result.draft');

        return is_string($token) ? $token : null;
    }
};
?>

@php
    $amount = static fn (float|int|null $value): string => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    $kcal = static fn (float|int|null $value): string => number_format(round((float) $value));
    $photoAllowance = $this->photoAllowance;
    $goals = $this->goals;
    $gutter = 'pr-[max(1rem,env(safe-area-inset-right))] pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1.5rem,env(safe-area-inset-right))] sm:pl-[max(1.5rem,env(safe-area-inset-left))]';
    $press = 'touch-manipulation select-none transition duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100';
    $ring = 'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';
    $link = 'inline-flex min-h-11 items-center gap-1 rounded-full text-sm font-medium text-primary disabled:pointer-events-none disabled:opacity-50 '.$press.' '.$ring;
    $card = 'rounded-[1.75rem] bg-card';
@endphp

<div class="min-h-svh bg-muted text-foreground [-webkit-tap-highlight-color:transparent] [-webkit-text-size-adjust:100%]">
    <header class="{{ $gutter }} pt-[env(safe-area-inset-top)]">
        <div class="mx-auto flex h-14 w-full max-w-2xl items-center justify-between gap-3">
            <a href="{{ route('home') }}" aria-label="Acara Plate home" class="-ml-2 inline-flex min-h-11 items-center gap-2 rounded-full px-2 {{ $press }} {{ $ring }}">
                <span aria-hidden="true" class="text-xl leading-none">🍓</span>
                <span class="text-[17px] font-bold tracking-tight">Acara Plate</span>
            </a>
            @if ($analysis !== null && ! auth()->check())
                <button
                    type="button"
                    wire:click="save('login')"
                    wire:loading.attr="disabled"
                    wire:target="save,upgrade,signInForPlan"
                    class="-mr-3 inline-flex min-h-11 items-center justify-center rounded-full px-3 text-[15px] font-medium text-muted-foreground hover:text-foreground disabled:pointer-events-none disabled:opacity-50 {{ $press }} {{ $ring }}"
                >
                    Log in
                </button>
            @endif
        </div>
    </header>

    @if ($analysis === null)
        <div x-data x-init="window.acaraForgetSnapPhoto?.()" class="{{ $gutter }} pt-2 pb-16 sm:pt-4">
            <section class="{{ $card }} mx-auto flex w-full max-w-2xl flex-col gap-4 p-6">
                <h1 class="text-xl font-semibold tracking-tight">This meal preview is no longer available</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ $unavailableReason === 'consumed'
                        ? 'This meal has already been saved to a food log.'
                        : 'Meal previews expire 60 minutes after analysis to protect your privacy. Analyze another photo to pick up where you left off.' }}
                </p>
                <x-snap-result.button :href="route('snap-to-track')">
                    <x-snap-result.icon name="camera" />
                    Analyze another photo
                </x-snap-result.button>
            </section>
        </div>
    @else
        @php
            $title = Str::limit(collect($analysis['items'])->pluck('name')->map(fn (string $name): string => trim($name))->filter()->implode(', '), 500, '');
            $macros = [
                ['label' => 'Protein', 'value' => $analysis['total_protein'], 'goal' => $goals->protein, 'icon' => 'drumstick', 'tile' => 'bg-rose-100', 'iconColor' => 'text-rose-500', 'text' => 'text-rose-600', 'ring' => 'stroke-rose-500'],
                ['label' => 'Carbs', 'value' => $analysis['total_carbs'], 'goal' => $goals->carbs, 'icon' => 'wheat', 'tile' => 'bg-amber-100', 'iconColor' => 'text-amber-500', 'text' => 'text-amber-700', 'ring' => 'stroke-amber-400'],
                ['label' => 'Fat', 'value' => $analysis['total_fat'], 'goal' => $goals->fat, 'icon' => 'droplet', 'tile' => 'bg-orange-100', 'iconColor' => 'text-orange-500', 'text' => 'text-orange-700', 'ring' => 'stroke-orange-400'],
                ['label' => 'Fiber', 'value' => $analysis['total_fiber'] ?? null, 'goal' => $goals->fiber, 'icon' => 'salad', 'tile' => 'bg-green-100', 'iconColor' => 'text-green-600', 'text' => 'text-green-700', 'ring' => 'stroke-green-500'],
            ];
            $barOffer = $this->offerForPlacement('result_bar');
            $showPlanChoices = $barOffer !== null && ! $photoAllowance->exhausted;
            $barClearance = $barOffer ? 232 : 96;
            $freeScansLeft = $photoAllowance->enabled && $photoAllowance->mode === 'trial' && ! $photoAllowance->exhausted ? $photoAllowance->remaining : null;
            $freeScansLabel = $freeScansLeft === null ? null : strtr(
                __('common.photo_plans.free.remaining_'.($freeScansLeft === 1 ? 'one' : 'other')),
                ['{{count}}' => (string) $freeScansLeft],
            );
        @endphp

        <div
            x-data="{
                at: new Date(@js($analyzedAt ?? now()->toIso8601String())),
                photo: window.acaraRecallSnapPhoto?.(@js($analysisRequestId)) ?? null,
                actionsOnScreen: { meal: true, offer: false },
                get mealType() {
                    const hour = this.at.getHours();

                    if (hour >= 4 && hour < 11) return 'breakfast';
                    if (hour >= 11 && hour < 16) return 'lunch';
                    if (hour >= 16 && hour < 22) return 'dinner';

                    return 'snack';
                },
                get time() {
                    return this.at.toLocaleTimeString('en', { hour: 'numeric', minute: '2-digit' });
                },
                get week() {
                    const start = new Date(this.at);
                    start.setHours(0, 0, 0, 0);
                    start.setDate(start.getDate() - ((start.getDay() + 6) % 7));

                    return Array.from({ length: 7 }, (_, index) => {
                        const day = new Date(start);
                        day.setDate(start.getDate() + index);

                        return day;
                    });
                },
                isToday(day) {
                    return day.toDateString() === this.at.toDateString();
                },
                isFuture(day) {
                    return day > this.at && ! this.isToday(day);
                },
                jumpTo(id) {
                    document.getElementById(id)?.scrollIntoView({
                        block: 'start',
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    });
                },
            }"
            x-init="window.acaraTrackOnce?.('snap_to_track_result_viewed', { source: 'public_snap_to_track' }, @js($analysisRequestId ?? 'restored'))"
            @class([$gutter, 'pt-2 sm:pt-4', $showPlanChoices ? 'pb-[calc(env(safe-area-inset-bottom)+16rem)]' : 'pb-[calc(env(safe-area-inset-bottom)+6.5rem)]'])
        >
            <div class="mx-auto flex w-full max-w-2xl min-w-0 flex-col gap-6">
                <h1 class="sr-only">Your meal preview</h1>
                <div class="flex flex-wrap items-center justify-between gap-x-3">
                    <p class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary">
                        <x-snap-result.icon name="sparkles" class="size-3.5" />
                        Preview · not saved yet
                    </p>
                    <a href="#meal-result" x-on:click.prevent="jumpTo('meal-result')" class="{{ $link }}">
                        See your meal
                        <x-snap-result.icon name="chevron-down" class="size-4" />
                    </a>
                </div>

                <nav aria-label="Choose a day" class="flex min-w-0 flex-col">
                    <ol class="grid w-full grid-cols-7">
                        <template x-for="day in week" :key="day.toDateString()">
                            <li class="flex justify-center">
                                <span
                                    class="flex flex-col items-center gap-1"
                                    :class="isFuture(day) && 'opacity-40'"
                                    :aria-current="isToday(day) ? 'date' : null"
                                >
                                    <span
                                        class="flex size-11 items-center justify-center rounded-full text-lg font-semibold tabular-nums"
                                        :class="isToday(day) ? 'bg-primary text-primary-foreground shadow-[0_0_16px_rgba(16,185,129,0.35)]' : ''"
                                        x-text="day.getDate()"
                                    ></span>
                                    <span
                                        class="text-xs capitalize"
                                        :class="isToday(day) ? 'font-medium text-primary' : 'text-muted-foreground'"
                                        x-text="day.toLocaleDateString('en', { weekday: 'short' })"
                                    ></span>
                                </span>
                            </li>
                        </template>
                    </ol>
                </nav>

                <div class="flex flex-col gap-2.5">
                    <section aria-labelledby="calorie-goal-heading" class="{{ $card }} flex items-center justify-between gap-4 p-5 sm:p-6">
                        <div class="flex min-w-0 flex-col gap-1">
                            <h2 id="calorie-goal-heading" class="text-sm text-muted-foreground">Daily calories</h2>
                            <p class="flex flex-wrap items-baseline gap-x-1.5">
                                <span class="text-5xl font-bold tracking-tight tabular-nums">{{ $kcal($analysis['total_calories']) }}</span>
                                <span class="text-base text-muted-foreground tabular-nums">/ {{ number_format($goals->calories) }} kcal</span>
                            </p>
                        </div>
                        <x-snap-result.progress-ring :value="$analysis['total_calories'] / $goals->calories" :size="88" :stroke="10">
                            <span class="text-sm font-semibold tabular-nums">{{ round($analysis['total_calories'] / $goals->calories * 100) }}%</span>
                        </x-snap-result.progress-ring>
                    </section>
                    <p class="px-2 text-xs leading-relaxed text-muted-foreground">{{ number_format($goals->calories) }} kcal and the macro targets are a general reference, not goals calculated for you. Create a free account to set your own.</p>
                </div>

                <section aria-labelledby="macro-goals-heading" class="flex flex-col gap-3">
                    <h2 id="macro-goals-heading" class="px-1 text-xl font-semibold tracking-tight">Macronutrients</h2>
                    <ul class="grid grid-cols-2 gap-2.5">
                        @foreach ($macros as $macro)
                            @php
                                $eaten = $macro['value'] === null ? null : (float) $macro['value'];
                            @endphp
                            <li class="{{ $card }} flex min-w-0 flex-col gap-4 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="truncate pt-1 text-sm text-muted-foreground">{{ $macro['label'] }}</span>
                                    <x-snap-result.progress-ring :value="($eaten ?? 0) / $macro['goal']" :indicator="$macro['ring']">
                                        <x-snap-result.icon :name="$macro['icon']" class="size-5 {{ $macro['iconColor'] }}" />
                                    </x-snap-result.progress-ring>
                                </div>
                                <div class="flex items-end justify-between gap-2">
                                    <p class="flex min-w-0 flex-wrap items-baseline gap-x-1">
                                        <span class="text-3xl font-bold tracking-tight tabular-nums">{{ $eaten === null ? '—' : $amount($eaten) }}</span>
                                        <span class="text-sm text-muted-foreground tabular-nums">/ {{ $macro['goal'] }} g</span>
                                    </p>
                                    @if ($eaten !== null)
                                        <span class="shrink-0 pb-0.5 text-sm font-semibold tabular-nums {{ $macro['text'] }}">{{ round($eaten / $macro['goal'] * 100) }}%</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section id="meal-result" aria-labelledby="meal-result-heading" class="flex scroll-mt-4 flex-col gap-3">
                    <h2 id="meal-result-heading" class="px-1 text-xl font-semibold tracking-tight">Your meal</h2>
                    <article class="{{ $card }} flex flex-col gap-5 p-4 sm:p-5">
                        <div class="flex items-center gap-4">
                            <div class="relative size-24 shrink-0 overflow-hidden rounded-[1.25rem] sm:size-28">
                                <template x-if="photo">
                                    <img :src="photo" alt="Your meal photo" class="absolute inset-0 size-full object-cover">
                                </template>
                                <template x-if="! photo">
                                    <span
                                        aria-hidden="true"
                                        class="flex size-full items-center justify-center"
                                        :class="{ 'bg-orange-100': mealType === 'breakfast', 'bg-cyan-100': mealType === 'lunch', 'bg-indigo-100': mealType === 'dinner', 'bg-emerald-100': mealType === 'snack' }"
                                    >
                                        <x-snap-result.icon name="coffee" class="size-8 text-orange-700" x-show="mealType === 'breakfast'" />
                                        <x-snap-result.icon name="sun-medium" class="size-8 text-cyan-700" x-show="mealType === 'lunch'" />
                                        <x-snap-result.icon name="moon" class="size-8 text-indigo-700" x-show="mealType === 'dinner'" />
                                        <x-snap-result.icon name="apple" class="size-8 text-emerald-700" x-show="mealType === 'snack'" />
                                    </span>
                                </template>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <h3 class="line-clamp-2 leading-snug font-semibold break-words">{{ $title }}</h3>
                                <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="text-lg font-semibold text-primary tabular-nums">{{ $kcal($analysis['total_calories']) }} kcal</span>
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">AI estimate</span>
                                </p>
                                <time class="text-xs text-muted-foreground tabular-nums" :datetime="at.toISOString()" x-text="time"></time>
                            </div>
                        </div>

                        <dl class="grid grid-cols-4 gap-2">
                            @foreach ($macros as $macro)
                                <div class="flex min-w-0 flex-col gap-1">
                                    <dt class="flex min-w-0 flex-col items-start gap-1.5 text-xs text-muted-foreground">
                                        <span aria-hidden="true" class="flex size-8 items-center justify-center rounded-xl {{ $macro['tile'] }}">
                                            <x-snap-result.icon :name="$macro['icon']" class="size-4 {{ $macro['iconColor'] }}" />
                                        </span>
                                        <span class="max-w-full truncate">{{ $macro['label'] }}</span>
                                    </dt>
                                    <dd class="text-base font-semibold tabular-nums">{{ $macro['value'] === null ? '—' : $amount($macro['value']).' g' }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="flex flex-col gap-2">
                            <div
                                x-intersect:enter.margin.0.0.-{{ $barClearance }}px.0="actionsOnScreen.meal = true"
                                x-intersect:leave.margin.0.0.-{{ $barClearance }}px.0="actionsOnScreen.meal = false"
                            >
                                <x-snap-result.button
                                    wire:click="save('register')"
                                    wire:loading.attr="disabled"
                                    wire:target="save,upgrade,signInForPlan"
                                    x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'meal' })"
                                >
                                    Save this meal
                                </x-snap-result.button>
                            </div>
                            @auth
                                <p class="px-1 text-sm leading-relaxed text-muted-foreground">Saving is free. The meal goes straight into your food log, where you can adjust any portion.</p>
                            @else
                                <p class="px-1 text-sm leading-relaxed text-muted-foreground">Saving is free. Create an account or log in and the meal goes straight into your food log, where you can adjust any portion.</p>
                            @endauth
                            <div class="flex flex-wrap items-center justify-between gap-x-4">
                                <a href="#ingredients" x-on:click.prevent="jumpTo('ingredients')" class="{{ $link }}">
                                    See ingredients and portions
                                    <x-snap-result.icon name="chevron-down" class="size-4" />
                                </a>
                                @guest
                                    <button
                                        type="button"
                                        wire:click="save('login')"
                                        wire:loading.attr="disabled"
                                        wire:target="save,upgrade,signInForPlan"
                                        class="{{ $link }}"
                                    >
                                        Already have an account? Log in
                                    </button>
                                @endguest
                            </div>
                        </div>
                    </article>
                </section>

                <section id="ingredients" aria-labelledby="ingredients-heading" class="flex scroll-mt-4 flex-col gap-3">
                    <h2 id="ingredients-heading" class="px-1 text-xl font-semibold tracking-tight">Ingredients and portions</h2>
                    <ul class="{{ $card }} flex flex-col px-5 py-2">
                        @foreach ($analysis['items'] as $item)
                            <li class="flex items-baseline justify-between gap-4 py-3">
                                <span class="flex min-w-0 flex-col gap-0.5">
                                    <span class="text-base break-words">{{ $item['name'] }}</span>
                                    @if (filled($item['portion'] ?? null))
                                        <span class="text-sm text-muted-foreground">{{ $item['portion'] }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-sm font-medium text-muted-foreground tabular-nums">{{ $kcal($item['calories']) }} kcal</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="flex items-start gap-2 px-2 text-xs leading-relaxed text-muted-foreground">
                        <x-snap-result.icon name="sparkles" class="mt-0.5 size-3.5 shrink-0 text-primary" />
                        <span>Snap to Track · AI confidence: {{ $analysis['confidence'] }}%. Nutrition values are estimates, and you can adjust any portion after saving.</span>
                    </p>
                </section>

                <x-snap-result.offer :allowance="$photoAllowance" :clearance="$barClearance" />

                <p class="px-2 text-xs leading-relaxed text-muted-foreground">{{ App\Services\AiTransparency::carbBoundaryNotice() }}</p>

                @unless ($photoAllowance->exhausted)
                    <div class="flex flex-col gap-2">
                        @if ($freeScansLabel !== null)
                            <p class="px-1 text-center text-sm font-medium text-muted-foreground">{{ $freeScansLabel }}</p>
                        @endif
                        <x-snap-result.button variant="plain" :href="route('snap-to-track')">
                            <x-snap-result.icon name="camera" />
                            Analyze another photo
                        </x-snap-result.button>
                    </div>
                @endunless
            </div>

            <div
                x-data="{ plan: 'free' }"
                x-show="! actionsOnScreen.meal && ! actionsOnScreen.offer"
                x-transition:enter="transition duration-300 ease-out motion-reduce:transition-none"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition duration-200 ease-out motion-reduce:transition-none"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                style="display: none;"
                class="fixed inset-x-0 bottom-0 z-30 md:pb-4"
            >
                <div class="mx-auto flex w-full max-w-md flex-col gap-2.5 rounded-t-[1.75rem] bg-card pt-3 pr-[max(0.75rem,env(safe-area-inset-right))] pb-[max(0.75rem,env(safe-area-inset-bottom))] pl-[max(0.75rem,env(safe-area-inset-left))] shadow-[0_-10px_40px_rgba(0,0,0,0.1)] md:rounded-[1.75rem] md:shadow-[0_16px_48px_rgba(0,0,0,0.18)] md:ring-1 md:ring-foreground/10">
                    @if ($showPlanChoices)
                        <div role="radiogroup" aria-label="How to keep this meal" class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-2">
                            <x-snap-result.plan-choice value="free" :label="__('common.photo_plans.free.price')" description="Save this meal" />
                            <x-snap-result.plan-choice
                                value="paid"
                                :label="__('common.photo_plans.offer.scans_and_coach')"
                                :description="$barOffer->formattedPrice.__('common.checkout_subscription.per_month')"
                                x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: 'result_bar', variant: {{ Js::from($photoAllowance->variant ?? 'baseline') }} }, {{ Js::from('result_bar:'.$this->resultKey()) }}); $wire.offerViewed('result_bar')"
                            />
                        </div>
                    @endif
                    <div class="flex flex-col gap-2">
                        @if ($barOffer)
                            <div
                                @if ($showPlanChoices)
                                    x-show="plan === 'paid'" style="display: none;"
                                @else
                                    x-intersect.once="$wire.offerViewed('result_bar')"
                                @endif
                                class="flex flex-col gap-2"
                            >
                                <x-snap-result.button
                                    variant="plan"
                                    size="sm"
                                    wire:click="upgrade('result_bar')"
                                    wire:loading.attr="disabled"
                                    wire:target="save,upgrade,signInForPlan"
                                    x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: 'result_bar', variant: {{ Js::from($photoAllowance->variant ?? 'baseline') }} })"
                                >
                                    @if ($showPlanChoices)
                                        {{ __('common.photo_plans.offer.continue_to_checkout') }}
                                    @else
                                        {!! e(strtr(__('common.photo_plans.offer.get_plan'), ['@{{name}}' => $barOffer->name, '@{{price}}' => $barOffer->formattedPrice])) !!}
                                    @endif
                                </x-snap-result.button>
                                @error('checkout')
                                    <p role="alert" class="px-2 text-center text-sm text-destructive">{{ $message }}</p>
                                @enderror
                                @unless ($showPlanChoices)
                                    <div class="flex flex-col items-center gap-0.5 px-2 pb-1 text-center text-[11px] leading-snug">
                                        @guest
                                            <p class="text-xs font-medium">{{ __('common.photo_plans.offer.no_account') }}</p>
                                        @endguest
                                        <p class="text-muted-foreground">{{ __('common.photo_plans.offer.renewal') }}</p>
                                    </div>
                                @endunless
                            </div>
                        @endif
                        <div @if ($showPlanChoices) x-show="plan === 'free'" @endif class="flex flex-col gap-2">
                            <x-snap-result.button
                                wire:click="save('register')"
                                wire:loading.attr="disabled"
                                wire:target="save,upgrade,signInForPlan"
                                x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'result_bar' })"
                            >
                                Save this meal free
                            </x-snap-result.button>
                        </div>
                        @if ($showPlanChoices)
                            <div class="flex min-h-12 flex-wrap items-center justify-between gap-x-3 px-1 text-xs">
                                @if ($freeScansLabel !== null)
                                    <a href="{{ route('snap-to-track') }}" x-show="plan === 'free'" class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-1 hover:bg-muted {{ $press }} {{ $ring }}">
                                        <x-snap-result.icon name="chevron-right" class="size-4 shrink-0 rotate-180" />
                                        <span class="flex flex-col">
                                            <span class="text-sm font-semibold">{{ __('common.photo_plans.checkout.try_another') }}</span>
                                            <span class="text-muted-foreground">{{ $freeScansLabel }}</span>
                                        </span>
                                    </a>
                                @endif
                                <div x-show="plan === 'paid'" style="display: none;" class="flex min-w-0 flex-1 flex-col">
                                    @guest
                                        <span class="font-medium text-balance">{{ __('common.photo_plans.offer.no_account') }}</span>
                                    @endguest
                                    <span class="text-muted-foreground">{{ __('common.photo_plans.offer.monthly_renewal') }}</span>
                                </div>
                                <a href="{{ route('checkout.subscription') }}" target="_blank" rel="noopener" class="ml-auto inline-flex min-h-11 items-center rounded-lg px-1 font-medium underline underline-offset-4 {{ $press }} {{ $ring }}">
                                    {{ __('common.photo_plans.offer.plan_details') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
