<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sync_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_identifier');
            $table->uuid('batch_id');
            $table->string('payload_hash', 64);
            $table->json('response');
            $table->json('affected_utc_dates');
            $table->timestamps();
            $table->unique(['user_id', 'device_identifier', 'batch_id'], 'mobile_sync_receipts_batch_unique');
        });
    }
};
