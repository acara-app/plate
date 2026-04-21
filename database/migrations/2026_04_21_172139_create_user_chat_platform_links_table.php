<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_chat_platform_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('platform', 32);
            $table->string('platform_user_id')->nullable();
            $table->string('platform_chat_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->string('linking_token', 16)->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'platform_user_id']);
            $table->unique(['platform', 'linking_token']);
            $table->index('conversation_id');
        });
    }
};
