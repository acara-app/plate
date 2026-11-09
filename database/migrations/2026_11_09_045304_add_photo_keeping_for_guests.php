<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_analysis_requests', function (Blueprint $table): void {
            $table->boolean('keep_photo')->default(false);
        });

        Schema::table('meal_photo_samples', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
