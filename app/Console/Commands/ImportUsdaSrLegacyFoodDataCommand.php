<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportUsdaFoodDataAction;
use Illuminate\Console\Command;

final class ImportUsdaSrLegacyFoodDataCommand extends Command
{
    protected $signature = 'import:usda-sr-legacy-food-data {--path= : Path to the SR Legacy Food JSON file}';

    protected $description = 'Import USDA SR Legacy Food data into the database';

    public function __construct(
        private readonly ImportUsdaFoodDataAction $importAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->option('path') ?: storage_path('sources/FoodData_Central_sr_legacy_food_json_2018-04.json');
        $table = 'usda_sr_legacy_foods';

        if (! file_exists($path)) {
            $this->error("SR Legacy Foods file not found at: {$path}");

            return self::FAILURE;
        }

        $this->info('Importing SR Legacy Foods...');
        $start = microtime(true);

        $this->importAction->handle($path, $table);

        $this->info(sprintf('✓ SR Legacy Foods imported in %.2fs', microtime(true) - $start));

        return self::SUCCESS;
    }
}
