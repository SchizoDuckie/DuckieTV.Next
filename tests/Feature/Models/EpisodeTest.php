<?php

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('formats episode numbers correctly', function () {
    expect(Episode::formatEpisode(1, 5))->toBe('s01e05')
        ->and(Episode::formatEpisode(12, 1))->toBe('s12e01')
        ->and(Episode::formatEpisode(1, 1, 5))->toBe('s01e01(05)')
        ->and(Episode::formatEpisode(2, 10, 35))->toBe('s02e10(35)');
});

it('provides formatted_episode attribute', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 1]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 3,
        'episodenumber' => 7,
        'trakt_id' => 100,
    ]);

    expect($ep->formatted_episode)->toBe('s03e07');
});

it('detects if episode has aired', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 2]);

    $aired = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'firstaired' => now()->subDay()->getTimestampMs(),
        'trakt_id' => 101,
    ]);

    $notAired = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'firstaired' => now()->addDay()->getTimestampMs(),
        'trakt_id' => 102,
    ]);

    $noDate = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 3,
        'firstaired' => 0,
        'trakt_id' => 103,
    ]);

    expect($aired->hasAired())->toBeTrue()
        ->and($notAired->hasAired())->toBeFalse()
        ->and($noDate->hasAired())->toBeFalse();
});

it('marks episode as watched with paired download', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 3]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'trakt_id' => 201,
    ]);

    expect($ep->isWatched())->toBeFalse()
        ->and($ep->isDownloaded())->toBeFalse();

    $ep->markWatched(watchedDownloadedPaired: true);
    $ep->refresh();

    expect($ep->isWatched())->toBeTrue()
        ->and($ep->isDownloaded())->toBeTrue()
        ->and($ep->watchedAt)->not->toBeNull();
});

it('marks episode as watched without paired download', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 4]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'trakt_id' => 202,
    ]);

    $ep->markWatched(watchedDownloadedPaired: false);
    $ep->refresh();

    expect($ep->isWatched())->toBeTrue()
        ->and($ep->isDownloaded())->toBeFalse();
});

it('marks episode as not watched', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 5]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'watched' => 1,
        'watchedAt' => now()->getTimestampMs(),
        'trakt_id' => 203,
    ]);

    $ep->markNotWatched();
    $ep->refresh();

    expect($ep->isWatched())->toBeFalse()
        ->and($ep->watchedAt)->toBeNull();
});

it('marks episode as not downloaded with paired unwatched', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 6]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'watched' => 1,
        'downloaded' => 1,
        'magnetHash' => 'abc123',
        'trakt_id' => 204,
    ]);

    $ep->markNotDownloaded(watchedDownloadedPaired: true);
    $ep->refresh();

    expect($ep->isDownloaded())->toBeFalse()
        ->and($ep->isWatched())->toBeFalse()
        ->and($ep->magnetHash)->toBeNull();
});

it('belongs to a serie and season', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 7]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);
    $ep = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'trakt_id' => 205,
    ]);

    expect($ep->serie->id)->toBe($serie->id)
        ->and($ep->season->id)->toBe($season->id);
});
