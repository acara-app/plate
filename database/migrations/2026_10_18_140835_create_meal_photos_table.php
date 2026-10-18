<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analysis_draft_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('health_group_id')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->timestamps();

            $table->index(['user_id', 'health_group_id']);
        });
    }
};
