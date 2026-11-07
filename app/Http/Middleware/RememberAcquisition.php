<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Billing\PhotoPlanCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class RememberAcquisition
{
    public function __construct(private PhotoPlanCatalog $catalog) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->ajax() && ! $request->hasHeader('X-Inertia') && $request->hasSession() && ! $request->session()->has('acquisition') && $this->catalog->isSelling()) {
            $request->session()->put('acquisition', ['device' => $this->device($request), ...$this->source($request)]);
        }

        return $next($request);
    }

    private function device(Request $request): string
    {
        return Str::contains((string) $request->userAgent(), ['Mobi', 'Android', 'iPhone', 'iPad', 'iPod'], ignoreCase: true) ? 'mobile' : 'desktop';
    }

    /** @return array<string, string> */
    private function source(Request $request): array
    {
        $source = $this->label($request->query('utm_source'));
        $medium = $this->label($request->query('utm_medium'));
        $referrer = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if ($source === null && is_string($referrer) && Str::chopStart($referrer, 'www.') !== Str::chopStart($request->getHost(), 'www.')) {
            $source = $this->label(Str::chopStart($referrer, 'www.'));
            $medium ??= 'referral';
        }

        if ($source === null) {
            return [];
        }

        return array_filter([
            'source' => $source,
            'medium' => $medium,
            'campaign' => $this->label($request->query('utm_campaign')),
        ]);
    }

    private function label(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $label = Str::of($value)->lower()->replaceMatches('/[^a-z0-9._-]+/', '-')->trim('-')->limit(64, '')->toString();

        return $label === '' ? null : $label;
    }
}
