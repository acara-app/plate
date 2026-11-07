<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Models\User;

interface BillsUsageToUser
{
    public function forModelUser(?User $user): static;

    public function modelUser(): ?User;
}
