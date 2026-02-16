<?php

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a serie', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 1]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    expect($season->serie->id)->toBe($serie->id);
});

it('has many episodes', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 2]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'trakt_id' => 101,
    ]);
    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'trakt_id' => 102,
    ]);

    expect($season->episodes)->toHaveCount(2);
});

it('marks season as watched', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 3]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    $ep1 = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'firstaired' => now()->subDay()->getTimestampMs(),
        'trakt_id' => 201,
    ]);
    $ep2 = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'firstaired' => now()->subDay()->getTimestampMs(),
        'trakt_id' => 202,
    ]);

    $season->markSeasonAsWatched();

    $ep1->refresh();
    $ep2->refresh();
    $season->refresh();

    expect($ep1->isWatched())->toBeTrue()
        ->and($ep2->isWatched())->toBeTrue()
        ->and($season->watched)->toBeTrue();
});

it('marks season as unwatched', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 4]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1, 'watched' => true]);

    $ep = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'watched' => 1,
        'trakt_id' => 301,
    ]);

    $season->markSeasonAsUnWatched();

    $ep->refresh();
    $season->refresh();

    expect($ep->isWatched())->toBeFalse()
        ->and($season->watched)->toBeFalse();
});

it('counts not watched episodes', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 5]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'watched' => 1,
        'trakt_id' => 401,
    ]);
    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'watched' => 0,
        'trakt_id' => 402,
    ]);
    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 3,
        'watched' => 0,
        'trakt_id' => 403,
    ]);

    expect($season->getEpisodeCount())->toBe(3)
        ->and($season->getNotWatchedCount())->toBe(2);
});
