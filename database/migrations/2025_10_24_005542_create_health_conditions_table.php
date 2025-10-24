<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_conditions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->text('nutritional_impact')->nullable();
            $table->json('recommended_nutrients')->nullable();
            $table->json('nutrients_to_limit')->nullable();
            $table->timestamps();
        });
    }
};
