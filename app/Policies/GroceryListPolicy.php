<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GroceryList;
use App\Models\User;

final class GroceryListPolicy
{
    public function view(User $user, GroceryList $groceryList): bool
    {
        return $user->id === $groceryList->user_id;
    }

    public function update(User $user, GroceryList $groceryList): bool
    {
        return $user->id === $groceryList->user_id;
    }
}
