<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_products', function (Blueprint $table): void {
            $table->string('stripe_lookup_key')->nullable()->after('stripe_price_id');
            $table->string('yearly_stripe_lookup_key')->nullable()->after('yearly_stripe_price_id');
        });
    }
};
