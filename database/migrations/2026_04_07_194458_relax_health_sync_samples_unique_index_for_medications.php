<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS health_sync_samples_mobile_unique');

        DB::statement("
            CREATE UNIQUE INDEX health_sync_samples_mobile_unique
            ON health_sync_samples (user_id, type_identifier, measured_at)
            WHERE entry_source = 'mobile_sync' AND type_identifier <> 'medication'
        ");
    }
};
