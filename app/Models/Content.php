<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read ContentType $type
 * @property-read string $slug
 * @property-read string $title
 * @property-read string $meta_title
 * @property-read string $meta_description
 * @property-read array<string, mixed> $body
 * @property-read string|null $image_path
 * @property-read bool $is_published
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Content extends Model
{
    /** @use HasFactory<\Database\Factories\ContentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'type' => ContentType::class,
            'slug' => 'string',
            'title' => 'string',
            'meta_title' => 'string',
            'meta_description' => 'string',
            'body' => 'array',
            'image_path' => 'string',
            'is_published' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, ContentType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function food(Builder $query): void
    {
        $query->ofType(ContentType::Food);
    }

    protected function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('s3_public')->url($this->image_path);
    }

    protected function getDisplayNameAttribute(): string
    {
        return $this->body['display_name'] ?? $this->title;
    }

    protected function getDiabeticInsightAttribute(): ?string
    {
        return $this->body['diabetic_insight'] ?? null;
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function getNutritionAttribute(): array
    {
        return $this->body['nutrition'] ?? [];
    }

    protected function getGlycemicAssessmentAttribute(): ?string
    {
        return $this->body['glycemic_assessment'] ?? null;
    }

    protected function getGlycemicLoadAttribute(): ?string
    {
        return $this->body['glycemic_load'] ?? null;
    }
}
