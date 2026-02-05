<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('usda_sr_legacy_foods');
        Schema::dropIfExists('usda_foundation_foods');
    }
};
