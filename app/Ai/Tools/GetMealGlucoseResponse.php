<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Data\MealGlucoseInsightData;
use App\Enums\DataSensitivity;
use App\Enums\GlucoseUnit;
use App\Models\User;
use App\Services\AiTransparency;
use App\Services\MealGlucoseResponseService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class GetMealGlucoseResponse implements Tool
{
    public function __construct(private MealGlucoseResponseService $responses) {}

    // @codeCoverageIgnoreStart
    public function name(): string
    {
        return 'get_meal_glucose_response';
    }

    public function description(): string
    {
        return 'Retrieve how the user\'s OWN glucose responded after their recently logged meals — a pre-meal baseline and the peak rise in the hours afterward, computed from their own readings. Use when the user asks how a meal or their food affected their glucose, or what a meal "did" to them. Results are strictly retrospective observations of past data: present them as such, never as a prediction and never as a basis for insulin or medication dosing.';
    }

    // @codeCoverageIgnoreEnd

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode(['error' => 'User not authenticated']); // @codeCoverageIgnore
        }

        if (! $user->wantsGlucoseMealInsights()) {
            return (string) json_encode([
                'opt_in_required' => true,
                'message' => 'Meal glucose insights are turned off. Let the user know they can enable "Meal Glucose Insights" in their settings to see how their own glucose responded after meals.',
            ]);
        }

        $daysInput = $request['days'] ?? 7;
        $days = max(1, is_numeric($daysInput) ? (int) $daysInput : 7);

        $unit = $user->profile->units_preference ?? GlucoseUnit::MmolL;

        $insights = array_map(
            function (array $entry) use ($user, $unit): array {
                $pattern = is_numeric($entry['carbs'])
                    ? $this->responses->carbBandPattern($user, (float) $entry['carbs'], $entry['groupId'])
                    : null; // @codeCoverageIgnore

                return $this->present($entry['mealAt'], MealGlucoseInsightData::fromResponse($entry['response'], $unit, $pattern));
            },
            $this->responses->recentResponses($user, $days),
        );

        return (string) json_encode([
            'success' => true,
            'insights' => $insights,
            'notice' => AiTransparency::carbBoundaryNotice(),
            'guidance' => "Descriptive observations of the user's own past glucose only. Never frame them as a prediction or as dosing guidance.",
            'message' => $insights === []
                ? 'No comparable glucose data around the user\'s recent meals yet — there isn\'t enough surrounding glucose to describe a response.'
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        // @codeCoverageIgnoreStart
        return [
            'days' => $schema->integer()->required()->nullable()
                ->description('How many days back to look for logged meals. Defaults to 7.'),
        ];
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array{meal_logged_at: string, summary: string, comparable: string|null, overlapping: bool}
     */
    private function present(CarbonInterface $mealAt, MealGlucoseInsightData $insight): array
    {
        return [
            'meal_logged_at' => $mealAt->toIso8601String(),
            'summary' => $insight->summary,
            'comparable' => $insight->comparable,
            'overlapping' => $insight->overlapping,
        ];
    }
}
