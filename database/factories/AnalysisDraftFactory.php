<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisDraftSource;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalysisDraft>
 */
final class AnalysisDraftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token_hash' => AnalysisDraft::hashToken(Str::random(64)),
            'schema_version' => AnalysisDraft::SCHEMA_VERSION,
            'source' => AnalysisDraftSource::PublicSnapToTrack,
            'payload' => [
                'items' => [
                    [
                        'name' => 'Grilled chicken breast',
                        'calories' => 280.0,
                        'protein' => 35.0,
                        'carbs' => 0.0,
                        'fat' => 12.0,
                        'portion' => '1 breast (170g)',
                        'grams' => 170.0,
                        'match_name' => 'chicken breast grilled',
                        'provenance' => 'model',
                    ],
                    [
                        'name' => 'Steamed rice',
                        'calories' => 210.0,
                        'protein' => 4.0,
                        'carbs' => 45.0,
                        'fat' => 0.5,
                        'portion' => '1 cup (160g)',
                        'grams' => 160.0,
                        'match_name' => 'white rice cooked',
                        'provenance' => 'reference',
                    ],
                ],
                'total_calories' => 490.0,
                'total_protein' => 39.0,
                'total_carbs' => 45.0,
                'total_fat' => 12.5,
                'confidence' => 82,
                'analyzer_version' => 'gemini-3.5-flash/p3',
                'reference_release' => null,
            ],
            'user_id' => null,
            'claimed_at' => null,
            'consumed_at' => null,
            'health_group_id' => null,
            'expires_at' => now()->addMinutes(AnalysisDraft::TTL_MINUTES),
        ];
    }

    public function claimed(?User $user = null): self
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id ?? User::factory(),
            'claimed_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'created_at' => now()->subMinutes(90),
            'expires_at' => now()->subMinutes(30),
        ]);
    }

    public function consumed(): self
    {
        return $this->state(fn (): array => [
            'consumed_at' => now(),
            'health_group_id' => Str::uuid()->toString(),
        ]);
    }
}
