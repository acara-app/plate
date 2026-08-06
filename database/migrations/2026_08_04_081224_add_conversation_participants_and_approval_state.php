<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const array LEGACY_INDEXES = [
        'agent_conversations_user_id_updated_at_index',
        'agent_conversation_messages_user_id_index',
        'agent_conversation_messages_conversation_id_user_id_updated_at_index',
        'agent_conversation_messages_conversation_id_user_id_updated_at_',
    ];

    public function up(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->string('participant_type')->nullable();
            $table->unsignedBigInteger('participant_id')->nullable();
        });

        Schema::table('agent_conversation_messages', function (Blueprint $table): void {
            $table->string('participant_type')->nullable();
            $table->unsignedBigInteger('participant_id')->nullable();
            $table->text('approval_state')->nullable();
        });

        foreach (['agent_conversations', 'agent_conversation_messages'] as $table) {
            DB::table($table)->update([
                'participant_type' => 'user',
                'participant_id' => DB::raw('user_id'),
            ]);
        }

        foreach (self::LEGACY_INDEXES as $index) {
            DB::statement('DROP INDEX IF EXISTS '.$index);
        }

        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });

        Schema::table('agent_conversation_messages', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });

        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->index(['participant_type', 'participant_id', 'updated_at'], 'participant_updated_at_index');
        });

        Schema::table('agent_conversation_messages', function (Blueprint $table): void {
            $table->index(['conversation_id', 'participant_type', 'participant_id', 'updated_at'], 'conversation_index');
            $table->index(['participant_type', 'participant_id'], 'participant_index');
        });

        Schema::dropIfExists('agent_approvals');
    }
};
