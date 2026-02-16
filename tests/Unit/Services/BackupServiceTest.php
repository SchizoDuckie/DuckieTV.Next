<?php

namespace Tests\Unit\Services;

use App\Services\BackupService;
use App\Services\FavoritesService;
use App\Services\SettingsService;
use App\Services\TraktService;
use Mockery;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    public function test_restoreShow_restores_single_series()
    {
        // Mocks
        $settings = Mockery::mock(SettingsService::class);
        $favorites = Mockery::mock(FavoritesService::class);
        $trakt = Mockery::mock(TraktService::class);

        // Data
        $seriesId = '123';
        $backupData = [[], ['watched' => 1]];

        // Trakt Expectation
        $trakt->shouldReceive('serie')->with('123')->andReturn(['title' => 'Show A', 'trakt_id' => 123]);

        // Favorites Expectation - The callback is internal, so we just expect the method call
        $favorites->shouldReceive('addFavorite')->once()->andReturn(new \App\Models\Serie());

        // Service
        $service = new BackupService($settings, $favorites, $trakt);

        // Execute
        $result = $service->restoreShow($seriesId, $backupData, function($percent, $msg) {
             // assertions on callback can be done here if needed
        });

        $this->assertTrue($result);
    }
}
