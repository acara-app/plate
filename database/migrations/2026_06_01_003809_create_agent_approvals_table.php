<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_approvals', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_id', 36)->nullable();
            $table->string('tool_name');
            $table->string('channel')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('payload');
            $table->text('summary')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('conversation_id');
        });
    }
};
