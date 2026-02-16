<?php

namespace App\Http\Middleware;

use App\Services\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = settings()->get('application.locale', config('app.locale'));
        $locale = str_replace('-', '_', $locale);
        $availableLocales = $this->translationService->getAvailableLocales();

        if (! empty($locale) && array_key_exists($locale, $availableLocales)) {
            App::setLocale($locale);
        } else {
            // Fallback mechanisms
            if ($locale === 'en' && array_key_exists('en_US', $availableLocales)) {
                App::setLocale('en_US');
            } elseif (! empty($availableLocales)) {
                // Use first available locale if the requested one is not found
                App::setLocale(array_key_first($availableLocales));
            }
        }

        return $next($request);
    }
}
