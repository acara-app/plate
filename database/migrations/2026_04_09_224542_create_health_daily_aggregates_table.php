<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_daily_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type_identifier');
            $table->decimal('value_sum', 12, 4)->nullable();
            $table->decimal('value_avg', 12, 4)->nullable();
            $table->decimal('value_min', 12, 4)->nullable();
            $table->decimal('value_max', 12, 4)->nullable();
            $table->decimal('value_last', 12, 4)->nullable();
            $table->unsignedInteger('value_count')->default(0);
            $table->string('source_primary', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date', 'type_identifier']);
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type_identifier', 'date']);
        });
    }
};
