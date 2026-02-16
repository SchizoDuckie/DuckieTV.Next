<?php

/**
 * Tests for TorrentController.
 *
 * Validates the torrent search dialog rendering, AJAX search endpoint,
 * detail page fetching, and engine listing. Uses mocked HTTP responses
 * to avoid hitting real torrent sites during testing.
 *
 * @see \App\Http\Controllers\TorrentController
 * @see \App\Services\TorrentSearchService
 */

use App\Services\SettingsService;
use App\Services\TorrentSearchEngines\SearchEngineInterface;
use App\Services\TorrentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Search Dialog (View Rendering)
|--------------------------------------------------------------------------
*/

it('renders the torrent search dialog with pre-filled query', function () {
    $response = $this->get(route('torrents.dialog', [
        'query' => 'Breaking Bad S01E01',
        'episode_id' => 42,
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('torrents.search');
    $response->assertViewHas('query', 'Breaking Bad S01E01');
    $response->assertViewHas('episodeId', '42');
    $response->assertViewHas('engines');
    $response->assertViewHas('defaultEngine');
    $response->assertViewHas('qualityList');
    $response->assertSee('FIND TORRENT');
    $response->assertSee('Breaking Bad S01E01');
});

it('renders the search dialog with default values when no params given', function () {
    $response = $this->get(route('torrents.dialog'));

    $response->assertStatus(200);
    $response->assertViewHas('query', '');
});

it('includes all registered engines in the dialog', function () {
    $response = $this->get(route('torrents.dialog'));

    $response->assertStatus(200);
    $engines = $response->viewData('engines');
    expect($engines)->toContain('ThePirateBay');
    expect($engines)->toContain('1337x');
    expect($engines)->toContain('Knaben');
});

/*
|--------------------------------------------------------------------------
| Search Endpoint (JSON API)
|--------------------------------------------------------------------------
*/

it('returns validation error when query is missing', function () {
    $response = $this->getJson(route('torrents.search'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['query']);
});

it('returns validation error when query is empty', function () {
    $response = $this->getJson(route('torrents.search', ['query' => '']));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['query']);
});

it('returns validation error for invalid sortBy format', function () {
    $response = $this->getJson(route('torrents.search', [
        'query' => 'test',
        'sortBy' => 'invalid-format',
    ]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sortBy']);
});

it('accepts valid sortBy formats', function () {
    // Mock the search service to avoid hitting real torrent sites
    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('search')
        ->once()
        ->with('test query', null, 'seeders.d')
        ->andReturn([]);
    $mockService->shouldReceive('getSearchEngine')->andReturnSelf();

    $this->app->instance(TorrentSearchService::class, $mockService);

    $settings = $this->app->make(SettingsService::class);

    $response = $this->getJson(route('torrents.search', [
        'query' => 'test query',
        'sortBy' => 'seeders.d',
    ]));

    $response->assertStatus(200);
    $response->assertJsonStructure(['results', 'engine', 'query']);
});

it('returns search results as JSON', function () {
    $mockResults = [
        [
            'releasename' => 'Breaking.Bad.S01E01.720p.BluRay',
            'size' => '800.00 MB',
            'seeders' => 150,
            'leechers' => 20,
            'magnetUrl' => 'magnet:?xt=urn:btih:abc123',
            'torrentUrl' => 'http://example.com/torrent.torrent',
            'detailUrl' => 'http://example.com/details/123',
            'noMagnet' => false,
            'noTorrent' => false,
        ],
    ];

    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('search')
        ->once()
        ->with('Breaking Bad S01E01', 'ThePirateBay', 'seeders.d')
        ->andReturn($mockResults);

    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->getJson(route('torrents.search', [
        'query' => 'Breaking Bad S01E01',
        'engine' => 'ThePirateBay',
        'sortBy' => 'seeders.d',
    ]));

    $response->assertStatus(200);
    $response->assertJson([
        'results' => $mockResults,
        'engine' => 'ThePirateBay',
        'query' => 'Breaking Bad S01E01',
    ]);
});

it('returns 422 with error message when search engine fails', function () {
    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('search')
        ->once()
        ->andThrow(new Exception('Connection refused'));

    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->getJson(route('torrents.search', [
        'query' => 'test query',
    ]));

    $response->assertStatus(422);
    $response->assertJson([
        'error' => 'Connection refused',
        'results' => [],
    ]);
});

it('falls back to default engine when none specified', function () {
    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('search')
        ->once()
        ->with('test', null, 'seeders.d')
        ->andReturn([]);

    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->getJson(route('torrents.search', ['query' => 'test']));

    $response->assertStatus(200);
    // Engine should be the default from settings
    $response->assertJsonFragment(['engine' => 'ThePirateBay']);
});

/*
|--------------------------------------------------------------------------
| Details Endpoint (Magnet Resolution)
|--------------------------------------------------------------------------
*/

it('returns validation errors when details params are missing', function () {
    // We assume the service is not called if basic validation fails, but strict mocking might require it if
    // the rules() method is resolved. However, rules() calls getSearchEngines(), so we might need a partial mock
    // or real service if we don't want to mock it here.
    // Ideally we'd use the real service for simple validation tests, or swap it.
    // But since we are swapping it in other tests, let's swap it here too to be safe/consistent,
    // although for missing params, the validation *might* fail before rules are fully evaluated if we were using
    // a form request that does authorization first... wait, validation happens in rules().
    // The Container resolves the FormRequest, which calls rules(), which calls the Service.
    // So we MUST mock it even for 422s if the 422 is from 'required' rules but we need to know the 'in' rules.

    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->postJson(route('torrents.details'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['engine', 'url', 'releasename']);
});

it('fetches magnet URL from detail page', function () {
    $mockEngine = Mockery::mock(SearchEngineInterface::class);
    $mockEngine->shouldReceive('getDetails')
        ->once()
        ->with('http://example.com/details/123', 'Breaking.Bad.S01E01')
        ->andReturn([
            'magnetUrl' => 'magnet:?xt=urn:btih:abc123&dn=Breaking.Bad.S01E01',
            'torrentUrl' => 'http://itorrents.org/torrent/ABC123.torrent',
        ]);

    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('getSearchEngine')
        ->once()
        ->with('1337x')
        ->andReturn($mockEngine);

    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->postJson(route('torrents.details'), [
        'engine' => '1337x',
        'url' => 'http://example.com/details/123',
        'releasename' => 'Breaking.Bad.S01E01',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'magnetUrl' => 'magnet:?xt=urn:btih:abc123&dn=Breaking.Bad.S01E01',
    ]);
});

it('returns 422 when detail page fetch fails', function () {
    $mockEngine = Mockery::mock(SearchEngineInterface::class);
    $mockEngine->shouldReceive('getDetails')
        ->once()
        ->andThrow(new Exception('Detail page returned 403'));

    $mockService = Mockery::mock(TorrentSearchService::class);
    $mockService->shouldReceive('getSearchEngines')->andReturn(['ThePirateBay' => [], '1337x' => []]);
    $mockService->shouldReceive('getSearchEngine')
        ->once()
        ->with('1337x')
        ->andReturn($mockEngine);

    $this->app->instance(TorrentSearchService::class, $mockService);

    $response = $this->postJson(route('torrents.details'), [
        'engine' => '1337x',
        'url' => 'http://example.com/details/123',
        'releasename' => 'test',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => 'Detail page returned 403']);
});

/*
|--------------------------------------------------------------------------
| Engines List Endpoint
|--------------------------------------------------------------------------
*/

it('returns list of available search engines', function () {
    $response = $this->getJson(route('torrents.engines'));

    $response->assertStatus(200);

    $engines = $response->json();
    expect($engines)->toBeArray();
    expect(count($engines))->toBeGreaterThan(0);

    // Each engine should have name and isDefault
    $first = $engines[0];
    expect($first)->toHaveKeys(['name', 'isDefault']);
});

it('marks the default engine correctly', function () {
    $response = $this->getJson(route('torrents.engines'));

    $engines = $response->json();
    $defaults = array_filter($engines, fn ($e) => $e['isDefault']);

    expect(count($defaults))->toBe(1);

    $defaultEngine = array_values($defaults)[0];
    expect($defaultEngine['name'])->toBe('ThePirateBay');
});

it('includes all 15 registered engines', function () {
    $response = $this->getJson(route('torrents.engines'));

    $engines = $response->json();
    $names = array_column($engines, 'name');

    expect($names)->toContain('ThePirateBay');
    expect($names)->toContain('1337x');
    expect($names)->toContain('LimeTorrents');
    expect($names)->toContain('Nyaa');
    expect($names)->toContain('theRARBG');
    expect($names)->toContain('Knaben');
    expect(count($engines))->toBe(15);
});
