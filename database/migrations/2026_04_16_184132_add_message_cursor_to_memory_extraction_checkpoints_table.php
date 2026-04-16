<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memory_extraction_checkpoints', function (Blueprint $table): void {
            $table->string('last_extracted_message_id', 36)->nullable()->after('last_extracted_at');
            $table->timestamp('last_extracted_message_at')->nullable()->after('last_extracted_message_id');
            $table->index(['user_id', 'last_extracted_message_at']);
        });
    }
};
