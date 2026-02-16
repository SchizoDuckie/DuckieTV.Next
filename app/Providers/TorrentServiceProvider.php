<?php

namespace App\Providers;

use App\Services\TorrentSearchService;
use App\Services\TorrentClientService;
use App\Services\SettingsService;
use App\Services\TorrentSearchEngines\ThePirateBayEngine;
use App\Services\TorrentSearchEngines\OneThreeThreeSevenXEngine;
use App\Services\TorrentSearchEngines\LimeTorrentsEngine;
use App\Services\TorrentSearchEngines\NyaaEngine;
use App\Services\TorrentSearchEngines\TheRARBGEngine;
use App\Services\TorrentSearchEngines\IsoHuntEngine;
use App\Services\TorrentSearchEngines\IdopeEngine;
use App\Services\TorrentSearchEngines\KATEngine;
use App\Services\TorrentSearchEngines\ShowRSSEngine;
use App\Services\TorrentSearchEngines\KnabenEngine;
use App\Services\TorrentSearchEngines\PiratesParadiseEngine;
use App\Services\TorrentSearchEngines\TorrentDownloadsEngine;
use App\Services\TorrentSearchEngines\UindexEngine;
use App\Services\TorrentSearchEngines\ETagEngine;
use App\Services\TorrentSearchEngines\FileMoodEngine;
use App\Services\TorrentClients\QBittorrentClient;
use App\Services\TorrentClients\TransmissionClient;
use App\Services\TorrentClients\UTorrentClient;
use App\Services\TorrentClients\BiglyBTClient;
use App\Services\TorrentClients\VuzeClient;
use App\Services\TorrentClients\Aria2Client;
use App\Services\TorrentClients\DelugeClient;
use App\Services\TorrentClients\RTorrentClient;
use App\Services\TorrentClients\KTorrentClient;
use App\Services\TorrentClients\TixatiClient;
use App\Services\TorrentClients\TTorrentClient;
use App\Services\TorrentClients\UTorrentWebUIClient;
use App\Services\TorrentClients\TorrentClientInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Torrent-related services.
 * 
 * This provider registers the TorrentSearchService as a singleton and 
 * populates it with available search engines. It also binds the active
 * TorrentClientInterface implementation based on user settings.
 */
class TorrentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Tag search engine implementations
        $this->app->tag([
            ThePirateBayEngine::class,
            OneThreeThreeSevenXEngine::class,
            LimeTorrentsEngine::class,
            NyaaEngine::class,
            TheRARBGEngine::class,
            IsoHuntEngine::class,
            IdopeEngine::class,
            KATEngine::class,
            ShowRSSEngine::class,
            KnabenEngine::class,
            PiratesParadiseEngine::class,
            TorrentDownloadsEngine::class,
            UindexEngine::class,
            ETagEngine::class,
            FileMoodEngine::class,
        ], 'torrent.search_engines');

        // Tag torrent client implementations
        $this->app->tag([
            QBittorrentClient::class,
            TransmissionClient::class,
            UTorrentClient::class,
            BiglyBTClient::class,
            VuzeClient::class,
            Aria2Client::class,
            DelugeClient::class,
            RTorrentClient::class,
            KTorrentClient::class,
            TixatiClient::class,
            TTorrentClient::class,
            UTorrentWebUIClient::class,
        ], 'torrent.clients');

        $this->app->singleton(TorrentSearchService::class, function ($app) {
            return new TorrentSearchService($app->make(SettingsService::class), $app->tagged('torrent.search_engines'));
        });

        $this->app->singleton(TorrentClientService::class, function ($app) {
            return new TorrentClientService($app->make(SettingsService::class), $app->tagged('torrent.clients'));
        });

        $this->app->bind(TorrentClientInterface::class, function ($app) {
            return $app->make(TorrentClientService::class)->getActiveClient();
        });
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
