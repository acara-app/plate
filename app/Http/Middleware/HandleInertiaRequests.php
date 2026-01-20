<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'subscribed' => $request->user()?->hasActiveSubscription() ?? false,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $locale,
            'translations' => Inertia::once(fn (): array => $this->getTranslations($locale)),
        ];
    }

    /**
     * Load all translations for the given locale.
     *
     * @return array<string, mixed>
     */
    private function getTranslations(string $locale): array
    {
        $translations = [];
        $langPath = lang_path($locale);

        if (File::isDirectory($langPath)) {
            foreach (File::files($langPath) as $file) {
                $namespace = $file->getFilenameWithoutExtension();
                $translations[$namespace] = require $file->getPathname();
            }
        }

        return $translations;
    }
}
