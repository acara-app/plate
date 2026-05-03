<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Support\Facades\Config;

final readonly class PremiumRolloutGate
{
    public function isActiveFor(?User $user = null): bool
    {
        if ((bool) Config::get('plate.enable_premium_upgrades', false)) {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ($this->isInAllowlist($user)) {
            return true;
        }

        return $this->isInRolloutPercentile($user);
    }

    public function reasonFor(?User $user = null): string
    {
        if ((bool) Config::get('plate.enable_premium_upgrades', false)) {
            return 'global_flag';
        }

        if (! $user instanceof User) {
            return 'disabled';
        }

        if ($this->isInAllowlist($user)) {
            return 'allowlist';
        }

        if ($this->isInRolloutPercentile($user)) {
            return 'percentile';
        }

        return 'disabled';
    }

    private function isInAllowlist(User $user): bool
    {
        /** @var array<int, string> $allowlist */
        $allowlist = (array) Config::get('plate.premium_rollout.allowlist', []);

        if ($allowlist === []) {
            return false;
        }

        $email = mb_strtolower((string) $user->email);
        $id = (string) $user->id;

        foreach ($allowlist as $entry) {
            $candidate = mb_strtolower((string) $entry);

            if ($candidate === '') {
                continue; // @codeCoverageIgnore
            }

            if ($candidate === $id || $candidate === $email) {
                return true;
            }
        }

        return false;
    }

    private function isInRolloutPercentile(User $user): bool
    {
        $percentage = (int) Config::get('plate.premium_rollout.percentage', 0);

        if ($percentage <= 0) {
            return false;
        }

        if ($percentage >= 100) {
            return true;
        }

        return (crc32((string) $user->id) % 100) < $percentage;
    }
}
