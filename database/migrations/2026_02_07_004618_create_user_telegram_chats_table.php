<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_telegram_chats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('telegraph_chat_id')->nullable()->constrained('telegraph_chats')->onDelete('cascade');
            $table->string('linking_token', 16)->unique()->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'telegraph_chat_id'])->whereNotNull('telegraph_chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_telegram_chats');
    }
};
