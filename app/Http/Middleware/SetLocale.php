<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localeHeader = $request->header('X-Locale');

        $languageCode = Locale::ENGLISH->value;

        if ($localeHeader) {
            $locale = Locale::fromString($localeHeader);
            if ($locale) {
                $languageCode = $locale->value;
            }
        }

        App::setLocale($languageCode);

        return $next($request);
    }
}
