<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class LogToolEvent
{
    /**
     * @var array<int, string>
     */
    public const array PII_KEYS = [
        'ip',
        'ip_address',
        'ipAddress',
        'user_agent',
        'userAgent',
        'ua',
    ];

    public const float WEIGHT_BUCKET_CAP_KG = 130.0;

    public const float WEIGHT_BUCKET_WIDTH_KG = 10.0;

    public const float SAFE_MG_BUCKET_CAP = 600.0;

    public const float SAFE_MG_BUCKET_WIDTH = 50.0;

    public const int CUPS_BUCKET_CAP = 10;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(string $toolName, string $eventName, array $properties = []): void
    {
        try {
            $sanitized = $this->stripPii($properties);

            $sessionId = $this->extractString($sanitized, 'session_id');
            $locale = $this->extractString($sanitized, 'locale');

            $bucketed = $this->bucket($sanitized);

            DB::table('tool_events')->insert([
                'tool_name' => $toolName,
                'event_name' => $eventName,
                'session_id' => $sessionId,
                'locale' => $locale,
                'properties' => json_encode($bucketed, JSON_THROW_ON_ERROR),
                'created_at' => CarbonImmutable::now(),
            ]);
        } catch (Throwable $throwable) {
            Log::warning('Failed to log tool event', [
                'tool_name' => $toolName,
                'event_name' => $eventName,
                'exception' => $throwable->getMessage(),
            ]);
        }
    }

    public function bucketWeight(mixed $weightKg): ?string
    {
        if (! is_numeric($weightKg)) {
            return null;
        }

        return $this->numericBucket(
            (float) $weightKg,
            self::WEIGHT_BUCKET_WIDTH_KG,
            self::WEIGHT_BUCKET_CAP_KG,
        );
    }

    public function bucketSafeMg(mixed $safeMg): ?string
    {
        if (! is_numeric($safeMg)) {
            return null;
        }

        return $this->numericBucket(
            (float) $safeMg,
            self::SAFE_MG_BUCKET_WIDTH,
            self::SAFE_MG_BUCKET_CAP,
        );
    }

    public function bucketCups(mixed $cups): ?string
    {
        if (! is_numeric($cups)) {
            return null;
        }

        $value = (int) floor((float) $cups);

        if ($value < 0) {
            return '<0';
        }

        if ($value >= self::CUPS_BUCKET_CAP) {
            return self::CUPS_BUCKET_CAP.'+';
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function stripPii(array $properties): array
    {
        foreach (self::PII_KEYS as $piiKey) {
            unset($properties[$piiKey]);
        }

        return $properties;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function extractString(array &$properties, string $key): ?string
    {
        if (! array_key_exists($key, $properties)) {
            return null;
        }

        $value = $properties[$key];
        unset($properties[$key]);

        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function bucket(array $properties): array
    {
        if (array_key_exists('weight', $properties)) {
            $properties['weight'] = $this->bucketWeight($properties['weight']);
        }

        if (array_key_exists('weight_kg', $properties)) {
            $properties['weight_kg'] = $this->bucketWeight($properties['weight_kg']);
        }

        if (array_key_exists('safe_mg', $properties)) {
            $properties['safe_mg'] = $this->bucketSafeMg($properties['safe_mg']);
        }

        if (array_key_exists('cups', $properties)) {
            $properties['cups'] = $this->bucketCups($properties['cups']);
        }

        return $properties;
    }

    private function numericBucket(float $value, float $width, float $cap): string
    {
        if ($value < 0.0) {
            return '<0';
        }

        if ($value >= $cap) {
            return ((int) $cap).'+';
        }

        $lower = (int) (floor($value / $width) * $width);
        $upper = (int) ($lower + $width - 1);

        return $lower.'-'.$upper;
    }
}
