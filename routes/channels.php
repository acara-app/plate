<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\ChatChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn (User $user, string $id): bool => $user->id === (int) $id);

Broadcast::channel(ChatChannel::PATTERN, fn (User $user, int $userId): bool => $user->id === $userId);
