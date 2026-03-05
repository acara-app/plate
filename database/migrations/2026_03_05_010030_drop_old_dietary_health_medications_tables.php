<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_profile_dietary_preference');
        Schema::dropIfExists('user_profile_health_condition');
        Schema::dropIfExists('user_medications');
        Schema::dropIfExists('dietary_preferences');
        Schema::dropIfExists('health_conditions');
    }
};
