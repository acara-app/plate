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
        Schema::create('grocery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grocery_list_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quantity');
            $table->string('category');
            $table->boolean('is_checked')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['grocery_list_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grocery_items');
    }
};
