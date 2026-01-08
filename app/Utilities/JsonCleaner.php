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

        // Remove markdown code blocks
        $response = preg_replace('/```(?:json)?\\s*(.*?)\\s*```/s', '$1', $response) ?? $response;

        $response = mb_trim($response);

        // Try to find and validate JSON
        if (! str_starts_with($response, '{') && ! str_starts_with($response, '[')) {
            $response = self::extractLastValidJson($response) ?? $response;
        }

        // If still not valid JSON or there are multiple JSON blocks, try to extract the last valid one
        try {
            json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $extractedJson = self::extractLastValidJson($response);
            if ($extractedJson !== null) {
                $response = $extractedJson;
            } else {
                Log::error('Invalid JSON in AI response', [
                    'original_response' => $originalResponse,
                    'cleaned_response' => $response,
                    'json_error' => 'Syntax error',
                ]);
                throw new JsonException('Syntax error');
            }
        }

        return $response;
    }

    /**
     * Extract the last valid JSON object from a string that may contain multiple JSON blocks.
     *
     * This is useful when AI returns multiple JSON objects (like tool call simulations
     * followed by the actual result).
     */
    private static function extractLastValidJson(string $response): ?string
    {
        // Find all potential JSON object boundaries
        preg_match_all('/\\{(?:[^{}]|(?R))*\\}/s', $response, $matches);

        if (empty($matches[0])) {
            return null;
        }

        // Try each match from last to first, return the first valid one with expected keys
        $blocks = $matches[0];

        foreach (array_reverse($blocks) as $block) {
            try {
                $decoded = json_decode($block, true, 512, JSON_THROW_ON_ERROR);

                // Prefer blocks that have typical SEO content keys
                if (is_array($decoded) && (
                    isset($decoded['display_name']) ||
                    isset($decoded['h1_title']) ||
                    isset($decoded['meta_title'])
                )) {
                    return $block;
                }
            } catch (JsonException) {
                continue;
            }
        }

        // If no SEO block found, just return the last valid JSON
        foreach (array_reverse($blocks) as $block) {
            try {
                json_decode($block, true, 512, JSON_THROW_ON_ERROR);

                return $block;
            } catch (JsonException) {
                continue;
            }
        }

        return null;
    }
}
