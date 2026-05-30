<?php

declare(strict_types=1);

use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GroceryListAgent;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'App\Ai\Agents\NutritionAgent' => NutritionSpecialist::class,
            'App\Ai\Agents\HealthAgent' => HealthSpecialist::class,
            'App\Ai\Agents\FitnessAgent' => FitnessSpecialist::class,
            'App\Ai\Agents\GroceryListGeneratorAgent' => GroceryListAgent::class,
        ];

        foreach ($renames as $old => $new) {
            DB::table('ai_usages')->where('agent', $old)->update(['agent' => $new]);
        }
    }
};
