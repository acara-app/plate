<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table): void {
            $table->id();

            $table->string('type')->index();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('meta_title');
            $table->text('meta_description');
            $table->json('body');
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->string('category')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
