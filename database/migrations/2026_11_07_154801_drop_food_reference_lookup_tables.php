<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reference_foods');

        Schema::table('benchmark_runs', function (Blueprint $table): void {
            $table->dropColumn('reference_lookup_enabled');
        });
    }
};
