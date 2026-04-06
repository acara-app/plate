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
        Schema::table('health_sync_samples', function (Blueprint $table): void {
            $table->string('entry_source', 20)->nullable()->after('source');
            $table->text('notes')->nullable()->after('metadata');
            $table->uuid('group_id')->nullable()->after('notes');

            $table->dropUnique('health_sync_samples_unique');

            $table->index(['user_id', 'group_id']);
            $table->index(['user_id', 'entry_source', 'measured_at']);
        });

        DB::statement("
            CREATE UNIQUE INDEX health_sync_samples_mobile_unique
            ON health_sync_samples (user_id, type_identifier, measured_at)
            WHERE entry_source = 'mobile_sync'
        ");

        Schema::dropIfExists('health_entries');
    }
};
