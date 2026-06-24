<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->timestamp('kept_at')->nullable()->after('pinned_at');
        });

        DB::statement('DROP INDEX IF EXISTS agent_conversations_expiry_idx');
        DB::statement('CREATE INDEX IF NOT EXISTS agent_conversations_expiry_idx ON agent_conversations (updated_at) WHERE pinned_at IS NULL AND kept_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS agent_conversations_expiry_idx');

        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->dropColumn('kept_at');
        });

        DB::statement('CREATE INDEX IF NOT EXISTS agent_conversations_expiry_idx ON agent_conversations (updated_at) WHERE pinned_at IS NULL');
    }
};
