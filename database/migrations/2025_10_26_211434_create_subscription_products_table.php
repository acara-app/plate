<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->double('price');
            $table->string('description')->nullable();
            $table->boolean('popular')->default(false);
            $table->string('stripe_price_id')->nullable();
            $table->string('billing_interval')->nullable();
            $table->string('product_group')->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->string('yearly_stripe_price_id')->nullable();
            $table->json('features')->nullable();
            $table->boolean('coming_soon')->default(false);
            $table->timestamps();
        });
    }
};
