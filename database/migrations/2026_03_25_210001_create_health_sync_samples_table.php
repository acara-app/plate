<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_sync_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mobile_sync_device_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type_identifier');
            $table->decimal('value', 12, 4);
            $table->string('unit', 20);
            $table->timestamp('measured_at');
            $table->string('source', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type_identifier', 'measured_at'], 'health_sync_samples_unique');
            $table->index(['user_id', 'measured_at']);
        });
    }
};
