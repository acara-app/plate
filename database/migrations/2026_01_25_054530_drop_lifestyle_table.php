<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropForeign(['lifestyle_id']);
            $table->dropColumn('lifestyle_id');
        });

        Schema::dropIfExists('lifestyles');
    }

    public function down(): void
    {
        Schema::create('lifestyles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('activity_level')->nullable();
            $table->string('sleep_hours')->nullable();
            $table->string('occupation')->nullable();
            $table->text('description')->nullable();
            $table->float('activity_multiplier')->nullable();
            $table->timestamps();
        });

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->foreignId('lifestyle_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
