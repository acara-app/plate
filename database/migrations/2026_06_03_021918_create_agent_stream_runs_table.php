<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_stream_runs', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('conversation_id', 36);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('agent');
            $table->string('channel', 25);
            $table->string('model');
            $table->string('status', 20)->default('running');
            $table->string('invocation_id')->nullable();
            $table->string('assistant_message_id', 36)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_stream_runs');
    }
};
