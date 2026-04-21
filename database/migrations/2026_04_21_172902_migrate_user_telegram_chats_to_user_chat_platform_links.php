<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_telegram_chats')) {
            return;
        }

        DB::transaction(function (): void {
            $rows = DB::table('user_telegram_chats as utc')
                ->leftJoin('telegraph_chats as tc', 'tc.id', '=', 'utc.telegraph_chat_id')
                ->select([
                    'utc.user_id',
                    'utc.conversation_id',
                    'utc.linking_token',
                    'utc.token_expires_at',
                    'utc.is_active',
                    'utc.linked_at',
                    'utc.created_at',
                    'utc.updated_at',
                    'tc.chat_id as telegraph_external_chat_id',
                ])
                ->orderBy('utc.id')
                ->get();

            $seenPlatformUserIds = [];
            $seenTokens = [];

            foreach ($rows as $row) {
                $externalChatId = $row->telegraph_external_chat_id;
                $platformUserId = $externalChatId === null ? null : (string) (is_scalar($externalChatId) ? $externalChatId : '');

                $rawToken = $row->linking_token;
                $token = is_string($rawToken) ? $rawToken : null;

                if ($platformUserId !== null && $platformUserId !== '') {
                    if (isset($seenPlatformUserIds[$platformUserId])) {
                        continue;
                    }

                    $seenPlatformUserIds[$platformUserId] = true;
                }

                if ($token !== null) {
                    if (isset($seenTokens[$token])) {
                        $token = null;
                    } else {
                        $seenTokens[$token] = true;
                    }
                }

                DB::table('user_chat_platform_links')->insert([
                    'user_id' => $row->user_id,
                    'platform' => 'telegram',
                    'platform_user_id' => $platformUserId,
                    'platform_chat_id' => null,
                    'conversation_id' => $row->conversation_id,
                    'linking_token' => $token,
                    'token_expires_at' => $row->token_expires_at,
                    'is_active' => (bool) $row->is_active,
                    'linked_at' => $row->linked_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });
    }
};
