<?php

use App\Http\Middleware\SetLocale;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

it('sets the locale if valid', function () {
    // Mock TranslationService
    $translationService = Mockery::mock(TranslationService::class);
    $translationService->shouldReceive('getAvailableLocales')->andReturn(['en_US' => 'English', 'nl_NL' => 'Dutch']);

    // Use settings helper to ensure service cache is updated
    settings('application.locale', 'nl_NL');

    $middleware = new SetLocale($translationService);
    $request = Request::create('/', 'GET');

    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('nl_NL');

        return response('OK');
    });
});

it('falls back to en_US if en is requested', function () {
    $translationService = Mockery::mock(TranslationService::class);
    $translationService->shouldReceive('getAvailableLocales')->andReturn(['en_US' => 'English']);

    settings('application.locale', 'en');

    $middleware = new SetLocale($translationService);
    $request = Request::create('/', 'GET');

    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('en_US');

        return response('OK');
    });
});

it('normalizes locale strings', function () {
    $translationService = Mockery::mock(TranslationService::class);
    $translationService->shouldReceive('getAvailableLocales')->andReturn(['en_US' => 'English']);

    settings('application.locale', 'en-US');

    $middleware = new SetLocale($translationService);
    $request = Request::create('/', 'GET');

    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('en_US');

        return response('OK');
    });
});

it('falls back to first available locale if invalid', function () {
    $translationService = Mockery::mock(TranslationService::class);
    // Return array where 'de_DE' is first
    $translationService->shouldReceive('getAvailableLocales')->andReturn(['de_DE' => 'German', 'en_US' => 'English']);

    settings('application.locale', 'invalid_LOCALE');

    $middleware = new SetLocale($translationService);
    $request = Request::create('/', 'GET');

    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('de_DE');

        return response('OK');
    });
});

it('does nothing if no setting and no valid fallback', function () {
    // Setup initial state
    Config::set('app.locale', 'default');
    App::setLocale('default');

    $translationService = Mockery::mock(TranslationService::class);
    $translationService->shouldReceive('getAvailableLocales')->andReturn([]);

    // No setting created

    $middleware = new SetLocale($translationService);
    $request = Request::create('/', 'GET');

    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('default');

        return response('OK');
    });
});
