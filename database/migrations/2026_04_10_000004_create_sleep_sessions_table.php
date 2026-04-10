<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sleep_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('sample_uuid')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->string('stage', 24);
            $table->string('source', 100)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'sample_uuid'], 'sleep_sessions_hkuuid_unique');
            $table->index(['user_id', 'started_at', 'ended_at'], 'sleep_sessions_user_range');
        });
    }
};
