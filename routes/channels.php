<?php

declare(strict_types=1);

use App\Services\ChatChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);

Broadcast::channel(ChatChannel::PATTERN, fn ($user, int $userId): bool => (int) $user->id === $userId);
