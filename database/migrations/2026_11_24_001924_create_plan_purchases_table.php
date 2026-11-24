<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_purchases', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('subscription_product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('snap_journey_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_hash', 64)->index();
            $table->string('placement', 32);
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamp('checkout_returned_at')->nullable();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('email')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('claim_token_hash', 64)->nullable()->unique();
            $table->timestamp('claim_token_expires_at')->nullable();
            $table->string('claim_conflict', 32)->nullable();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->string('claim_method', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_purchases');
    }
};
