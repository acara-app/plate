<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MobileAuthNonceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $nonce_id
 * @property string $nonce
 * @property string $device_identifier
 * @property CarbonInterface $expires_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 *
 * @codeCoverageIgnore
 */
final class MobileAuthNonce extends Model
{
    /** @use HasFactory<MobileAuthNonceFactory> */
    use HasFactory;

    use MassPrunable;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->where('expires_at', '<', now());
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
