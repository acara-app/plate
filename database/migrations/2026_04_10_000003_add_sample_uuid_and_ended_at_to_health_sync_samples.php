<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_sync_samples', function (Blueprint $table): void {
            $table->uuid('sample_uuid')->nullable();
            $table->timestamp('ended_at')->nullable();
        });

        Schema::table('health_sync_samples', function (Blueprint $table): void {
            $table->unique(['user_id', 'sample_uuid'], 'health_sync_samples_hkuuid_unique');
        });
    }
};
