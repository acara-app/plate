<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use Carbon\CarbonInterface;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $user_id
 * @property ConsentPurpose $purpose
 * @property string $version
 * @property bool $granted
 * @property ConsentSource $source
 * @property-read User $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Consent extends Model
{
    /** @use HasFactory<ConsentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'purpose' => ConsentPurpose::class,
            'granted' => 'boolean',
            'source' => ConsentSource::class,
        ];
    }
}
