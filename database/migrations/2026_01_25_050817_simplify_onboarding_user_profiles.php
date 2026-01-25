<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('goal_choice')->nullable();
            $table->string('animal_product_choice')->nullable();
            $table->string('intensity_choice')->nullable();
            $table->string('calculated_diet_type')->nullable();
            $table->decimal('derived_activity_multiplier', 3, 2)->nullable();
            $table->boolean('needs_re_onboarding')->default(false);
        });
    }
};
