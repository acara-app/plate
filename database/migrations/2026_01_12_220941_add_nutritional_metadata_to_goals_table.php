<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->decimal('protein_ratio', 3, 2)->nullable()->after('description')->comment('Protein as decimal (e.g., 0.30 for 30%)');
            $table->decimal('carb_ratio', 3, 2)->nullable()->after('protein_ratio')->comment('Carbs as decimal');
            $table->decimal('fat_ratio', 3, 2)->nullable()->after('carb_ratio')->comment('Fat as decimal');
            $table->decimal('calorie_adjustment', 4, 2)->nullable()->after('fat_ratio')->comment('Calorie adjustment (e.g., -0.20 for 20% deficit)');
        });
    }
};
