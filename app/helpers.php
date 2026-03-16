<?php

declare(strict_types=1);

use Inertia\Inertia;

if (! function_exists('getMimeType')) {

    function getMimeType(string $filename): string
    {

        $mime_types = [

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'webp' => 'image/webp',
            'avif' => 'image/avif',

            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

            'map' => 'application/javascript',
        ];

        $parts = explode('.', $filename);
        $ext = mb_strtolower(array_pop($parts));

        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }

        return 'application/octet-stream';

    }
}

if (! function_exists('makeKey')) {
    /**
     * @param  string|array<int|string, mixed>  $key
     */
    function makeKey(string|array $key): string
    {
        if (is_array($key)) {
            $key = implode('|', $key);
        }

        if (mb_strlen($key) > 200) {
            return md5($key);
        }

        return $key;
    }
}

if (! function_exists('toast')) {
    /**
     * @param  string  $message  The message to display
     * @param  string  $type  The type of toast (e.g., 'success', 'error', 'info', 'warning')
     */
    function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash([
            'toast' => [
                'message' => $message,
                'type' => $type,
            ],
        ]);
    }
}

if (! function_exists('enable_premium_upgrades')) {
    function enable_premium_upgrades(): bool
    {
        return (bool) config('plate.enable_premium_upgrades', false);
    }
}
