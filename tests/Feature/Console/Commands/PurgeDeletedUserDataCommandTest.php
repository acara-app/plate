<?php

declare(strict_types=1);

use App\Console\Commands\PurgeDeletedUserDataCommand;
use App\Models\Conversation;
use App\Models\DeletedUser;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

covers(PurgeDeletedUserDataCommand::class);

function snapMeteringFor(string $subject, ?string $owner = null): void
{
    DB::table('snap_photo_accounts')->insert(['subject' => $subject, 'owner' => $owner]);

    $periodId = DB::table('snap_photo_periods')->insertGetId([
        'subject' => $subject,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'allowance' => 2,
        'mode' => 'lifetime',
    ]);

    DB::table('snap_photo_requests')->insert([
        'period_id' => $periodId,
        'subject' => $subject,
        'request_key' => Str::random(20),
        'fingerprint' => hash('sha256', $subject),
        'status' => 'succeeded',
        'attempt' => Str::uuid()->toString(),
        'source' => 'test',
        'created_at' => now(),
        'expires_at' => now()->addHour(),
    ]);
}

it('purges orphaned data for users deleted over 30 days ago', function (string $userType): void {
    $user = User::factory()->create();
    $userId = $user->id;
    $userEmail = $user->email;

    $conversation = Conversation::factory()->forUser($user)->create();
    History::factory()->forConversation($conversation)->create();

    DB::table('sessions')->insert([
        'id' => fake()->uuid(),
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'test',
        'last_activity' => time(),
    ]);

    DB::table('notifications')->insert([
        'id' => fake()->uuid(),
        'type' => 'App\Notifications\Test',
        'notifiable_type' => $userType,
        'notifiable_id' => $userId,
        'data' => json_encode(['test' => true]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('personal_access_tokens')->insert([
        'tokenable_type' => $userType,
        'tokenable_id' => $userId,
        'name' => 'test-token',
        'token' => hash('sha256', 'test'),
        'abilities' => json_encode(['*']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $userEmail,
        'token' => 'test-token',
        'created_at' => now(),
    ]);

    $user->delete();

    DeletedUser::query()->create([
        'user_id' => $userId,
        'email' => $userEmail,
        'deleted_at' => now()->subDays(31),
    ]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseMissing('agent_conversations', ['participant_type' => 'user', 'participant_id' => $userId]);
    $this->assertDatabaseMissing('agent_conversation_messages', ['participant_type' => 'user', 'participant_id' => $userId]);
    $this->assertDatabaseMissing('sessions', ['user_id' => $userId]);
    $this->assertDatabaseMissing('notifications', ['notifiable_id' => $userId, 'notifiable_type' => $userType]);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $userId, 'tokenable_type' => $userType]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $userEmail]);
    $this->assertDatabaseMissing('deleted_users', ['user_id' => $userId]);
})->with(['short morph alias' => ['user'], 'full class name' => [User::class]]);

it('does not purge data for users deleted less than 30 days ago', function (): void {
    $user = User::factory()->create();
    $userId = $user->id;

    $conversation = Conversation::factory()->forUser($user)->create();

    $user->delete();

    DeletedUser::query()->create([
        'user_id' => $userId,
        'email' => $user->email,
        'deleted_at' => now()->subDays(15),
    ]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseHas('agent_conversations', ['participant_type' => 'user', 'participant_id' => $userId]);
    $this->assertDatabaseHas('deleted_users', ['user_id' => $userId]);
});

it('does not affect other users data', function (): void {
    $deletedUser = User::factory()->create();
    $deletedUserId = $deletedUser->id;

    $otherUser = User::factory()->create();
    $otherConversation = Conversation::factory()->forUser($otherUser)->create();

    $deletedUser->delete();

    DeletedUser::query()->create([
        'user_id' => $deletedUserId,
        'email' => $deletedUser->email,
        'deleted_at' => now()->subDays(31),
    ]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseHas('agent_conversations', ['participant_type' => 'user', 'participant_id' => $otherUser->id]);
    expect($otherUser->fresh())->not->toBeNull();
});

it('outputs message when no data to purge', function (): void {
    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->expectsOutput('No user data to purge.')
        ->assertSuccessful();
});

it('purges subscription records', function (): void {
    $user = User::factory()->create();
    $userId = $user->id;

    $subscriptionId = DB::table('subscriptions')->insertGetId([
        'user_id' => $userId,
        'type' => 'default',
        'stripe_id' => 'sub_test_'.fake()->uuid(),
        'stripe_status' => 'canceled',
        'stripe_price' => 'price_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('subscription_items')->insert([
        'subscription_id' => $subscriptionId,
        'stripe_id' => 'si_test_'.fake()->uuid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => 'price_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user->delete();

    DeletedUser::query()->create([
        'user_id' => $userId,
        'email' => $user->email,
        'deleted_at' => now()->subDays(31),
    ]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseMissing('subscriptions', ['user_id' => $userId]);
    $this->assertDatabaseMissing('subscription_items', ['subscription_id' => $subscriptionId]);
});

it('removes tokens and notifications left by accounts that no longer exist', function (): void {
    $active = User::factory()->create();
    DeletedUser::query()->create(['user_id' => 9001, 'email' => 'pending@example.com', 'deleted_at' => now()->subDays(5)]);

    foreach ([$active->id, 9001, 9002] as $userId) {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'user',
            'tokenable_id' => $userId,
            'name' => 'test-token',
            'token' => hash('sha256', "token-{$userId}"),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => fake()->uuid(),
            'type' => 'App\Notifications\Test',
            'notifiable_type' => 'user',
            'notifiable_id' => $userId,
            'data' => json_encode(['test' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->artisan(PurgeDeletedUserDataCommand::class)->assertSuccessful();

    expect(DB::table('personal_access_tokens')->pluck('tokenable_id')->all())->toEqualCanonicalizing([$active->id, 9001])
        ->and(DB::table('notifications')->pluck('notifiable_id')->all())->toEqualCanonicalizing([$active->id, 9001]);
});

it('removes scan usage records and Telegram chats of deleted accounts', function (): void {
    $active = User::factory()->create();
    $deleted = User::factory()->create();
    $deletedId = $deleted->id;

    snapMeteringFor("user:{$active->id}");
    snapMeteringFor('guest:unclaimed');
    snapMeteringFor("user:{$deletedId}");
    snapMeteringFor('guest:claimed', owner: "user:{$deletedId}");
    snapMeteringFor('user:9002');

    $botId = DB::table('telegraph_bots')->insertGetId(['token' => 'test-token', 'name' => 'Acara', 'created_at' => now(), 'updated_at' => now()]);

    foreach (['111' => $active, '222' => $deleted] as $chatId => $owner) {
        DB::table('telegraph_chats')->insert(['chat_id' => $chatId, 'name' => 'Chat', 'telegraph_bot_id' => $botId, 'created_at' => now()->subDays(40), 'updated_at' => now()->subDays(40)]);
        DB::table('user_chat_platform_links')->insert(['user_id' => $owner->id, 'platform' => 'telegram', 'platform_user_id' => $chatId, 'is_active' => true, 'linked_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    DB::table('telegraph_chats')->insert(['chat_id' => '333', 'name' => 'New chat', 'telegraph_bot_id' => $botId, 'created_at' => now(), 'updated_at' => now()]);

    $deleted->delete();
    DeletedUser::query()->create(['user_id' => $deletedId, 'email' => $deleted->email, 'deleted_at' => now()->subDays(31)]);

    $this->artisan(PurgeDeletedUserDataCommand::class)->assertSuccessful();

    $remaining = ["user:{$active->id}", 'guest:unclaimed'];

    expect(DB::table('snap_photo_accounts')->pluck('subject')->all())->toEqualCanonicalizing($remaining)
        ->and(DB::table('snap_photo_periods')->pluck('subject')->all())->toEqualCanonicalizing($remaining)
        ->and(DB::table('snap_photo_requests')->pluck('subject')->all())->toEqualCanonicalizing($remaining)
        ->and(DB::table('telegraph_chats')->pluck('chat_id')->all())->toEqualCanonicalizing(['111', '333']);
});
