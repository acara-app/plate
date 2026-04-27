<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildBrewPlanSpec;
use App\Actions\CalculateCaffeineSafeDose;
use App\Actions\CalculateCaffeineSleepCutoff;
use App\Actions\SearchCaffeineDrinks;
use App\Ai\Agents\BrewBuddyAgent;
use App\Http\Requests\PlanBrewBuddyRequest;
use App\Models\CaffeineDrink;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CaffeineCalculatorController
{
    /**
     * @var array<string, int>
     */
    private const array SENSITIVITY_STEPS = [
        'low' => 4,
        'normal' => 2,
        'high' => 0,
    ];

    public function create(Request $request): Response
    {
        return Inertia::render('caffeine-calculator');
    }

    public function plan(PlanBrewBuddyRequest $request): JsonResponse
    {
        $candidates = $this->lookupCandidateDrinks((string) $request->validated('prompt'));

        $safeMg = $this->resolveSafeMg($request);
        $cutoff = $this->resolveSleepCutoff($request, $safeMg);

        $userPrompt = $this->composePrompt($request, $candidates, $safeMg, $cutoff);

        $plan = resolve(BrewBuddyAgent::class)->plan($userPrompt);
        $spec = resolve(BuildBrewPlanSpec::class)->handle($plan);

        return response()->json([
            'summary' => $plan->summary,
            'spec' => $spec,
        ]);
    }

    /**
     * @return list<array{id: int, name: string, category: ?string, caffeine_mg: float, volume_oz: float}>
     */
    private function lookupCandidateDrinks(string $prompt): array
    {
        $hits = resolve(SearchCaffeineDrinks::class)->handle($prompt);

        if ($hits->isEmpty()) {
            return CaffeineDrink::query()
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'category', 'caffeine_mg', 'volume_oz'])
                ->map(fn (CaffeineDrink $drink): array => [
                    'id' => $drink->id,
                    'name' => $drink->name,
                    'category' => $drink->category,
                    'caffeine_mg' => (float) $drink->caffeine_mg,
                    'volume_oz' => (float) $drink->volume_oz,
                ])
                ->all();
        }

        $ids = $hits->pluck('id')->all();

        $models = CaffeineDrink::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'category', 'caffeine_mg', 'volume_oz'])
            ->keyBy('id');

        return $hits
            ->map(function (array $hit) use ($models): ?array {
                $drink = $models->get($hit['id']);
                if (! $drink instanceof CaffeineDrink) {
                    return null;
                }

                return [
                    'id' => (int) $drink->id,
                    'name' => (string) $drink->name,
                    'category' => $drink->category,
                    'caffeine_mg' => (float) $drink->caffeine_mg,
                    'volume_oz' => (float) $drink->volume_oz,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveSafeMg(PlanBrewBuddyRequest $request): ?float
    {
        $weightKg = $request->validated('weight_kg');
        if ($weightKg === null) {
            return null;
        }

        $sensitivity = (string) ($request->validated('sensitivity') ?? 'normal');
        $step = self::SENSITIVITY_STEPS[$sensitivity] ?? self::SENSITIVITY_STEPS['normal'];

        return round(resolve(CalculateCaffeineSafeDose::class)->handle((float) $weightKg, $step, 95.0)->safeMg, 1);
    }

    private function resolveSleepCutoff(PlanBrewBuddyRequest $request, ?float $safeMg): ?string
    {
        $bedtime = $request->validated('bedtime');
        if ($bedtime === null || $safeMg === null) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', (string) $bedtime));
        $bed = CarbonImmutable::now()->setTime($hour, $minute);

        return resolve(CalculateCaffeineSleepCutoff::class)->handle($bed, $safeMg, 1)?->format('H:i');
    }

    /**
     * @param  list<array{id: int, name: string, category: ?string, caffeine_mg: float, volume_oz: float}>  $candidates
     */
    private function composePrompt(
        PlanBrewBuddyRequest $request,
        array $candidates,
        ?float $safeMg,
        ?string $cutoffHhmm,
    ): string {
        $context = [
            'user_message' => (string) $request->validated('prompt'),
            'known_facts' => array_filter([
                'weight_kg' => $request->validated('weight_kg'),
                'sensitivity' => $request->validated('sensitivity'),
                'bedtime' => $request->validated('bedtime'),
            ], fn ($value): bool => $value !== null),
            'safe_mg' => $safeMg,
            'sleep_cutoff_hhmm' => $cutoffHhmm,
            'candidate_drinks' => $candidates,
            'cutoff_formula' => 'cutoff_hhmm = bedtime - 5h * log2(planned_total_mg / 50mg). The provided sleep_cutoff_hhmm assumes the safe_mg ceiling; planning less caffeine pushes the cutoff earlier.',
        ];

        return "User request:\n".(string) $request->validated('prompt')
            ."\n\nContext (JSON):\n".(string) json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
