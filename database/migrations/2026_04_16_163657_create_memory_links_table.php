<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_links', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('source_memory_id')->constrained('memories')->cascadeOnDelete();
            $table->foreignUlid('target_memory_id')->constrained('memories')->cascadeOnDelete();
            $table->string('relationship')->default('related');
            $table->timestamps();

            $table->unique(['source_memory_id', 'target_memory_id', 'relationship'], 'memory_links_unique');
            $table->index('target_memory_id');
        });
    }
};
