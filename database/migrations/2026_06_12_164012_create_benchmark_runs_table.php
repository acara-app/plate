<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('analyzer_version')->index();
            $table->boolean('reference_lookup_enabled');
            $table->boolean('smoke');
            $table->unsignedSmallInteger('repeats');
            $table->unsignedInteger('meal_count');
            $table->unsignedInteger('skipped_meals');
            $table->json('report');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_runs');
    }
};
