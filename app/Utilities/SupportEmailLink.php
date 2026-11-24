<?php

declare(strict_types=1);

namespace App\Utilities;

final class SupportEmailLink
{
    public const string ADDRESS = 'support@acara.app';

    /**
     * @param  list<string>  $lines
     */
    public static function make(string $subject, array $lines): string
    {
        return 'mailto:'.self::ADDRESS.'?'.http_build_query([
            'subject' => $subject,
            'body' => implode("\r\n", ['Hi Acara team,', '', ...$lines, '', 'Thanks!']),
        ], encoding_type: PHP_QUERY_RFC3986);
    }
}
