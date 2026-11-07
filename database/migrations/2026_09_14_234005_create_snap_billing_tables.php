<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snap_photo_accounts', function (Blueprint $table): void {
            $table->string('subject')->primary();
            $table->string('owner')->nullable();
        });
        Schema::create('snap_photo_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('subject');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('allowance');
            $table->string('mode');
            $table->unique(['subject', 'starts_at', 'mode'], 'snap_period_unique');
        });
        Schema::create('snap_photo_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained('snap_photo_periods');
            $table->string('subject');
            $table->string('request_key', 64);
            $table->string('fingerprint', 64);
            $table->string('guest_id')->nullable()->index();
            $table->string('status');
            $table->uuid('attempt');
            $table->string('source');
            $table->text('result')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
            $table->unique(['subject', 'request_key']);
            $table->index(['period_id', 'status']);
        });
        Schema::create('snap_paid_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('subscription_id')->index();
            $table->string('customer_id');
            $table->string('invoice_id')->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
        });
        Schema::create('snap_analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('name');
            $table->json('properties');
            $table->timestamp('created_at');
        });
    }
};
