<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caffeine_drinks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->decimal('volume_oz', 8, 2)->nullable();
            $table->decimal('caffeine_mg', 8, 2);
            $table->string('source')->nullable();
            $table->string('license_url')->nullable();
            $table->string('attribution')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caffeine_drinks');
    }
};
