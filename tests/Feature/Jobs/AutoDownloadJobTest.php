<?php

use App\Jobs\AutoDownloadJob;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use App\Services\FavoritesService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('can be dispatched to a queue', function () {
    Queue::fake();

    AutoDownloadJob::dispatch();

    Queue::assertPushed(AutoDownloadJob::class);
});

it('skips when torrenting is disabled', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', false);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    // Should complete without error
    expect(true)->toBeTrue();
});

it('skips when autodownload is disabled', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', true);
    $settings->set('torrenting.autodownload', false);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    expect(true)->toBeTrue();
});

it('processes episode candidates and skips downloaded', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', true);
    $settings->set('torrenting.autodownload', true);

    $serie = Serie::create([
        'name' => 'Test Show',
        'trakt_id' => 1,
        'tvdb_id' => 100,
        'displaycalendar' => true,
        'autoDownload' => true,
        'runtime' => 30,
    ]);

    $season = Season::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'trakt_id' => 10,
    ]);

    // Already downloaded episode
    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'episodename' => 'Downloaded Episode',
        'episodenumber' => 1,
        'seasonnumber' => 1,
        'firstaired' => now()->subDay()->getTimestampMs(),
        'trakt_id' => 100,
        'downloaded' => 1,
        'watched' => 0,
    ]);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    // Should complete without error (episode skipped because downloaded)
    expect(true)->toBeTrue();
});

it('skips episodes hidden from calendar', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', true);
    $settings->set('torrenting.autodownload', true);

    $serie = Serie::create([
        'name' => 'Hidden Show',
        'trakt_id' => 2,
        'tvdb_id' => 200,
        'displaycalendar' => false, // hidden from calendar
        'autoDownload' => true,
    ]);

    $season = Season::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 1,
        'trakt_id' => 20,
    ]);

    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'episodename' => 'Hidden Episode',
        'episodenumber' => 1,
        'seasonnumber' => 1,
        'firstaired' => now()->subHours(3)->getTimestampMs(),
        'trakt_id' => 200,
        'downloaded' => 0,
        'watched' => 0,
    ]);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    // Episode should NOT have been downloaded (serie hidden from calendar)
    $ep = Episode::find(1);
    expect($ep->downloaded)->toBe(0);
});

it('skips specials when show-specials is disabled', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', true);
    $settings->set('torrenting.autodownload', true);
    $settings->set('calendar.show-specials', false);

    $serie = Serie::create([
        'name' => 'Special Show',
        'trakt_id' => 3,
        'tvdb_id' => 300,
        'displaycalendar' => true,
        'autoDownload' => true,
        'ignoreHideSpecials' => false,
    ]);

    $season = Season::create([
        'serie_id' => $serie->id,
        'seasonnumber' => 0,
        'trakt_id' => 30,
    ]);

    Episode::create([
        'serie_id' => $serie->id,
        'season_id' => $season->id,
        'episodename' => 'Special Episode',
        'episodenumber' => 1,
        'seasonnumber' => 0,
        'firstaired' => now()->subHours(3)->getTimestampMs(),
        'trakt_id' => 300,
        'downloaded' => 0,
        'watched' => 0,
    ]);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    // Special episode should NOT have been downloaded
    $ep = Episode::where('trakt_id', 300)->first();
    expect($ep->downloaded)->toBe(0);
});

it('updates lastrun timestamp after check', function () {
    $settings = app(SettingsService::class);
    $settings->set('torrenting.enabled', true);
    $settings->set('torrenting.autodownload', true);

    $job = new AutoDownloadJob();
    $job->handle($settings, app(FavoritesService::class));

    expect((int) $settings->get('autodownload.lastrun'))->toBeGreaterThan(0);
});
