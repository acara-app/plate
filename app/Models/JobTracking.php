<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property string $uuid
 * @property-read int $user_id
 * @property-read string $job_type
 * @property JobStatus $status
 * @property int $progress
 * @property string|null $message
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface|null $started_at
 * @property-read CarbonInterface|null $completed_at
 * @property-read CarbonInterface|null $failed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 */
final class JobTracking extends Model
{
    /** @use HasFactory<\Database\Factories\JobTrackingFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $guarded = [];

    public static function boot(): void
    {
        parent::boot();

        self::creating(function (JobTracking $jobTracking): void {
            if (! $jobTracking->uuid) {
                $jobTracking->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsStarted(): self
    {
        $this->update([
            'status' => JobStatus::Processing,
            'started_at' => now(),
        ]);

        return $this;
    }

    public function updateProgress(int $progress, ?string $message = null): self
    {
        $this->update([
            'progress' => $progress,
            'message' => $message,
        ]);

        return $this;
    }

    public function markAsCompleted(?string $message = null): self
    {
        $this->update([
            'status' => JobStatus::Completed,
            'progress' => 100,
            'message' => $message,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function markAsFailed(?string $message = null): self
    {
        $this->update([
            'status' => JobStatus::Failed,
            'message' => $message,
            'failed_at' => now(),
        ]);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'uuid' => 'string',
            'user_id' => 'integer',
            'job_type' => 'string',
            'status' => JobStatus::class,
            'progress' => 'integer',
            'message' => 'string',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
