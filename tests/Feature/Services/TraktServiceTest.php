<?php

use App\Services\SettingsService;
use App\Services\TraktService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->settings = app(SettingsService::class);
    $this->trakt = app(TraktService::class);
});

it('can be resolved from the container', function () {
    expect($this->trakt)->toBeInstanceOf(TraktService::class);
});

it('returns the pin url', function () {
    expect($this->trakt->getPinUrl())->toBe('https://trakt.tv/pin/179590');
});

it('searches for shows and normalizes results', function () {
    Http::fake([
        'api.trakt.tv/search/show*' => Http::response([
            [
                'type' => 'show',
                'score' => 100,
                'show' => [
                    'title' => 'Breaking Bad',
                    'year' => 2008,
                    'ids' => [
                        'trakt' => 1388,
                        'slug' => 'breaking-bad',
                        'tvdb' => 81189,
                        'imdb' => 'tt0903747',
                        'tmdb' => 1396,
                    ],
                ],
            ],
        ]),
    ]);

    $results = $this->trakt->search('Breaking Bad');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['name'])->toBe('Breaking Bad')
        ->and($results[0]['trakt_id'])->toBe(1388)
        ->and($results[0]['tvdb_id'])->toBe(81189)
        ->and($results[0]['imdb_id'])->toBe('tt0903747')
        ->and($results[0]['tmdb_id'])->toBe(1396);
});

it('fetches seasons and normalizes ids', function () {
    Http::fake([
        'api.trakt.tv/shows/1388/seasons*' => Http::response([
            [
                'number' => 1,
                'ids' => ['trakt' => 3950, 'tvdb' => 30272, 'tmdb' => 3572],
                'rating' => 8.5,
                'votes' => 100,
                'episode_count' => 7,
            ],
        ]),
    ]);

    $seasons = $this->trakt->seasons('1388');

    expect($seasons)->toHaveCount(1)
        ->and($seasons[0]['number'])->toBe(1)
        ->and($seasons[0]['trakt_id'])->toBe(3950)
        ->and($seasons[0]['tvdb_id'])->toBe(30272);
});

it('fetches episodes and deduplicates by number', function () {
    Http::fake([
        'api.trakt.tv/shows/1388/seasons/1/episodes*' => Http::response([
            ['number' => 1, 'title' => 'Pilot', 'ids' => ['trakt' => 62085]],
            ['number' => 1, 'title' => 'Pilot Duplicate', 'ids' => ['trakt' => 99999]], // duplicate
            ['number' => 0, 'title' => 'Episode Zero', 'ids' => ['trakt' => 99998]], // episode 0 filtered
            ['number' => 2, 'title' => 'Cat\'s in the Bag...', 'ids' => ['trakt' => 62086]],
        ]),
    ]);

    $episodes = $this->trakt->episodes('1388', '1');

    expect($episodes)->toHaveCount(2)
        ->and($episodes[0]['name'])->toBe('Pilot')
        ->and($episodes[0]['trakt_id'])->toBe(62085)
        ->and($episodes[1]['name'])->toBe('Cat\'s in the Bag...');
});

it('fetches a full serie with people, seasons, and episodes', function () {
    Http::fake([
        'api.trakt.tv/shows/1388?extended=full*' => Http::response([
            'title' => 'Breaking Bad',
            'ids' => ['trakt' => 1388, 'tvdb' => 81189, 'imdb' => 'tt0903747', 'tmdb' => 1396],
        ]),
        'api.trakt.tv/shows/1388/people*' => Http::response([
            'cast' => [['person' => ['name' => 'Bryan Cranston'], 'character' => 'Walter White']],
        ]),
        'api.trakt.tv/shows/1388/seasons/1/episodes*' => Http::response([
            ['number' => 1, 'title' => 'Pilot', 'ids' => ['trakt' => 62085]],
        ]),
        'api.trakt.tv/shows/1388/seasons?*' => Http::response([
            ['number' => 1, 'ids' => ['trakt' => 3950, 'tvdb' => 30272, 'tmdb' => 3572]],
        ]),
    ]);

    $serie = $this->trakt->serie('1388');

    expect($serie['name'])->toBe('Breaking Bad')
        ->and($serie['trakt_id'])->toBe(1388)
        ->and($serie['people'])->toBeArray()
        ->and($serie['seasons'])->toHaveCount(1)
        ->and($serie['seasons'][0]['episodes'])->toHaveCount(1)
        ->and($serie['seasons'][0]['episodes'][0]['name'])->toBe('Pilot');
});

it('fetches serie summary only when seriesOnly is true', function () {
    Http::fake([
        'api.trakt.tv/shows/1388?*' => Http::response([
            'title' => 'Breaking Bad',
            'ids' => ['trakt' => 1388, 'tvdb' => 81189],
        ]),
    ]);

    $serie = $this->trakt->serie('1388', null, true);

    expect($serie['name'])->toBe('Breaking Bad')
        ->and($serie)->not->toHaveKey('people')
        ->and($serie)->not->toHaveKey('seasons');

    Http::assertSentCount(1);
});

it('resolves a show by tvdb_id', function () {
    Http::fake([
        'api.trakt.tv/search/tvdb/*' => Http::response([
            ['type' => 'show', 'show' => [
                'title' => 'Breaking Bad',
                'ids' => ['trakt' => 1388, 'tvdb' => 81189],
            ]],
        ]),
    ]);

    $result = $this->trakt->resolveID('81189');

    expect($result['name'])->toBe('Breaking Bad')
        ->and($result['tvdb_id'])->toBe(81189);
});

it('resolves a show by trakt_id', function () {
    Http::fake([
        'api.trakt.tv/search/trakt/*' => Http::response([
            ['type' => 'show', 'show' => [
                'title' => 'Breaking Bad',
                'ids' => ['trakt' => 1388, 'tvdb' => 81189],
            ]],
        ]),
    ]);

    $result = $this->trakt->resolveID('1388', true);

    expect($result['trakt_id'])->toBe(1388);
});

it('throws when tvdb_id resolves no results', function () {
    Http::fake([
        'api.trakt.tv/search/tvdb/*' => Http::response([]),
    ]);

    $this->trakt->resolveID('999999');
})->throws(RuntimeException::class, 'No results for search by tvdb_id');

it('handles login and stores tokens', function () {
    Http::fake([
        'api.trakt.tv/oauth/token*' => Http::response([
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'bearer',
        ]),
    ]);

    $token = $this->trakt->login('12345678');

    expect($token)->toBe('test_access_token')
        ->and($this->settings->get('trakttv.token'))->toBe('test_access_token')
        ->and($this->settings->get('trakttv.refresh_token'))->toBe('test_refresh_token');
});

it('handles trending with cache', function () {
    // First call: no cache, hits API
    Http::fake([
        'api.trakt.tv/shows/trending*' => Http::response([
            ['show' => ['title' => 'Show A', 'ids' => ['trakt' => 1]]],
            ['show' => ['title' => 'Show B', 'ids' => ['trakt' => 2]]],
        ]),
    ]);

    $results = $this->trakt->trending(true);
    expect($results)->toHaveCount(2);

    // Second call without noCache should use cached data
    Http::fake(); // no more API calls expected
    $cached = $this->trakt->trending(false);
    expect($cached)->toHaveCount(2);
});

it('marks episodes as watched via sync API', function () {
    $this->settings->set('trakttv.token', 'test_token');

    Http::fake([
        'api.trakt.tv/sync/history*' => Http::response([
            'added' => ['episodes' => 1],
        ]),
    ]);

    $result = $this->trakt->markEpisodeWatched(62085, 1700000000000);

    expect($result['added']['episodes'])->toBe(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sync/history')
            && $request->method() === 'POST'
            && $request['episodes'][0]['ids']['trakt'] === 62085;
    });
});

it('marks episodes as not watched via sync API', function () {
    $this->settings->set('trakttv.token', 'test_token');

    Http::fake([
        'api.trakt.tv/sync/history/remove*' => Http::response([
            'deleted' => ['episodes' => 1],
        ]),
    ]);

    $result = $this->trakt->markEpisodeNotWatched(62085);

    expect($result['deleted']['episodes'])->toBe(1);
});

it('adds show to collection', function () {
    $this->settings->set('trakttv.token', 'test_token');

    Http::fake([
        'api.trakt.tv/sync/collection*' => Http::response([
            'added' => ['shows' => 1],
        ]),
    ]);

    $result = $this->trakt->addShowToCollection(1388);

    expect($result['added']['shows'])->toBe(1);
});

it('throws RateLimitException on 429 rate limit', function () {
    $this->settings->set('trakttv.token', 'test_token');

    Http::fake([
        'api.trakt.tv/*' => Http::response('Rate limited', 429, ['Retry-After' => '0']),
    ]);

    expect(fn () => $this->trakt->search('Breaking Bad'))
        ->toThrow(\App\Exceptions\RateLimitException::class);
});
