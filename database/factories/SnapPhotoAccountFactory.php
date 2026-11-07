<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SnapPhotoAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SnapPhotoAccount>
 */
final class SnapPhotoAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => 'guest:'.Str::uuid(),
            'owner' => null,
        ];
    }
}
