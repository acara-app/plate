<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SnapPhotoAccountFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $subject
 * @property string|null $owner
 */
#[WithoutIncrementing]
#[WithoutTimestamps]
final class SnapPhotoAccount extends Model
{
    /** @use HasFactory<SnapPhotoAccountFactory> */
    use HasFactory;

    protected $primaryKey = 'subject';

    protected $keyType = 'string';
}
