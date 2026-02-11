<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserTelegramChat;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

it('has correct fillable attributes', function (): void {
    $chat = new UserTelegramChat();

    expect($chat->getGuarded())->toBe([]);
});

it('has correct casts', function (): void {
    $chat = new UserTelegramChat();
    $casts = $chat->casts();

    expect($casts)
        ->toHaveKey('is_active', 'boolean')
        ->toHaveKey('linked_at', 'datetime')
        ->toHaveKey('token_expires_at', 'datetime')
        ->toHaveKey('created_at', 'datetime')
        ->toHaveKey('updated_at', 'datetime');
});

it('belongs to a user', function (): void {
    $chat = UserTelegramChat::factory()->create();

    expect($chat->user)->toBeInstanceOf(User::class);
});

it('belongs to a telegraph chat', function (): void {
    $bot = TelegraphBot::factory()->create();
    $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create();

    $chat = UserTelegramChat::factory()->create([
        'telegraph_chat_id' => $telegraphChat->id,
    ]);

    expect($chat->telegraphChat)->toBeInstanceOf(TelegraphChat::class)
        ->and($chat->telegraphChat->id)->toBe($telegraphChat->id);
});

it('returns null for telegraph chat when not set', function (): void {
    $chat = UserTelegramChat::factory()->create([
        'telegraph_chat_id' => null,
    ]);

    expect($chat->telegraphChat)->toBeNull();
});

describe('isTokenValid', function (): void {
    it('returns false when token is null', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linking_token' => null,
            'token_expires_at' => now()->addHours(24),
        ]);

        expect($chat->isTokenValid())->toBeFalse();
    });

    it('returns false when token_expires_at is null', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => null,
        ]);

        expect($chat->isTokenValid())->toBeFalse();
    });

    it('returns false when token has expired', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => now()->subHour(),
        ]);

        expect($chat->isTokenValid())->toBeFalse();
    });

    it('returns true when token is valid and not expired', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => now()->addHours(24),
        ]);

        expect($chat->isTokenValid())->toBeTrue();
    });

    it('returns true when token expires in the future', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => now()->addMinute(),
        ]);

        expect($chat->isTokenValid())->toBeTrue();
    });
});

describe('markAsLinked', function (): void {
    it('marks chat as active', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'is_active' => false,
        ]);

        $chat->markAsLinked();

        expect($chat->fresh()->is_active)->toBeTrue();
    });

    it('sets linked_at timestamp', function (): void {
        $chat = UserTelegramChat::factory()->create([
            'linked_at' => null,
        ]);

        $chat->markAsLinked();

        expect($chat->fresh()->linked_at)->not->toBeNull();
    });

    it('clears the linking token', function (): void {
        $chat = UserTelegramChat::factory()->withToken()->create();

        $chat->markAsLinked();

        expect($chat->fresh()->linking_token)->toBeNull();
    });

    it('clears the token expiration', function (): void {
        $chat = UserTelegramChat::factory()->withToken()->create();

        $chat->markAsLinked();

        expect($chat->fresh()->token_expires_at)->toBeNull();
    });

    it('performs all updates in one call', function (): void {
        $chat = UserTelegramChat::factory()->withToken()->create([
            'is_active' => false,
            'linked_at' => null,
        ]);

        $chat->markAsLinked();
        $fresh = $chat->fresh();

        expect($fresh->is_active)->toBeTrue()
            ->and($fresh->linked_at)->not->toBeNull()
            ->and($fresh->linking_token)->toBeNull()
            ->and($fresh->token_expires_at)->toBeNull();
    });
});

describe('generateToken', function (): void {
    it('generates an 8 character uppercase token', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $token = $chat->generateToken();

        expect($token)->toHaveLength(8)
            ->and($token)->toBe(mb_strtoupper($token));
    });

    it('stores the token on the model', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $token = $chat->generateToken();

        expect($chat->fresh()->linking_token)->toBe($token);
    });

    it('sets token expiration to 24 hours by default', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $chat->generateToken();

        $diffHours = $chat->fresh()->token_expires_at->diffInHours(now(), absolute: true);
        expect($diffHours)->toBeGreaterThanOrEqual(23)
            ->toBeLessThanOrEqual(25);
    });

    it('allows custom expiration time', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $chat->generateToken(48);

        $diffHours = $chat->fresh()->token_expires_at->diffInHours(now(), absolute: true);
        expect($diffHours)->toBeGreaterThanOrEqual(47)
            ->toBeLessThanOrEqual(49);
    });

    it('returns the generated token', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $token = $chat->generateToken();

        expect($token)->toBeString()
            ->and($token)->toHaveLength(8);
    });

    it('generates different tokens on subsequent calls', function (): void {
        $chat = UserTelegramChat::factory()->create();

        $token1 = $chat->generateToken();
        $token2 = $chat->generateToken();

        expect($token1)->not->toBe($token2);
    });
});

describe('scopes', function (): void {
    describe('active scope', function (): void {
        it('returns only active chats', function (): void {
            $activeChat = UserTelegramChat::factory()->create(['is_active' => true]);
            $inactiveChat = UserTelegramChat::factory()->create(['is_active' => false]);

            $results = UserTelegramChat::active()->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($activeChat->id);
        });

        it('returns no results when all chats are inactive', function (): void {
            UserTelegramChat::factory()->create(['is_active' => false]);
            UserTelegramChat::factory()->create(['is_active' => false]);

            $results = UserTelegramChat::active()->get();

            expect($results)->toBeEmpty();
        });
    });

    describe('linked scope', function (): void {
        it('returns only chats with telegraph_chat_id', function (): void {
            $bot = TelegraphBot::factory()->create();
            $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create();

            $linkedChat = UserTelegramChat::factory()->create([
                'telegraph_chat_id' => $telegraphChat->id,
            ]);
            $unlinkedChat = UserTelegramChat::factory()->create([
                'telegraph_chat_id' => null,
            ]);

            $results = UserTelegramChat::linked()->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($linkedChat->id);
        });
    });

    describe('pending scope', function (): void {
        it('returns chats without telegraph_chat_id but with token', function (): void {
            $bot = TelegraphBot::factory()->create();
            $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create();

            $pendingChat = UserTelegramChat::factory()->withToken()->create([
                'telegraph_chat_id' => null,
            ]);
            $linkedChat = UserTelegramChat::factory()->create([
                'telegraph_chat_id' => $telegraphChat->id,
                'linking_token' => 'ABC123',
            ]);
            $noTokenChat = UserTelegramChat::factory()->create([
                'telegraph_chat_id' => null,
                'linking_token' => null,
            ]);

            $results = UserTelegramChat::pending()->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($pendingChat->id);
        });

        it('excludes chats with telegraph_chat_id even if they have token', function (): void {
            $bot = TelegraphBot::factory()->create();
            $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create();

            UserTelegramChat::factory()->create([
                'telegraph_chat_id' => $telegraphChat->id,
                'linking_token' => 'ABC123',
            ]);

            $results = UserTelegramChat::pending()->get();

            expect($results)->toBeEmpty();
        });

        it('excludes chats without linking_token', function (): void {
            UserTelegramChat::factory()->create([
                'telegraph_chat_id' => null,
                'linking_token' => null,
            ]);

            $results = UserTelegramChat::pending()->get();

            expect($results)->toBeEmpty();
        });
    });
});
