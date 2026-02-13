<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_telegram_chats', function (Blueprint $table): void {
            $table->json('pending_health_log')->nullable()->after('linked_at');
        });
    }
};
