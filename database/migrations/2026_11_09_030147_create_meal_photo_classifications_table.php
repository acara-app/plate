<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_photo_classifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_photo_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('provider');
            $table->string('model');
            $table->json('answers');
            $table->timestamps();
        });
    }
};
