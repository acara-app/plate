<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tool_name');
            $table->string('event_name');
            $table->string('session_id')->nullable();
            $table->string('locale')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tool_name', 'event_name']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_events');
    }
};
