<?php

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a serie with all fields', function () {
    $serie = Serie::create([
        'name' => 'Breaking Bad',
        'trakt_id' => 1388,
        'tvdb_id' => 81189,
        'imdb_id' => 'tt0903747',
        'status' => 'ended',
        'genre' => 'drama|thriller',
        'network' => 'AMC',
        'runtime' => 60,
        'displaycalendar' => true,
        'autoDownload' => true,
    ]);

    expect($serie->id)->toBeInt()
        ->and($serie->name)->toBe('Breaking Bad')
        ->and($serie->trakt_id)->toBe(1388)
        ->and($serie->displaycalendar)->toBeTrue()
        ->and($serie->autoDownload)->toBeTrue();
});

it('strips "The" prefix in getSortName', function () {
    $serie = Serie::create(['name' => 'The Walking Dead', 'trakt_id' => 1]);
    expect($serie->getSortName())->toBe('Walking Dead');

    $serie2 = Serie::create(['name' => 'A Series of Unfortunate Events', 'trakt_id' => 2]);
    expect($serie2->getSortName())->toBe('Series of Unfortunate Events');

    $serie3 = Serie::create(['name' => 'Breaking Bad', 'trakt_id' => 3]);
    expect($serie3->getSortName())->toBe('Breaking Bad');
});

it('has many episodes and seasons', function () {
    $serie = Serie::create(['name' => 'Test Show', 'trakt_id' => 100]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);
    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'episodename' => 'Pilot',
        'trakt_id' => 1001,
    ]);

    expect($serie->episodes)->toHaveCount(1)
        ->and($serie->seasons)->toHaveCount(1);
});

it('toggles autoDownload', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 200, 'autoDownload' => true]);
    expect($serie->autoDownload)->toBeTrue();

    $serie->toggleAutoDownload();
    $serie->refresh();
    expect($serie->autoDownload)->toBeFalse();

    $serie->toggleAutoDownload();
    $serie->refresh();
    expect($serie->autoDownload)->toBeTrue();
});

it('toggles calendar display', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 201, 'displaycalendar' => true]);
    $serie->toggleCalendarDisplay();
    $serie->refresh();
    expect($serie->displaycalendar)->toBeFalse();
});

it('detects anime genre', function () {
    $anime = Serie::create(['name' => 'Naruto', 'trakt_id' => 300, 'genre' => 'anime|action']);
    $drama = Serie::create(['name' => 'Drama', 'trakt_id' => 301, 'genre' => 'drama']);

    expect($anime->isAnime())->toBeTrue()
        ->and($drama->isAnime())->toBeFalse();
});

it('marks all episodes as watched', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 400]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    // Aired episode
    $ep1 = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'firstaired' => now()->subDay()->getTimestampMs(),
        'trakt_id' => 4001,
    ]);

    // Future episode - should not be marked
    $ep2 = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'firstaired' => now()->addYear()->getTimestampMs(),
        'trakt_id' => 4002,
    ]);

    $serie->markSerieAsWatched();

    $ep1->refresh();
    $ep2->refresh();

    expect($ep1->isWatched())->toBeTrue()
        ->and($ep1->isDownloaded())->toBeTrue()
        ->and($ep2->isWatched())->toBeFalse();
});

it('gets next and last episode', function () {
    $serie = Serie::create(['name' => 'Test', 'trakt_id' => 500]);
    $season = Season::create(['serie_id' => $serie->id, 'seasonnumber' => 1]);

    $past = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 1,
        'firstaired' => now()->subWeek()->getTimestampMs(),
        'trakt_id' => 5001,
    ]);

    $future = Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'seasonnumber' => 1,
        'episodenumber' => 2,
        'firstaired' => now()->addWeek()->getTimestampMs(),
        'trakt_id' => 5002,
    ]);

    expect($serie->getLastEpisode()->id)->toBe($past->id)
        ->and($serie->getNextEpisode()->id)->toBe($future->id);
});
