<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifestyles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('activity_level');
            $table->string('sleep_hours')->nullable();
            $table->string('occupation')->nullable();
            $table->text('description')->nullable();
            $table->decimal('activity_multiplier', 3, 2)->default(1.00);
            $table->timestamps();
        });
    }
};
