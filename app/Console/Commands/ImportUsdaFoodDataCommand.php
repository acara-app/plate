<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportUsdaFoodDataAction;
use Illuminate\Console\Command;

final class ImportUsdaFoodDataCommand extends Command
{
    protected $signature = 'import:usda-food-foundation-data {--path= : Path to the Foundation Food JSON file}';

    protected $description = 'Import USDA Foundation Food data into the database';

    public function __construct(
        private readonly ImportUsdaFoodDataAction $importAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->option('path') ?: storage_path('sources/FoodData_Central_foundation_food_json_2025-04-24 3.json');
        $table = 'usda_foundation_foods';

        if (! file_exists($path)) {
            $this->error("Foundation Foods file not found at: {$path}");

            return self::FAILURE;
        }

        $start = microtime(true);

        $this->importAction->handle($path, $table);

        $this->info(sprintf('✓ Foundation Foods imported in %.2fs', microtime(true) - $start));

        return self::SUCCESS;
    }
}
