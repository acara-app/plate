<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usda_foundation_foods', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->text('description');
            $table->string('food_category')->nullable();
            $table->date('publication_date')->nullable();
            $table->json('nutrients');
            $table->timestamps();

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText('description');
            }
        });

        Schema::create('usda_sr_legacy_foods', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->text('description');
            $table->string('food_category')->nullable();
            $table->date('publication_date')->nullable();
            $table->json('nutrients');
            $table->timestamps();

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText('description');
            }
        });
    }
};
