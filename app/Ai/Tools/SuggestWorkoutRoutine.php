<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class SuggestWorkoutRoutine implements Tool
{
    public function name(): string
    {
        return 'suggest_workout_routine';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Suggest personalized workout routines and exercise plans. Use this to help users with fitness goals, workout scheduling, exercise variety, and training programs.';
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
                'workout' => null,
            ]) ?: '{"error":"User not authenticated","workout":null}';
        }

        /** @var string $focus */
        $focus = $request['focus'] ?? 'general';
        /** @var string $fitnessLevel */
        $fitnessLevel = $request['fitness_level'] ?? 'intermediate';

        $workouts = $this->generateWorkouts($focus, $fitnessLevel);

        return json_encode([
            'success' => true,
            'focus' => $focus,
            'fitness_level' => $fitnessLevel,
            'workouts' => $workouts,
        ]) ?: '{"success":true}';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'focus' => $schema->string()
                ->enum(['general', 'strength', 'cardio', 'flexibility', 'hiit'])
                ->description('Which type of workout to focus on: general fitness, strength training, cardio, flexibility, or HIIT.')
                ->required(),
            'fitness_level' => $schema->string()
                ->enum(['beginner', 'intermediate', 'advanced'])
                ->description('User\'s fitness level to adjust workout intensity.')
                ->required(),
        ];
    }

    /**
     * @phpstan-ignore return.type
     */
    private function generateWorkouts(string $focus, string $fitnessLevel): array
    {
        $intensity = match ($fitnessLevel) {
            'beginner' => ['duration' => '20-30 min', 'rest' => '60-90 sec'],
            'intermediate' => ['duration' => '30-45 min', 'rest' => '45-60 sec'],
            'advanced' => ['duration' => '45-60 min', 'rest' => '30-45 sec'],
            // @codeCoverageIgnoreStart
            default => ['duration' => '30-45 min', 'rest' => '45-60 sec'],
            // @codeCoverageIgnoreEnd
        };
        $workouts = match ($focus) {
            'strength' => [
                'day_1' => [
                    'title' => 'Upper Body Strength',
                    'exercises' => [
                        'Push-ups: 3 sets of 8-12 reps',
                        'Dumbbell rows: 3 sets of 10-12 reps',
                        'Shoulder press: 3 sets of 10-12 reps',
                        'Bicep curls: 3 sets of 12 reps',
                        'Tricep dips: 3 sets of 10 reps',
                    ],
                ],
                'day_2' => [
                    'title' => 'Lower Body Strength',
                    'exercises' => [
                        'Squats: 4 sets of 10-12 reps',
                        'Lunges: 3 sets of 12 reps each leg',
                        'Deadlifts: 3 sets of 10 reps',
                        'Calf raises: 3 sets of 15 reps',
                        'Glute bridges: 3 sets of 12 reps',
                    ],
                ],
            ],
            'cardio' => [
                'day_1' => [
                    'title' => 'HIIT Cardio',
                    'exercises' => [
                        'Warm-up: 5 min jog',
                        '30 sec sprint / 30 sec walk x 10',
                        'Cool-down: 5 min walk',
                    ],
                    'duration' => '25-30 min',
                ],
                'day_2' => [
                    'title' => 'Steady State Cardio',
                    'exercises' => [
                        'Warm-up: 5 min light jog',
                        '20-30 min moderate pace run/jog',
                        'Cool-down: 5 min walk + stretching',
                    ],
                    'duration' => '35-45 min',
                ],
            ],
            'flexibility' => [
                'day_1' => [
                    'title' => 'Full Body Stretch',
                    'exercises' => [
                        'Neck rolls: 2 min',
                        'Shoulder stretches: 2 min',
                        'Hip flexor stretches: 3 min',
                        'Hamstring stretches: 3 min',
                        'Yoga poses: 10 min (downward dog, cobra, child\'s pose)',
                    ],
                    'duration' => '25-30 min',
                ],
            ],
            default => [
                'day_1' => [
                    'title' => 'Full Body Circuit',
                    'exercises' => [
                        'Warm-up: 5 min dynamic stretches',
                        'Jumping jacks: 3 sets of 30 sec',
                        'Bodyweight squats: 3 sets of 15 reps',
                        'Push-ups: 3 sets of 10 reps',
                        'Mountain climbers: 3 sets of 30 sec',
                        'Plank: 3 sets of 30-60 sec',
                        'Cool-down: 5 min stretching',
                    ],
                ],
                'day_2' => [
                    'title' => 'Active Recovery',
                    'exercises' => [
                        '15 min light walk or jog',
                        '10 min yoga or stretching',
                        '5 min foam rolling',
                    ],
                ],
            ],
        };

        return [
            'intensity' => $intensity,
            'schedule' => $workouts,
            'tips' => [
                'Always warm up before workouts',
                'Stay hydrated throughout',
                'Listen to your body and rest when needed',
                'Progress gradually - increase intensity by 10% per week',
                'Get at least 1 rest day per week',
            ],
        ];
    }
}
