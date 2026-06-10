<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Contracts\Skills\LoadsSkills;
use App\Data\Skills\SkillContent;
use App\Data\Skills\SkillSummary;
use App\Models\User;
use App\Services\Skills\NullSkillLoader;
use Illuminate\Support\Collection;

covers(AgentBuilder::class);

it('omits the skills registry block when the null loader is bound', function (): void {
    app()->instance(LoadsSkills::class, new NullSkillLoader);

    $user = User::factory()->create();
    $request = new AgentRequest(
        message: 'Hello',
    );

    $builder = resolve(AgentBuilder::class);
    $instructions = $builder->buildInstructions($request, $user);

    expect($instructions)
        ->not->toContain('## Available Skills')
        ->not->toContain('activate_skill');
});

it('renders the skills registry block when a loader returns skills', function (): void {
    app()->instance(LoadsSkills::class, new class implements LoadsSkills
    {
        public function loadAll(): Collection
        {
            return collect([
                new SkillSummary(name: 'weightloss-analyzer', description: 'Analyze weight-management data.'),
                new SkillSummary(name: 'nutrition-analyzer', description: 'Review nutrition quality.'),
            ]);
        }

        public function loadByName(string $name): ?SkillContent
        {
            return null;
        }
    });

    $user = User::factory()->create();
    $request = new AgentRequest(
        message: 'Hello',
    );

    $builder = resolve(AgentBuilder::class);
    $instructions = $builder->buildInstructions($request, $user);

    expect($instructions)
        ->toContain('## Available Skills')
        ->toContain('`weightloss-analyzer`')
        ->toContain('Analyze weight-management data.')
        ->toContain('`nutrition-analyzer`')
        ->toContain('activate_skill');
});

it('keeps the null skill loader available as a community-safe fallback', function (): void {
    expect(class_exists(NullSkillLoader::class))->toBeTrue();
    expect(new NullSkillLoader)->toBeInstanceOf(LoadsSkills::class);
});
