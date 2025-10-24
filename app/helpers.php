<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Support\Header;

if (! function_exists('isProduction')) {
    function isProduction(): bool
    {
        return app()->environment('production');
    }
}

if (! function_exists('isLocal')) {
    function isLocal(): bool
    {
        return app()->environment('local');
    }
}

if (! function_exists('enumValues')) {
    function enumValues($enumStr): Illuminate\Support\Collection
    {
        return collect($enumStr::cases())->map->value;
    }
}

if (! function_exists('paginateFromRequest')) {
    function paginateFromRequest(
        Builder $query,
        int $perPage = 25,
    ): Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $perPage = request()->query('perPage', $perPage);
        $page = request()->query('page');

        return $query->paginate(perPage: (int) $perPage, page: (int) $page);
    }
}

if (! function_exists('appendUniqueIdToFilename')) {
    /**
     * makes a filename unique by appending a random string to the end
     * e.g. photo1.png becomes photo1_aCjs2dL.png
     */
    function appendUniqueIdToFilename(UploadedFile $file): string
    {
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        return str($filename)
            ->when($extension, fn ($str) => $str->replaceLast(".{$extension}", ''))
            ->slug('_')
            ->append('_'.Str::random(8))
            ->when($extension, fn ($str) => $str->append(".{$extension}"))
            ->toString();
    }
}

if (! function_exists('makeFilenameUniqueFromUrl')) {
    /**
     * makes a filename unique by appending a random string to the end
     *e.g. photo1.png becomes photo1_aCjs2dL.png
     */
    function makeFilenameUniqueFromUrl(string $remoteUrl): string
    {

        $extension = extensionFromUrl($remoteUrl);
        $filename = basename($remoteUrl);

        return str($filename)
            ->when($extension, fn ($str) => $str->replaceLast(".{$extension}", ''))
            ->slug('_')
            ->append('_'.Str::random(8))
            ->when($extension, fn ($str) => $str->append(".{$extension}"))
            ->toString();
    }
}

if (! function_exists('extensionFromUrl')) {
    function extensionFromUrl(string $url): string
    {
        return pathinfo($url, PATHINFO_EXTENSION) !== [] && (pathinfo($url, PATHINFO_EXTENSION) !== '' && pathinfo($url, PATHINFO_EXTENSION) !== '0') ? pathinfo($url, PATHINFO_EXTENSION) : 'jpg';
    }
}

if (! function_exists('removeHyphenAndCapitalize')) {
    function removeHyphenAndCapitalize($string): string
    {
        return ucwords(str_replace('-', ' ', (string) $string));
    }
}

if (! function_exists('getJsonStringify')) {
    function getJsonStringify(string $string): string
    {
        return (string) (
            str($string)
                ->explode("\n")
                ->first(fn ($line) => str($line)->startsWith('{'))
        );
    }
}

if (! function_exists('parseUrl')) {
    function parseUrl(string $content): string
    {
        return (string) preg_replace_callback(
            '/((https?:\/\/)?((localhost)|((?:\d{1,3}\.){3}\d{1,3})|[\w\-._@:%\+~#=]{1,256}(\.[a-zA-Z]{2,})+)(:\d+)?(\/[\w\-._@:%\+~#=\/]*)?(\?[\w\-._@:%\+~#=\/&]*)?)|((\+?\d{1,3}[\s.-]?)?(\(?\d{1,4}\)?[\s.-]?)?\d{1,4}[\\s.-]?\d{1,4}[\s.-]?\d{1,9})|([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',
            function (array $matches): string {
                $url = preg_match('/^https?:\/\//', $matches[0]) ? $matches[0] : 'https://'.$matches[0];
                $humanUrl = (string) preg_replace('/^https?:\/\//', '', $matches[0]);
                $isMail = preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $humanUrl);
                $isPhone = preg_match('/^(?:\+?(\d{1,3})[\s.-]?)?(?:\(?\d{1,4}\)?[\s.-]?)?\d{1,4}[\s.-]?\d{1,4}[\s.-]?\d{1,9}$/', $matches[0]);

                if ($isMail) {
                    $url = 'mailto:'.$humanUrl;
                } elseif ($isPhone) {
                    // Remove non-digit characters for a standardized phone link
                    $phone = preg_replace('/[^\d+]/', '', $matches[0]);
                    $url = 'tel:'.$phone;
                }

                if (mb_substr($humanUrl, -1) === '/') {
                    $humanUrl = mb_substr($humanUrl, 0, -1);
                }

                return $url;
            },
            str_replace('&amp;', '&', $content)
        );
    }
}

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

            // images
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

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

            // js.map
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

if (! function_exists('flattenValue')) {
    function flattenValue(array $target): array
    {
        return array_map(fn (array $item) => $item['value'], $target);
    }
}

if (! function_exists('revertFlattenedValue')) {
    function revertFlattenedValue(array $target): array
    {
        return array_map(fn ($item): array => ['value' => $item], $target);
    }
}

/**
 * @internal
 */
if (! function_exists('isNotNull')) {
    function isNotNull(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== '0';
    }
}

if (! function_exists('resolvePartialProperties')) {
    function resolvePartialProperties(): array
    {
        $props = [];

        $only = array_filter(explode(',', request()->header(Header::PARTIAL_ONLY, '')));

        if ($only !== []) {
            $newProps = [];

            foreach ($only as $key) {
                Arr::set($newProps, $key, Arr::get($props, $key));
            }

            $props = $newProps;
        }

        return $props;
    }
}

if (! function_exists('enumToValueLabelArray')) {
    /**
     * Converts enum cases to an array of value-label pairs.
     */
    function enumToValueLabelArray(array $options): array
    {
        return array_map(fn ($value, $key): array => [
            'label' => $value,
            'value' => $key,
        ], $options, array_keys($options));
    }
}

if (! function_exists('clearFilters')) {
    /**
     * Removes specified filter fields from query parameters.
     *
     * @param  Request  $request  The query parameters to process
     * @param  array  $fieldsToRemove  Fields to remove from the parameters (empty array means remove all filter fields)
     * @return string route url with the filter fields removed
     */
    function clearFilters(Request $request, array $fieldsToRemove = []): ?string
    {

        $query = $request->query();

        if ($fieldsToRemove === []) {
            $fieldsToRemove = array_keys($query);
        }

        foreach ($fieldsToRemove as $field) {
            unset($query[$field]);
        }

        return url(request()->path()).'?'.http_build_query($query);
    }
}

if (! function_exists('filteredParams')) {
    /**
     * Get the filtered parameters from the request.
     */
    function filteredParams(Request $request, array $filtersValue): array
    {
        return array_filter($request->query(), fn ($value, $key): bool => in_array($key, $filtersValue), ARRAY_FILTER_USE_BOTH);
    }
}

if (! function_exists('convertUsdToMnt')) {
    /**
     * Converts USD amount to MNT (Mongolian Tugrik).
     * 1 USD = 3,593 MNT
     */
    function convertUsdToMnt(float $usdAmount): float
    {
        return $usdAmount * 3593;
    }
}

if (! function_exists('formatMnt')) {
    /**
     * Formats MNT amount with currency symbol and proper number formatting.
     */
    function formatMnt(float $mntAmount): string
    {
        return '₮'.number_format($mntAmount, 0, '.', ',');
    }
}

if (! function_exists('convertAndFormatUsdToMnt')) {
    /**
     * Converts USD to MNT and formats it with currency symbol.
     */
    function convertAndFormatUsdToMnt(float $usdAmount): string
    {
        $mntAmount = convertUsdToMnt($usdAmount);

        return formatMnt($mntAmount);
    }
}
