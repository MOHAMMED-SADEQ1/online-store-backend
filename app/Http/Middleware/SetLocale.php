<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // 1. Route prefix (highest priority) — detect and strip from URL
        if ($request->segment(1) === 'api' && in_array($request->segment(2), ['ar', 'en'])) {
            $locale = $request->segment(2);
            $this->stripLocaleFromUri($request);
        }

        // 2. Accept-Language header
        if (!$locale && $request->hasHeader('Accept-Language')) {
            $header = $request->header('Accept-Language');
            if (in_array($header, ['ar', 'en'])) {
                $locale = $header;
            } elseif (str_starts_with($header, 'ar')) {
                $locale = 'ar';
            } elseif (str_starts_with($header, 'en')) {
                $locale = 'en';
            }
        }

        // 3. Query param ?lang=
        if (!$locale && $request->has('lang') && in_array($request->query('lang'), ['ar', 'en'])) {
            $locale = $request->query('lang');
        }

        // 4. Authenticated user locale
        if (!$locale && $request->user()?->locale && in_array($request->user()->locale, ['ar', 'en'])) {
            $locale = $request->user()->locale;
        }

        // 5. Default
        if (!$locale) {
            $locale = 'ar';
        }

        App::setLocale($locale);

        return $next($request);
    }

    private function stripLocaleFromUri(Request $request): void
    {
        $uri = $request->server->get('REQUEST_URI', '/');
        $newUri = preg_replace('#^/(api/)(ar|en)(/|$)#i', '/$1', $uri, 1);
        $request->server->set('REQUEST_URI', $newUri);
        $request->server->set('PATH_INFO', $newUri);
        $request->server->set('ORIG_PATH_INFO', $newUri);

        (function () {
            $this->pathInfo = null;
            $this->requestUri = null;
            $this->baseUrl = null;
            $this->basePath = null;
        })->call($request);
    }
}
