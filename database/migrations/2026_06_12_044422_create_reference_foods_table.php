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
        if (DB::getDriverName() === 'pgsql') {
            Schema::ensureVectorExtensionExists();
        }

        Schema::create('reference_foods', function (Blueprint $table): void {
            $table->id();
            $table->string('source');
            $table->string('external_id');
            $table->string('data_type');
            $table->text('description');
            $table->string('match_name')->index();
            $table->string('food_category')->nullable();
            $table->decimal('calories_per_100g', 8, 2)->nullable();
            $table->decimal('protein_per_100g', 8, 2)->nullable();
            $table->decimal('carbs_per_100g', 8, 2)->nullable();
            $table->decimal('fat_per_100g', 8, 2)->nullable();
            $table->json('nutrients');

            if (DB::getDriverName() === 'pgsql') {
                $dimensions = config()->integer('plate.food_photo_analyzer.reference_lookup.embeddings.dimensions', 1536);
                $table->vector('embedding', $dimensions)->nullable()->index();
            } else {
                $table->json('embedding')->nullable();
            }

            $table->string('release');
            $table->date('publication_date')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText('description');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_foods');
    }
};
