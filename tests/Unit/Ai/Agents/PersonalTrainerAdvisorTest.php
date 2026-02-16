<?php

declare(strict_types=1);

use App\Ai\Agents\PersonalTrainerAdvisor;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Contracts\Actions\GetsUserProfileContext;
use App\Enums\AgentMode;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    // Create a simple test implementation of the interface
    $this->profileContext = new class implements GetsUserProfileContext
    {
        public array $returnValue = [];

        public function handle(User $user): array
        {
            return $this->returnValue;
        }
    };

    // Use real tool instances
    $this->suggestWorkoutRoutine = new SuggestWorkoutRoutine;
    $this->getUserProfile = new GetUserProfile($this->profileContext);
    $this->getFitnessGoals = new GetFitnessGoals($this->profileContext);

    $this->agent = new PersonalTrainerAdvisor(
        $this->user,
        $this->profileContext,
        $this->suggestWorkoutRoutine,
        $this->getUserProfile,
        $this->getFitnessGoals
    );
});

it('generates instructions with profile context', function (): void {
    $this->profileContext->returnValue = ['context' => 'User wants to build muscle.'];

    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are an advanced AI Personal Trainer')
        ->toContain('USER PROFILE CONTEXT:')
        ->toContain('User wants to build muscle.')
        ->toContain('CHAT MODE: ask');
});

it('updates chat mode in instructions', function (): void {
    $this->profileContext->returnValue = ['context' => '...'];

    $this->agent->withMode(AgentMode::SuggestWorkoutRoutine);
    $instructions = $this->agent->instructions();

    expect($instructions)->toContain('CHAT MODE: suggest_workout_routine');
});

it('registers correct tools', function (): void {
    $tools = $this->agent->tools();

    expect($tools)->toHaveCount(3)
        ->and($tools[0])->toBeInstanceOf(SuggestWorkoutRoutine::class)
        ->and($tools[1])->toBeInstanceOf(GetUserProfile::class)
        ->and($tools[2])->toBeInstanceOf(GetFitnessGoals::class);
});
