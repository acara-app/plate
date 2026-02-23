<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent');
            $table->string('model');
            $table->string('provider');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cache_read_input_tokens')->default(0);
            $table->unsignedInteger('reasoning_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->timestamps();
        });
    }
};
