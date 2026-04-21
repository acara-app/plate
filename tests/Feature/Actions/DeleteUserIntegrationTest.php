<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Console\Commands\PurgeDeletedUserDataCommand;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\HealthSyncSample;
use App\Models\History;
use App\Models\MealPlan;
use App\Models\MobileSyncDevice;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

covers(DeleteUser::class);

it('deletes user and eventually purges all related data', function (): void {
    $user = User::factory()->create(['password' => 'password']);
    $userId = $user->id;
    $userEmail = $user->email;

    $profile = UserProfile::factory()->create(['user_id' => $userId]);

    $mealPlan = MealPlan::factory()->create(['user_id' => $userId]);
    $groceryList = GroceryList::factory()->create([
        'user_id' => $userId,
        'meal_plan_id' => $mealPlan->id,
    ]);
    GroceryItem::factory()->create(['grocery_list_id' => $groceryList->id]);

    HealthSyncSample::factory()->bloodGlucose()->count(3)->create(['user_id' => $userId]);
    HealthSyncSample::factory()->heartRate()->count(2)->create(['user_id' => $userId]);

    $device = MobileSyncDevice::factory()->paired()->create(['user_id' => $userId]);
    UserChatPlatformLink::factory()->create(['user_id' => $userId]);

    $conversation = Conversation::factory()->forUser($user)->create();
    History::factory()->forConversation($conversation)->userMessage()->count(2)->create();
    History::factory()->forConversation($conversation)->assistantMessage()->create();
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id]);

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

    DB::table('sessions')->insert([
        'id' => fake()->uuid(),
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'TestAgent',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    DB::table('notifications')->insert([
        'id' => fake()->uuid(),
        'type' => 'App\Notifications\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $userId,
        'data' => json_encode(['message' => 'test']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('personal_access_tokens')->insert([
        'tokenable_type' => User::class,
        'tokenable_id' => $userId,
        'name' => 'test-token',
        'token' => hash('sha256', 'test-token-value'),
        'abilities' => json_encode(['*']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $userEmail,
        'token' => 'test-reset-token',
        'created_at' => now(),
    ]);

    $aiUsage = AiUsage::factory()->create(['user_id' => $userId]);

    resolve(DeleteUser::class)->handle($user);

    $this->assertDatabaseMissing('users', ['id' => $userId]);

    $this->assertDatabaseHas('deleted_users', ['user_id' => $userId, 'email' => $userEmail]);

    $this->assertDatabaseMissing('user_profiles', ['user_id' => $userId]);
    $this->assertDatabaseMissing('meal_plans', ['user_id' => $userId]);
    $this->assertDatabaseMissing('grocery_lists', ['user_id' => $userId]);
    $this->assertDatabaseMissing('grocery_items', ['grocery_list_id' => $groceryList->id]);
    $this->assertDatabaseMissing('health_sync_samples', ['user_id' => $userId]);
    $this->assertDatabaseMissing('mobile_sync_devices', ['user_id' => $userId]);
    $this->assertDatabaseMissing('user_chat_platform_links', ['user_id' => $userId]);

    $this->assertDatabaseHas('agent_conversations', ['user_id' => $userId]);
    $this->assertDatabaseHas('agent_conversation_messages', ['user_id' => $userId]);
    $this->assertDatabaseHas('conversation_summaries', ['conversation_id' => $conversation->id]);
    $this->assertDatabaseHas('subscriptions', ['user_id' => $userId]);
    $this->assertDatabaseHas('subscription_items', ['subscription_id' => $subscriptionId]);
    $this->assertDatabaseHas('sessions', ['user_id' => $userId]);
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $userId, 'notifiable_type' => User::class]);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $userId, 'tokenable_type' => User::class]);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $userEmail]);

    expect($aiUsage->fresh()->user_id)->toBeNull();

    DB::table('deleted_users')
        ->where('user_id', $userId)
        ->update(['deleted_at' => now()->subDays(31)]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseMissing('agent_conversations', ['user_id' => $userId]);
    $this->assertDatabaseMissing('agent_conversation_messages', ['user_id' => $userId]);
    $this->assertDatabaseMissing('conversation_summaries', ['conversation_id' => $conversation->id]);
    $this->assertDatabaseMissing('subscriptions', ['user_id' => $userId]);
    $this->assertDatabaseMissing('subscription_items', ['subscription_id' => $subscriptionId]);
    $this->assertDatabaseMissing('sessions', ['user_id' => $userId]);
    $this->assertDatabaseMissing('notifications', ['notifiable_id' => $userId, 'notifiable_type' => User::class]);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $userId, 'tokenable_type' => User::class]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $userEmail]);
    $this->assertDatabaseMissing('deleted_users', ['user_id' => $userId]);

    expect($aiUsage->fresh())->not->toBeNull();
});

it('does not affect another user when deleting and purging', function (): void {
    $userToDelete = User::factory()->create();
    $deleteUserId = $userToDelete->id;

    $otherUser = User::factory()->create();

    UserProfile::factory()->create(['user_id' => $deleteUserId]);
    UserProfile::factory()->create(['user_id' => $otherUser->id]);

    Conversation::factory()->forUser($userToDelete)->create();
    $otherConversation = Conversation::factory()->forUser($otherUser)->create();
    History::factory()->forConversation($otherConversation)->create();

    HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $deleteUserId]);
    HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $otherUser->id]);

    DB::table('sessions')->insert([
        ['id' => fake()->uuid(), 'user_id' => $deleteUserId, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'a', 'last_activity' => time()],
        ['id' => fake()->uuid(), 'user_id' => $otherUser->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'b', 'last_activity' => time()],
    ]);

    resolve(DeleteUser::class)->handle($userToDelete);

    DB::table('deleted_users')
        ->where('user_id', $deleteUserId)
        ->update(['deleted_at' => now()->subDays(31)]);

    $this->artisan(PurgeDeletedUserDataCommand::class)
        ->assertSuccessful();

    expect($otherUser->fresh())->not->toBeNull();
    $this->assertDatabaseHas('user_profiles', ['user_id' => $otherUser->id]);
    $this->assertDatabaseHas('health_sync_samples', ['user_id' => $otherUser->id]);
    $this->assertDatabaseHas('agent_conversations', ['user_id' => $otherUser->id]);
    $this->assertDatabaseHas('agent_conversation_messages', ['user_id' => $otherUser->id]);
    $this->assertDatabaseHas('sessions', ['user_id' => $otherUser->id]);
});
