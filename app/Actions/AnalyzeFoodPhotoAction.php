<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\DataObjects\FoodAnalysisData;

final readonly class AnalyzeFoodPhotoAction
{
    public function __construct(
        private FoodPhotoAnalyzerAgent $agent,
    ) {}

    public function handle(string $imageBase64, string $mimeType): FoodAnalysisData
    {
        return $this->agent->analyze($imageBase64, $mimeType);
    }
}
