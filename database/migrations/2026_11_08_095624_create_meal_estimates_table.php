<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_estimates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('health_group_id');
            $table->string('source');
            $table->string('model_version')->nullable()->index();
            $table->json('original_prediction');
            $table->json('corrected_value')->nullable();
            $table->string('validation_status')->index();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'health_group_id']);
        });
    }
};
