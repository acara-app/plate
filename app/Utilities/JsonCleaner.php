<?php

declare(strict_types=1);

namespace App\Utilities;

use InvalidArgumentException;

final class JsonCleaner
{
    public static function extractAndValidateJson(string $response): string
    {
        $response = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $response) ?? $response;

        $response = mb_trim($response);

        if (! str_starts_with($response, '{') && ! str_starts_with($response, '[')) {
            if (preg_match('/(\{.*\}|\[.*\])/s', $response, $matches)) {
                $response = $matches[1];
            } else {
                throw new InvalidArgumentException('No valid JSON found in AI response');
            }
        }

        $response = self::escapeUnescapedControlCharsInsideStrings($response);

        json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $response;
    }

    private static function escapeUnescapedControlCharsInsideStrings(string $json): string
    {
        $out = '';
        $inString = false;
        $escape = false;
        $length = mb_strlen($json, '8bit');

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];

            if ($escape) {
                $out .= $char;
                $escape = false;

                continue;
            }

            if ($char === '\\') {
                $out .= $char;
                $escape = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                $out .= $char;

                continue;
            }

            if ($inString && ord($char) < 0x20) {
                $out .= match ($char) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    "\f" => '\\f',
                    "\x08" => '\\b',
                    default => sprintf('\\u%04x', ord($char)),
                };

                continue;
            }

            $out .= $char;
        }

        return $out;
    }
}
