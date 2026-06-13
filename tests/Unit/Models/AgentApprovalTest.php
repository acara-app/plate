<?php

declare(strict_types=1);

use App\Enums\HealthEntryType;
use App\Services\AiTransparency;
use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

covers(AgentApproval::class);

it('persists with a uuid primary key and pending defaults', function (): void {
    $approval = AgentApproval::factory()->create();

    expect($approval->id)->toBeString()
        ->and(mb_strlen($approval->id))->toBe(36)
        ->and($approval->status)->toBe(AgentApprovalStatus::Pending);

    $this->assertDatabaseHas('agent_approvals', [
        'id' => $approval->id,
        'tool_name' => 'log_health_entry',
        'status' => 'pending',
    ]);
});

it('casts the status column to the enum', function (): void {
    $approval = AgentApproval::factory()->executed()->create();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Executed);
});

it('encrypts the payload at rest and decrypts it transparently', function (): void {
    $approval = AgentApproval::factory()->create([
        'payload' => ['log_type' => 'glucose', 'glucose_value' => 140],
    ]);

    expect($approval->fresh()->payload)->toBe(['log_type' => 'glucose', 'glucose_value' => 140]);

    $raw = DB::table('agent_approvals')->where('id', $approval->id)->value('payload');

    expect($raw)->toBeString()
        ->and($raw)->not->toContain('glucose_value')
        ->and($raw)->not->toContain('log_type');
});

it('belongs to a user and an optional conversation', function (): void {
    $conversation = Conversation::factory()->create();
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    expect($approval->user)->toBeInstanceOf(User::class)
        ->and($approval->user->id)->toBe($conversation->user_id)
        ->and($approval->conversation)->toBeInstanceOf(Conversation::class)
        ->and($approval->conversation->id)->toBe($conversation->id);
});

it('scopes to pending approvals', function (): void {
    AgentApproval::factory()->create();
    AgentApproval::factory()->executed()->create();
    AgentApproval::factory()->rejected()->create();

    expect(AgentApproval::query()->pending()->count())->toBe(1);
});

it('scopes to stale pending approvals past their expiry', function (): void {
    AgentApproval::factory()->stale()->create();
    AgentApproval::factory()->create();
    AgentApproval::factory()->executed()->create(['expires_at' => now()->subDay()]);

    expect(AgentApproval::query()->stale()->count())->toBe(1);
});

it('builds generic card data from the summary and status', function (): void {
    $pending = AgentApproval::factory()->create(['summary' => '4 eggs (~310 kcal) this evening']);

    $card = $pending->toCardData();

    expect($card->status)->toBe('pending')
        ->and($card->summary)->toBe('4 eggs (~310 kcal) this evening')
        ->and($card->canApprove)->toBeTrue()
        ->and($card->canReject)->toBeTrue();

    expect(AgentApproval::factory()->executed()->create()->toCardData()->canApprove)->toBeFalse();
});

it('includes the carb boundary notice on food entry cards', function (): void {
    $approval = AgentApproval::factory()->create([
        'payload' => ['log_type' => HealthEntryType::Food->value, 'calories' => 310],
        'summary' => '4 eggs (~310 kcal) this evening',
    ]);

    expect($approval->toCardData()->notice)->toBe(AiTransparency::carbBoundaryNotice());
});

it('omits the boundary notice on non-food entry cards', function (): void {
    expect(AgentApproval::factory()->create()->toCardData()->notice)->toBeNull();
});
