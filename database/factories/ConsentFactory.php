<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
final class ConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => ConsentPurpose::ImproveFoodRecognition,
            'version' => ConsentPurpose::ImproveFoodRecognition->version(),
            'granted' => true,
            'source' => ConsentSource::Settings,
        ];
    }
}
