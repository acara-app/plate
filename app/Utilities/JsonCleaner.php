<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use JsonException;

final class JsonCleaner
{
    /**
     * Extract and validate JSON from AI response, handling common formatting issues
     */
    public static function extractAndValidateJson(string $response): string
    {
        $originalResponse = $response;

        $response = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $response) ?? $response;

        $response = mb_trim($response);

        if (! str_starts_with($response, '{') && ! str_starts_with($response, '[')) {
            if (preg_match('/(\{.*\}|\[.*\])/s', $response, $matches)) {
                $response = $matches[1];
            } else {
                Log::error('No valid JSON found in AI response', [
                    'original_response' => $originalResponse,
                    'cleaned_response' => $response,
                ]);
                throw new InvalidArgumentException('No valid JSON found in AI response');
            }
        }

        try {
            json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error('Invalid JSON in AI response', [
                'original_response' => $originalResponse,
                'cleaned_response' => $response,
                'json_error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $response;
    }
}
