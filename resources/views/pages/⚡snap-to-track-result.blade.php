<?php

declare(strict_types=1);

use App\Actions\PrepareSnapToTrackUpgrade;
use App\Actions\RestoreAnalysisDraftAction;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\DailyNutritionGoalsData;
use App\Enums\AnalysisDraftStatus;
use App\Enums\DietType;
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
#[Layout('layouts.mini-app', ['metaDescription' => 'Your Snap to Track meal breakdown, shown the way it looks in your Acara Plate food log.'])]
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

        if (auth()->check()) {
            $this->redirect(route('snap-to-track.save', ['draft' => $token]));

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
        return resolve(ManagesPhotoAnalyses::class)->entitlement(auth()->user(), PhotoAnalysisContext::guestId(request()));
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

    public function upgrade(PrepareSnapToTrackUpgrade $prepareUpgrade, SnapFunnel $funnel, string $placement = 'result'): void
    {
        $placement = in_array($placement, ['result', 'result_bar'], true) ? $placement : 'result';
        $funnel->recordOffer("{$placement}:{$this->resultKey()}", $placement, $this->photoAllowance->offer?->name, clicked: true);

        $this->redirect($prepareUpgrade->handle($this->photoAllowance->offer));
    }

    #[Async]
    #[Renderless]
    public function offerViewed(string $placement, SnapFunnel $funnel): void
    {
        $offer = $this->photoAllowance->canUpgrade ? $this->photoAllowance->offer : null;

        if ($this->draftToken === null || $offer === null || ! in_array($placement, ['result', 'result_bar'], true)) {
            return;
        }

        $funnel->recordOffer("{$placement}:{$this->resultKey()}", $placement, $offer->name);
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
    $photoAllowance = $this->photoAllowance;
    $goals = $this->goals;
@endphp

<div class="min-h-[100dvh] bg-background text-foreground">
    <header class="sticky top-0 z-30 border-b border-border/60 bg-background/90 backdrop-blur">
        <div class="mx-auto flex h-14 w-full max-w-2xl items-center justify-between gap-3 px-4 sm:px-6">
            <a href="{{ route('home') }}" aria-label="Acara Plate home" class="inline-flex min-h-11 items-center gap-2 rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
                <span aria-hidden="true" class="flex size-8 items-center justify-center rounded-md bg-emerald-50 text-lg ring-1 ring-emerald-200">🍓</span>
                <span class="text-sm font-semibold">Acara Plate</span>
            </a>
            @if ($analysis !== null)
                <button
                    type="button"
                    wire:click="save('login')"
                    wire:loading.attr="disabled"
                    wire:target="save,upgrade"
                    class="inline-flex h-10 items-center justify-center rounded-full px-4 text-sm font-medium outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                >
                    Log in
                </button>
            @endif
        </div>
    </header>

    @if ($analysis === null)
        <div x-data x-init="window.acaraForgetSnapPhoto?.()" class="mx-auto w-full max-w-2xl px-4 pt-6 pb-16 sm:px-6">
            <section class="flex flex-col gap-4 rounded-3xl bg-card p-6 shadow-xs ring-1 ring-border/60">
                <h1 class="text-xl font-semibold tracking-tight">This meal preview is no longer available</h1>
                <p class="text-sm text-muted-foreground">
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
            $barOffer = $photoAllowance->canUpgrade ? $photoAllowance->offer : null;
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
            }"
            x-init="window.acaraTrackOnce?.('snap_to_track_result_viewed', { source: 'public_snap_to_track' }, @js($analysisRequestId ?? 'restored'))"
            class="mx-auto flex w-full max-w-2xl min-w-0 flex-col gap-6 px-4 pt-4 pb-40 sm:px-6 sm:pt-6 md:pb-16"
        >
            <h1 class="sr-only">Your meal preview</h1>
            <div class="flex flex-wrap items-center justify-between gap-x-3">
                <p class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                    <x-snap-result.icon name="sparkles" class="size-3.5" />
                    Preview · not saved yet
                </p>
                <a href="#meal-result" class="inline-flex min-h-11 items-center gap-1 rounded-md text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50">
                    See your meal
                    <x-snap-result.icon name="chevron-down" class="size-4" />
                </a>
            </div>

            <nav aria-label="Choose a day" class="flex min-w-0 flex-col gap-1">
                <ol class="grid w-full grid-cols-7 py-2">
                    <template x-for="day in week" :key="day.toDateString()">
                        <li class="flex justify-center">
                            <span
                                class="flex flex-col items-center gap-1 py-1"
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
                                <span aria-hidden="true" class="size-1.5 rounded-full" :class="isToday(day) ? 'bg-primary' : 'bg-transparent'"></span>
                            </span>
                        </li>
                    </template>
                </ol>
            </nav>

            <div class="flex flex-col gap-2">
                <section aria-labelledby="calorie-goal-heading" class="flex flex-col gap-4 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60 sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 flex-col gap-1">
                            <h2 id="calorie-goal-heading" class="text-sm text-muted-foreground">Daily calories</h2>
                            <p class="flex flex-wrap items-baseline gap-x-1">
                                <span class="text-5xl font-bold tracking-tight tabular-nums">{{ $amount($analysis['total_calories']) }}</span>
                                <span class="text-lg text-muted-foreground tabular-nums">/{{ number_format($goals->calories) }} kcal</span>
                            </p>
                        </div>
                        <x-snap-result.progress-ring :value="$analysis['total_calories'] / $goals->calories" :size="88" :stroke="10" />
                    </div>
                </section>
                <p class="px-1 text-xs text-muted-foreground">{{ number_format($goals->calories) }} kcal and the macro targets are a general reference, not goals calculated for you. Create a free account to set your own.</p>
            </div>

            <section aria-labelledby="macro-goals-heading" class="flex flex-col gap-3">
                <h2 id="macro-goals-heading" class="text-xl font-semibold tracking-tight">Macronutrients</h2>
                <ul class="grid grid-cols-2 gap-3">
                    @foreach ($macros as $macro)
                        @php
                            $eaten = $macro['value'] === null ? null : (float) $macro['value'];
                        @endphp
                        <li class="flex min-w-0 flex-col gap-4 rounded-3xl bg-card p-4 shadow-xs ring-1 ring-border/60">
                            <div class="flex items-start justify-between gap-2">
                                <span class="truncate pt-1 text-sm text-muted-foreground">{{ $macro['label'] }}</span>
                                <x-snap-result.progress-ring :value="($eaten ?? 0) / $macro['goal']" :indicator="$macro['ring']">
                                    <x-snap-result.icon :name="$macro['icon']" class="size-5 {{ $macro['iconColor'] }}" />
                                </x-snap-result.progress-ring>
                            </div>
                            <div class="flex items-end justify-between gap-2">
                                <p class="flex min-w-0 flex-wrap items-baseline gap-x-0.5">
                                    <span class="text-3xl font-bold tracking-tight tabular-nums">{{ $eaten === null ? '—' : $amount($eaten) }}</span>
                                    <span class="text-sm text-muted-foreground tabular-nums">/{{ $macro['goal'] }} g</span>
                                </p>
                                @if ($eaten !== null)
                                    <span class="shrink-0 text-sm font-semibold tabular-nums {{ $macro['text'] }}">{{ round($eaten / $macro['goal'] * 100) }}%</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section id="meal-result" aria-labelledby="meal-result-heading" class="flex scroll-mt-20 flex-col gap-3">
                <h2 id="meal-result-heading" class="text-xl font-semibold tracking-tight">Your meal</h2>
                <article class="flex flex-col overflow-hidden rounded-3xl bg-card shadow-xs ring-1 ring-primary/30">
                    <div class="flex min-h-28">
                        <div class="relative w-28 shrink-0 overflow-hidden sm:w-32">
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
                        <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5 p-4">
                            <div class="flex min-w-0 items-baseline gap-2">
                                <h3 class="line-clamp-2 min-w-0 flex-1 leading-snug font-semibold break-words">{{ $title }}</h3>
                                <time class="shrink-0 text-xs text-muted-foreground tabular-nums" :datetime="at.toISOString()" x-text="time"></time>
                            </div>
                            <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="text-lg font-semibold text-primary tabular-nums">{{ $amount($analysis['total_calories']) }} kcal</span>
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">AI estimate</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 border-t border-border/60 px-4 pt-3 pb-1">
                        <dl class="grid grid-cols-4 gap-2">
                            @foreach ($macros as $macro)
                                <div class="flex min-w-0 flex-col gap-1">
                                    <dt class="flex min-w-0 flex-col items-start gap-1 text-xs text-muted-foreground">
                                        <span aria-hidden="true" class="flex size-7 items-center justify-center rounded-lg {{ $macro['tile'] }}">
                                            <x-snap-result.icon :name="$macro['icon']" class="size-4 {{ $macro['iconColor'] }}" />
                                        </span>
                                        <span class="max-w-full truncate">{{ $macro['label'] }}</span>
                                    </dt>
                                    <dd class="text-base font-semibold tabular-nums">{{ $macro['value'] === null ? '—' : $amount($macro['value']).' g' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <a href="#ingredients" class="inline-flex min-h-11 items-center gap-1 self-start rounded-md text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50">
                            See ingredients and portions
                            <x-snap-result.icon name="chevron-down" class="size-4" />
                        </a>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-border/60 bg-primary/5 p-4 sm:p-5">
                        <p class="inline-flex items-center gap-2 self-start rounded-full bg-background px-3 py-1 text-xs font-medium ring-1 ring-border">
                            <span aria-hidden="true" class="size-1.5 rounded-full bg-amber-500"></span>
                            Preview · not saved yet
                        </p>
                        <x-snap-result.button
                            x-intersect:enter.margin.-56px.0.-120px.0="actionsOnScreen.meal = true"
                            x-intersect:leave.margin.-56px.0.-120px.0="actionsOnScreen.meal = false"
                            wire:click="save('register')"
                            wire:loading.attr="disabled"
                            wire:target="save,upgrade"
                            x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'meal' })"
                        >
                            Save this meal
                        </x-snap-result.button>
                        <p class="text-sm text-muted-foreground">Saving is free. Create an account or log in and the meal goes straight into your food log, where you can adjust any portion.</p>
                        <button
                            type="button"
                            wire:click="save('login')"
                            wire:loading.attr="disabled"
                            wire:target="save,upgrade"
                            class="min-h-11 self-start rounded-md text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                        >
                            Already have an account? Log in
                        </button>
                    </div>
                </article>
            </section>

            <section id="ingredients" aria-labelledby="ingredients-heading" class="flex scroll-mt-20 flex-col gap-3">
                <h2 id="ingredients-heading" class="text-xl font-semibold tracking-tight">Ingredients and portions</h2>
                <ul class="divide-y divide-border overflow-hidden rounded-3xl bg-card shadow-xs ring-1 ring-border/60">
                    @foreach ($analysis['items'] as $item)
                        <li class="flex min-h-16 flex-col justify-center gap-1 px-5 py-3">
                            <span class="flex min-w-0 flex-1 flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-3">
                                <span class="min-w-0 text-base break-words sm:flex-1 sm:truncate">{{ $item['name'] }}</span>
                                <span class="flex min-w-0 gap-2 text-sm text-muted-foreground">
                                    <span class="shrink-0 tabular-nums">{{ $amount($item['calories']) }} kcal</span>
                                    @if (filled($item['portion'] ?? null))
                                        <span class="min-w-0 truncate text-muted-foreground/80 sm:max-w-40">{{ $item['portion'] }}</span>
                                    @endif
                                </span>
                            </span>
                            @if (($item['provenance'] ?? 'model') === 'reference')
                                <p class="text-xs text-muted-foreground"><span class="text-primary" aria-hidden="true">◆</span> USDA reference</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="flex items-start gap-2 text-xs text-muted-foreground">
                    <x-snap-result.icon name="sparkles" class="mt-0.5 size-3.5 shrink-0 text-primary" />
                    <span>Snap to Track · AI confidence: {{ $analysis['confidence'] }}%. Nutrition values are estimates, and you can adjust any portion after saving.</span>
                </p>
            </section>

            <x-snap-result.offer
                :allowance="$photoAllowance"
                x-intersect:enter.margin.-56px.0.-120px.0="actionsOnScreen.offer = true"
                x-intersect:leave.margin.-56px.0.-120px.0="actionsOnScreen.offer = false"
            />

            <p class="text-xs text-muted-foreground">{{ App\Services\AiTransparency::carbBoundaryNotice() }}</p>

            @unless ($photoAllowance->exhausted)
                <x-snap-result.button variant="outline" :href="route('snap-to-track')">
                    <x-snap-result.icon name="camera" />
                    Analyze another photo
                </x-snap-result.button>
            @endunless

            <div
                x-show="! actionsOnScreen.meal && ! actionsOnScreen.offer"
                x-transition.opacity.duration.150ms
                style="display: none;"
                class="fixed inset-x-0 bottom-0 z-30 flex flex-col gap-1 border-t border-border/60 bg-background/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:hidden"
            >
                <x-snap-result.button
                    wire:click="save('register')"
                    wire:loading.attr="disabled"
                    wire:target="save,upgrade"
                    x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'result_bar' })"
                >
                    Save this meal free
                </x-snap-result.button>
                @if ($barOffer)
                    <button
                        type="button"
                        x-intersect.once="$wire.offerViewed('result_bar')"
                        wire:click="upgrade('result_bar')"
                        wire:loading.attr="disabled"
                        wire:target="save,upgrade"
                        x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: 'result_bar', variant: {{ Js::from($photoAllowance->variant ?? 'baseline') }} })"
                        class="min-h-10 rounded-md px-2 text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                    >
                        {!! e(strtr(__('common.photo_plans.offer.short_cta'), ['@{{name}}' => $barOffer->name, '@{{price}}' => $barOffer->formattedPrice])) !!}
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
