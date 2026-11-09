<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_photo_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_photo_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('version');
            $table->json('answers');
            $table->timestamps();
        });
    }
};
