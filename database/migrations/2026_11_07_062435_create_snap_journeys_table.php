<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snap_journeys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('guest_hash', 64)->nullable()->index();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('device', 16);
            $table->string('acquisition_source')->nullable();
            $table->string('acquisition_medium')->nullable();
            $table->string('acquisition_campaign')->nullable();
            $table->boolean('internal')->default(false);
            $table->timestamp('started_at')->index();
            $table->timestamp('expires_at');
        });

        Schema::table('snap_analytics_events', function (Blueprint $table): void {
            $table->uuid('journey_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('snap_analytics_events', function (Blueprint $table): void {
            $table->dropIndex(['journey_id']);
        });

        Schema::table('snap_analytics_events', function (Blueprint $table): void {
            $table->dropColumn('journey_id');
        });

        Schema::dropIfExists('snap_journeys');
    }
};
