<?php

declare(strict_types=1);

namespace App\Enums;

enum MealPhotoRoute: string
{
    case Web = 'health-entries.photo';
    case Api = 'api.v2.health-entries.photo';

    public function urlFor(string $groupId): string
    {
        return route($this->value, ['group' => $groupId], absolute: false);
    }
}
