<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\AutoDLStatusController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalendarController::class , 'index'])->name('home');

Route::prefix('calendar')->group(function () {
    Route::get('/', [CalendarController::class , 'index'])->name('calendar.index');
    Route::post('/mark-day-watched', [CalendarController::class , 'markDayWatched'])->name('calendar.mark-watched');
    Route::post('/mark-day-downloaded', [CalendarController::class , 'markDayDownloaded'])->name('calendar.mark-downloaded');
});

Route::prefix('search')->group(function () {
    Route::get('/', [SearchController::class , 'index'])->name('search.index');
    Route::get('/trending', [SearchController::class , 'index'])->name('search.trending');
    Route::get('/query', [SearchController::class , 'search'])->name('search.query');
    Route::post('/add', [SearchController::class , 'add'])->name('search.add');
});

Route::get('/watchlist', [WatchlistController::class , 'index'])->name('watchlist.index');
Route::get('/autodlstatus', [AutoDLStatusController::class , 'index'])->name('autodlstatus.index');
Route::get('/about', [AboutController::class , 'index'])->name('about.index');

// Background Rotator
Route::get('/api/background/random', [\App\Http\Controllers\BackgroundController::class , 'getRandom'])->name('background.random');

// Series / Favorites
Route::get('/favorites', [\App\Http\Controllers\SeriesController::class , 'index'])->name('series.index');
Route::get('/series/{id}', [\App\Http\Controllers\SeriesController::class , 'show'])->name('series.show');
Route::patch('/series/{id}', [\App\Http\Controllers\SeriesController::class , 'update'])->name('series.update');
Route::put('/series/{id}', [\App\Http\Controllers\SeriesController::class , 'refresh'])->name('series.refresh');
Route::get('/series/{id}/details', [\App\Http\Controllers\SeriesController::class , 'details'])->name('series.details');
Route::get('/series/{id}/seasons', [\App\Http\Controllers\SeriesController::class , 'seasons'])->name('series.seasons');
Route::get('/series/{id}/episodes/{season_id?}', [\App\Http\Controllers\SeriesController::class , 'episodes'])->name('series.episodes');
Route::delete('/series/{id}', [\App\Http\Controllers\SeriesController::class , 'remove'])->name('series.remove');

// Episodes
Route::get('/episodes/{id}', [\App\Http\Controllers\EpisodeController::class , 'show'])->name('episodes.show');
Route::patch('/episodes/{id}', [\App\Http\Controllers\EpisodeController::class , 'update'])->name('episodes.update');
Route::post('/episodes/{id}/auto-download', [\App\Http\Controllers\EpisodeController::class , 'autoDownload'])->name('episodes.auto-download');

// Torrent Search
Route::get('/debug-engines', function() {
    $service = app(\App\Services\TorrentSearchService::class);
    return response()->json([
        'engines' => array_keys($service->getSearchEngines()),
        'count' => count($service->getSearchEngines())
    ]);
});

Route::prefix('torrents')->group(function () {
    Route::get('/search-dialog', [\App\Http\Controllers\TorrentController::class , 'searchDialog'])->name('torrents.search-dialog');
    Route::get('/search', [\App\Http\Controllers\TorrentController::class , 'search'])->name('torrents.search');
    Route::post('/details', [\App\Http\Controllers\TorrentController::class , 'details'])->name('torrents.details');
    Route::get('/engines', [\App\Http\Controllers\TorrentController::class , 'engines'])->name('torrents.engines');
    Route::post('/add', [\App\Http\Controllers\TorrentController::class , 'add'])->name('torrents.add');
    Route::get('/status', [\App\Http\Controllers\TorrentController::class , 'status'])->name('torrents.status');
    Route::post('/connect', [\App\Http\Controllers\TorrentController::class , 'connect'])->name('torrents.connect');
    Route::get('/', [\App\Http\Controllers\TorrentController::class , 'index'])->name('torrents.index');
    Route::get('/{infoHash}', [\App\Http\Controllers\TorrentController::class , 'show'])->name('torrents.show');
    Route::post('/{infoHash}/start', [\App\Http\Controllers\TorrentController::class , 'start'])->name('torrents.start');
    Route::post('/{infoHash}/stop', [\App\Http\Controllers\TorrentController::class , 'stop'])->name('torrents.stop');
    Route::post('/{infoHash}/pause', [\App\Http\Controllers\TorrentController::class , 'pause'])->name('torrents.pause');
    Route::post('/{infoHash}/remove', [\App\Http\Controllers\TorrentController::class , 'remove'])->name('torrents.remove');
});

// Settings
Route::prefix('settings')->group(function () {
    Route::get('/', [\App\Http\Controllers\SettingsController::class , 'index'])->name('settings.index');
    Route::post('/restore', [\App\Http\Controllers\SettingsController::class , 'restore'])->name('settings.restore');
    Route::get('/restore/progress', [\App\Http\Controllers\SettingsController::class , 'restoreProgress'])->name('settings.restore-progress');
    Route::post('/restore/cancel', [\App\Http\Controllers\SettingsController::class , 'cancelRestore'])->name('settings.restore-cancel');
    Route::get('/{section}', [\App\Http\Controllers\SettingsController::class , 'show'])->name('settings.show');
    Route::post('/{section}', [\App\Http\Controllers\SettingsController::class , 'update'])->name('settings.update');
});

Route::post('/settings/toggle-viewmode', [CalendarController::class , 'toggleViewMode'])->name('settings.toggle-viewmode');
