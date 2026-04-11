<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ContentMetaData;
use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Enums\PostCategory;
use BackedEnum;
use Carbon\CarbonInterface;
use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read int $id
 * @property-read ContentType $type
 * @property-read string $slug
 * @property-read string $title
 * @property-read array<string, mixed> $body
 * @property-read array{
 *     seo_title?: string,
 *     seo_description?: string,
 *     manual_links?: array<int, array{slug: string, anchor: string}>
 * }|null $meta_data
 * @property-read FoodCategory|PostCategory|null $category
 * @property-read string|null $image_path
 * @property-read string|null $image_url
 * @property-read ContentMetaData|null $meta
 * @property-read string $meta_title
 * @property-read string $meta_description
 * @property-read bool $is_published
 * @property-read string $locale
 * @property-read string|null $translation_group
 * @property-read string $display_name
 * @property-read string|null $diabetic_insight
 * @property-read array<string, float|int|null> $nutrition
 * @property-read string|null $glycemic_assessment
 * @property-read int $glycemic_index
 * @property-read string $glycemic_load
 * @property-read float $glycemic_load_numeric
 * @property-read string $category_label
 * @property-read array<int, array{slug: string, anchor: string}> $manual_links
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Content extends Model
{
    /** @use HasFactory<ContentFactory> */
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
            'body' => 'array',
            'meta_data' => 'array',
            'image_path' => 'string',
            'is_published' => 'boolean',
            'locale' => 'string',
            'translation_group' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Content, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'translation_group', 'translation_group');
    }

    protected function getCategoryAttribute(?string $value): FoodCategory|PostCategory|null
    {
        return match ($this->type) {
            ContentType::Food => $value ? FoodCategory::tryFrom($value) : null,
            ContentType::Post => $value ? PostCategory::tryFrom($value) : null,
            default => null,
        };
    }

    protected function setCategoryAttribute(FoodCategory|PostCategory|string|null $value): void
    {
        $this->attributes['category'] = $value instanceof BackedEnum ? $value->value : $value;
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

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, FoodCategory|PostCategory $category): void
    {
        $query->where('category', $category);
    }

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function post(Builder $query): void
    {
        $query->ofType(ContentType::Post);
    }

    /**
     * @param  Builder<Content>  $query
     */
    #[Scope]
    protected function inLocale(Builder $query, string $locale): void
    {
        $query->where('locale', $locale);
    }

    protected function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('s3_public')->url($this->image_path);
    }

    protected function getMetaAttribute(): ?ContentMetaData
    {
        $data = $this->meta_data;

        if ($data === null) {
            return null;
        }

        return new ContentMetaData(
            seoTitle: $data['seo_title'] ?? '',
            seoDescription: $data['seo_description'] ?? '',
            manualLinks: $data['manual_links'] ?? [],
        );
    }

    protected function getMetaTitleAttribute(): string
    {
        $meta = $this->meta;

        if ($meta === null) {
            return '';
        }

        return $meta->seoTitle;
    }

    protected function getMetaDescriptionAttribute(): string
    {
        $meta = $this->meta;

        if ($meta === null) {
            return '';
        }

        return $meta->seoDescription;
    }

    protected function getDisplayNameAttribute(): string
    {
        /** @var string $displayName */
        $displayName = $this->body['display_name'] ?? $this->title;

        return $displayName;
    }

    protected function getDiabeticInsightAttribute(): ?string
    {
        /** @var string|null $insight */
        $insight = $this->body['diabetic_insight'] ?? null;

        return $insight;
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function getNutritionAttribute(): array
    {
        /** @var array<string, float|int|null> $nutrition */
        $nutrition = $this->body['nutrition'] ?? [];

        return $nutrition;
    }

    protected function getGlycemicAssessmentAttribute(): ?string
    {
        /** @var string|null $assessment */
        $assessment = $this->body['glycemic_assessment'] ?? null;

        return $assessment;
    }

    protected function getGlycemicIndexAttribute(): int
    {
        /** @var int|null $storedGi */
        $storedGi = $this->body['glycemic_index'] ?? null;

        if ($storedGi !== null) {
            return $storedGi;
        }

        $category = $this->category;

        if ($category instanceof FoodCategory) {
            return $category->averageGlycemicIndex();
        }

        return 50;
    }

    protected function getGlycemicLoadAttribute(): string
    {
        /** @var string|null $load */
        $load = $this->body['glycemic_load'] ?? null;

        if ($load !== null) {
            return $load;
        }

        $numericGl = $this->glycemic_load_numeric;

        return match (true) {
            $numericGl <= 10 => 'low',
            $numericGl <= 19 => 'medium',
            default => 'high',
        };
    }

    protected function getGlycemicLoadNumericAttribute(): float
    {
        /** @var float|null $storedGl */
        $storedGl = $this->body['glycemic_load_numeric'] ?? null;

        if ($storedGl !== null) {
            return $storedGl;
        }

        $nutrition = $this->nutrition;
        $carbs = (float) ($nutrition['carbs'] ?? 0);
        $fiber = (float) ($nutrition['fiber'] ?? 0);
        $netCarbs = max(0, $carbs - $fiber);

        $gi = $this->glycemic_index;

        return round(($gi * $netCarbs) / 100, 1);
    }

    protected function getCategoryLabelAttribute(): string
    {
        return $this->category?->label() ?? 'Uncategorized';
    }

    /**
     * @return array<int, array{slug: string, anchor: string}>
     */
    protected function getManualLinksAttribute(): array
    {
        $metaData = $this->meta_data;

        if ($metaData === null) {
            return [];
        }

        /** @var array<int, array{slug: string, anchor: string}> $links */
        $links = $metaData['manual_links'] ?? [];

        return $links;
    }
}
