<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DeletedUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $email
 * @property-read CarbonInterface $deleted_at
 */
final class DeletedUser extends Model
{
    /** @use HasFactory<DeletedUserFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'email' => 'string',
            'deleted_at' => 'datetime',
        ];
    }
}
