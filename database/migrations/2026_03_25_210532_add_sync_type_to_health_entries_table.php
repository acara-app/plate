<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_entries', function (Blueprint $table): void {
            $table->string('sync_type', 50)->nullable()->after('notes');
            $table->unique(['user_id', 'sync_type', 'measured_at'], 'health_entries_sync_unique');
        });
    }
};
