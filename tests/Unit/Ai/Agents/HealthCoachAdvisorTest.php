<?php

declare(strict_types=1);

use App\Ai\Agents\HealthCoachAdvisor;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Contracts\Actions\GetsUserProfileContext;
use App\Enums\AgentMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

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
    $this->suggestWellnessRoutine = new SuggestWellnessRoutine;
    $this->getUserProfile = new GetUserProfile($this->profileContext);
    $this->getHealthGoals = new GetHealthGoals($this->profileContext);

    $this->agent = new HealthCoachAdvisor(
        $this->user,
        $this->profileContext,
        $this->suggestWellnessRoutine,
        $this->getUserProfile,
        $this->getHealthGoals
    );
});

it('generates instructions with profile context', function (): void {
    $this->profileContext->returnValue = ['context' => 'User has a goal to sleep better.'];

    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are an advanced AI Health Coach')
        ->toContain('USER PROFILE CONTEXT:')
        ->toContain('User has a goal to sleep better.')
        ->toContain('CHAT MODE: ask');
});

it('updates chat mode in instructions', function (): void {
    $this->profileContext->returnValue = ['context' => '...'];

    $this->agent->withMode(AgentMode::SuggestWellnessRoutine);
    $instructions = $this->agent->instructions();

    expect($instructions)->toContain('CHAT MODE: suggest_wellness_routine');
});

it('registers correct tools', function (): void {
    $tools = $this->agent->tools();

    expect($tools)->toHaveCount(3)
        ->and($tools[0])->toBeInstanceOf(SuggestWellnessRoutine::class)
        ->and($tools[1])->toBeInstanceOf(GetUserProfile::class)
        ->and($tools[2])->toBeInstanceOf(GetHealthGoals::class);
});

it('retrieves conversation history', function (): void {
    // specific to this user
    History::factory()->create([
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'My sleep is bad',
    ]);

    // different user (should be ignored)
    History::factory()->create([
        'user_id' => User::factory()->create()->id,
        'role' => 'user',
        'content' => 'Ignored message',
    ]);

    $messages = $this->agent->messages();

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[0]->content)->toBe('My sleep is bad');
});
