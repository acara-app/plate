<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class SuggestWellnessRoutine implements Tool
{
    public function name(): string
    {
        return 'suggest_wellness_routine';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Suggest personalized wellness routines including sleep hygiene, stress management, hydration, and lifestyle modifications. Use this to help users establish healthy daily habits.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return json_encode([
                'error' => 'User not authenticated',
                'routine' => null,
            ]);
        }

        /** @var string $focus */
        $focus = $request['focus'] ?? 'general';

        $routines = $this->generateRoutines($focus);

        return json_encode([
            'success' => true,
            'focus' => $focus,
            'routines' => $routines,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'focus' => $schema->string()
                ->enum(['general', 'sleep', 'stress', 'hydration'])
                ->description('Which wellness area to focus on: general daily routine, sleep improvement, stress management, or hydration.')
                ->required(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function generateRoutines(string $focus): array
    {
        $routines = match ($focus) {
            'sleep' => [
                'morning' => [
                    'title' => 'Morning Light Exposure',
                    'time' => 'Within 30 min of waking',
                    'activities' => [
                        'Get 10-30 minutes of natural sunlight',
                        'Avoid sunglasses (but never stare at sun)',
                        'This helps regulate circadian rhythm',
                    ],
                ],
                'evening' => [
                    'title' => 'Sleep Wind-Down',
                    'time' => '2 hours before bed',
                    'activities' => [
                        'Dim lights in your home',
                        'Avoid screens or use blue light filter',
                        'Try gentle stretching or reading',
                        'Keep bedroom cool (65-68°F / 18-20°C)',
                    ],
                ],
                'bedtime' => [
                    'title' => 'Consistent Sleep Schedule',
                    'time' => 'Same time daily',
                    'activities' => [
                        'Aim for 7-9 hours of sleep',
                        'Keep a consistent wake time',
                        'Avoid caffeine after 2pm',
                    ],
                ],
            ],
            'stress' => [
                'morning' => [
                    'title' => 'Mindful Start',
                    'time' => 'Upon waking',
                    'activities' => [
                        '5 minutes of deep breathing',
                        'Set one intention for the day',
                        'Avoid checking phone first thing',
                    ],
                ],
                'midday' => [
                    'title' => 'Stress Check-Ins',
                    'time' => 'Throughout day',
                    'activities' => [
                        'Take 3 deep breaths between tasks',
                        'Practice the 4-7-8 breathing technique',
                        'Take short walks after intense work',
                    ],
                ],
                'evening' => [
                    'title' => 'Wind-Down Ritual',
                    'time' => 'Evening',
                    'activities' => [
                        'Journal for 5-10 minutes',
                        'Progressive muscle relaxation',
                        'Gratitude practice',
                    ],
                ],
            ],
            'hydration' => [
                'morning' => [
                    'title' => 'Morning Hydration',
                    'time' => 'Upon waking',
                    'activities' => [
                        'Drink 16oz (500ml) of water',
                        'Add lemon if desired',
                        'Wait 30 min before coffee',
                    ],
                ],
                'daytime' => [
                    'title' => 'Regular Intake',
                    'time' => 'Throughout day',
                    'activities' => [
                        'Drink 8oz every 2 hours',
                        'More if exercising or hot weather',
                        'Keep water bottle visible',
                    ],
                ],
                'evening' => [
                    'title' => 'Evening Hydration',
                    'time' => 'Before bed',
                    'activities' => [
                        'Stop drinking 2 hours before bed',
                        'Monitor caffeine intake',
                        'Herbal teas are fine',
                    ],
                ],
            ],
            default => [
                'morning' => [
                    'title' => 'Morning Routine',
                    'time' => 'Upon waking',
                    'activities' => [
                        'Drink water before coffee',
                        'Get natural sunlight within 30 min',
                        '5 minutes of movement or stretching',
                    ],
                ],
                'midday' => [
                    'title' => 'Midday Check',
                    'time' => 'Lunchtime',
                    'activities' => [
                        'Take a 10-minute walk',
                        'Practice mindful eating',
                        'Stay hydrated',
                    ],
                ],
                'evening' => [
                    'title' => 'Evening Wind-Down',
                    'time' => 'Before bed',
                    'activities' => [
                        'Dim lights after sunset',
                        'Avoid screens 1 hour before bed',
                        'Keep consistent sleep schedule',
                    ],
                ],
            ],
        };

        return [
            'focus' => $focus,
            'routine' => $routines,
            'tips' => [
                'Start with one area and build gradually',
                'Track your progress to stay motivated',
                'Adjust timing to fit your schedule',
                'Be patient - habits take time to form',
            ],
        ];
    }
}
