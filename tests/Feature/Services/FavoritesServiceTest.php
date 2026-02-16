<?php

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use App\Services\FavoritesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fake TMDB API responses so addFavorite() image fetching works
    Http::fake([
        'api.themoviedb.org/3/tv/1396*' => Http::response([
            'poster_path' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg',
            'backdrop_path' => '/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg',
        ]),
        'api.themoviedb.org/3/tv/60059*' => Http::response([
            'poster_path' => '/fC2HDm5t0kHl7mTm7jxMR31b7by.jpg',
            'backdrop_path' => '/hPea3Qy5Gd0xVsg1J9fAgHNQYXp.jpg',
        ]),
        'api.themoviedb.org/*' => Http::response([
            'poster_path' => null,
            'backdrop_path' => null,
        ]),
    ]);

    $this->favorites = app(FavoritesService::class);
});

/**
 * Build a minimal Trakt API show response for testing addFavorite().
 */
function makeTraktShowData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Breaking Bad',
        'trakt_id' => 1388,
        'tvdb_id' => 81189,
        'tmdb_id' => 1396,
        'imdb_id' => 'tt0903747',
        'tvrage_id' => null,
        'name' => 'Breaking Bad',
        'certification' => 'TV-MA',
        'overview' => 'A chemistry teacher turned meth maker.',
        'network' => 'AMC',
        'status' => 'ended',
        'country' => 'us',
        'language' => 'en',
        'runtime' => 60,
        'first_aired' => '2008-01-20T00:00:00.000Z',
        'rating' => 9.2,
        'votes' => 50000,
        'genres' => ['drama', 'thriller'],
        'updated_at' => '2024-01-01T00:00:00.000Z',
        'airs' => ['day' => 'Sunday', 'time' => '21:00', 'timezone' => 'America/New_York'],
        'people' => [
            'cast' => [
                ['person' => ['name' => 'Bryan Cranston'], 'character' => 'Walter White'],
                ['person' => ['name' => 'Aaron Paul'], 'character' => 'Jesse Pinkman'],
            ],
        ],
        'seasons' => [
            [
                'number' => 1,
                'trakt_id' => 3950,
                'tmdb_id' => 3572,
                'overview' => 'Season 1',
                'rating' => 8.5,
                'votes' => 1000,
                'episodes' => [
                    [
                        'number' => 1,
                        'title' => 'Pilot',
                        'trakt_id' => 62085,
                        'tvdb_id' => 349232,
                        'tmdb_id' => 62085,
                        'imdb_id' => 'tt0959621',
                        'overview' => 'Walt begins his journey.',
                        'rating' => 8.8,
                        'votes' => 5000,
                        'first_aired' => '2008-01-20T02:00:00.000Z',
                        'number_abs' => null,
                    ],
                    [
                        'number' => 2,
                        'title' => 'Cat\'s in the Bag...',
                        'trakt_id' => 62086,
                        'tvdb_id' => 349233,
                        'tmdb_id' => 62086,
                        'imdb_id' => null,
                        'overview' => 'Walt and Jesse deal with aftermath.',
                        'rating' => 8.3,
                        'votes' => 4000,
                        'first_aired' => '2008-01-27T02:00:00.000Z',
                        'number_abs' => null,
                    ],
                ],
            ],
        ],
    ], $overrides);
}

it('adds a favorite show with seasons and episodes', function () {
    $data = makeTraktShowData();

    $serie = $this->favorites->addFavorite($data);

    expect($serie)->toBeInstanceOf(Serie::class)
        ->and($serie->name)->toBe('Breaking Bad')
        ->and($serie->trakt_id)->toBe(1388)
        ->and($serie->tvdb_id)->toBe(81189)
        ->and($serie->contentrating)->toBe('TV-MA')
        ->and($serie->network)->toBe('AMC')
        ->and($serie->status)->toBe('ended')
        ->and($serie->genre)->toBe('drama|thriller')
        ->and($serie->actors)->toContain('Bryan Cranston (Walter White)')
        ->and($serie->actors)->toContain('Aaron Paul (Jesse Pinkman)');

    // Check seasons created
    expect(Season::where('serie_id', $serie->id)->count())->toBe(1);
    $season = Season::where('serie_id', $serie->id)->first();
    expect($season->seasonnumber)->toBe(1)
        ->and($season->trakt_id)->toBe(3950);

    // Check episodes created
    expect(Episode::where('serie_id', $serie->id)->count())->toBe(2);
    $pilot = Episode::where('trakt_id', 62085)->first();
    expect($pilot->episodename)->toBe('Pilot')
        ->and($pilot->episodenumber)->toBe(1)
        ->and($pilot->seasonnumber)->toBe(1)
        ->and($pilot->serie_id)->toBe($serie->id)
        ->and($pilot->season_id)->toBe($season->id);
});

it('updates existing favorite when re-added', function () {
    $data = makeTraktShowData();

    // Add first time
    $serie1 = $this->favorites->addFavorite($data);

    // Update with new overview
    $data['overview'] = 'Updated overview';
    $serie2 = $this->favorites->addFavorite($data);

    // Should be the same serie, not a new one
    expect(Serie::count())->toBe(1)
        ->and($serie2->id)->toBe($serie1->id)
        ->and($serie2->overview)->toBe('Updated overview');
});

it('removes a favorite with cascading delete', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    expect(Serie::count())->toBe(1)
        ->and(Season::count())->toBe(1)
        ->and(Episode::count())->toBe(2);

    $this->favorites->remove($serie);

    expect(Serie::count())->toBe(0)
        ->and(Season::count())->toBe(0)
        ->and(Episode::count())->toBe(0);
});

it('cleans up orphaned episodes on update', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    expect(Episode::where('serie_id', $serie->id)->count())->toBe(2);

    // Re-add with only 1 episode (episode 2 removed from Trakt)
    $data['seasons'][0]['episodes'] = [$data['seasons'][0]['episodes'][0]];
    $this->favorites->addFavorite($data);

    expect(Episode::where('serie_id', $serie->id)->count())->toBe(1);
    expect(Episode::where('trakt_id', 62086)->exists())->toBeFalse();
});

it('looks up favorites by various IDs', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    expect($this->favorites->getById($serie->id)->trakt_id)->toBe(1388)
        ->and($this->favorites->getByTvdbId(81189)->trakt_id)->toBe(1388)
        ->and($this->favorites->getByTraktId(1388)->tvdb_id)->toBe(81189)
        ->and($this->favorites->hasFavorite(1388))->toBeTrue()
        ->and($this->favorites->hasFavorite(99999))->toBeFalse();
});

it('returns all series', function () {
    $this->favorites->addFavorite(makeTraktShowData());
    $this->favorites->addFavorite(makeTraktShowData([
        'title' => 'Better Call Saul',
        'trakt_id' => 59660,
        'tvdb_id' => 273181,
        'tmdb_id' => 60059,
        'seasons' => [],
    ]));

    $series = $this->favorites->getSeries();
    expect($series)->toHaveCount(2);
});

it('returns favorite IDs as strings', function () {
    $this->favorites->addFavorite(makeTraktShowData());

    $ids = $this->favorites->getFavoriteIds();
    expect($ids)->toContain('1388');
});

it('gets episodes for a date range', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    // Both episodes are from January 2008
    $start = (new DateTime('2008-01-01'))->getTimestamp() * 1000;
    $end = (new DateTime('2008-01-31'))->getTimestamp() * 1000;

    $episodes = $this->favorites->getEpisodesForDateRange($start, $end);
    expect($episodes)->toHaveCount(2);
});

it('returns random background serie', function () {
    $serie = Serie::create([
        'name' => 'Test Show',
        'trakt_id' => 1,
        'fanart' => 'https://example.com/fanart.jpg',
    ]);

    $result = $this->favorites->getRandomBackground();
    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($serie->id);
});

it('sets episode title to TBA when null', function () {
    $data = makeTraktShowData();
    $data['seasons'][0]['episodes'][0]['title'] = null;

    $serie = $this->favorites->addFavorite($data);

    $pilot = Episode::where('trakt_id', 62085)->first();
    expect($pilot->episodename)->toBe('TBA');
});

it('restores watched state from backup data', function () {
    $data = makeTraktShowData();
    $watched = [
        ['TRAKT_ID' => 62085, 'watchedAt' => 1700000000000],
    ];

    $serie = $this->favorites->addFavorite($data, $watched);

    $pilot = Episode::where('trakt_id', 62085)->first();
    expect($pilot->watched)->toBe(1)
        ->and($pilot->downloaded)->toBe(1)
        ->and($pilot->watchedAt)->toBe(1700000000000);

    // Second episode should not be watched
    $ep2 = Episode::where('trakt_id', 62086)->first();
    expect($ep2->watched)->toBe(0);
});

it('fetches images from TMDB when adding a favorite', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    expect($serie->fanart)->toBe('https://image.tmdb.org/t/p/original/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg')
        ->and($serie->poster)->toBe('https://image.tmdb.org/t/p/w500/ggFHVNu6YYI5L9pCfOacjizRGt.jpg');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.themoviedb.org/3/tv/1396');
    });
});

it('does not refetch images when fanart already exists', function () {
    $data = makeTraktShowData();

    // First add - fetches images
    $serie = $this->favorites->addFavorite($data);
    expect($serie->fanart)->not->toBeNull();

    // Second add - should NOT fetch again since fanart is already set
    Http::fake([
        'api.themoviedb.org/*' => Http::response([], 500), // Would fail if called
    ]);

    $updated = $this->favorites->addFavorite($data);
    expect($updated->fanart)->toBe('https://image.tmdb.org/t/p/original/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg');
});

it('handles useTraktId flag for lookup', function () {
    $data = makeTraktShowData();
    $serie = $this->favorites->addFavorite($data);

    // Re-add with useTraktId=true should find existing
    $updated = $this->favorites->addFavorite($data, [], true);
    expect($updated->id)->toBe($serie->id)
        ->and(Serie::count())->toBe(1);
});
