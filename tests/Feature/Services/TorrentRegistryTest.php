<?php

namespace Tests\Feature\Services;

use App\Services\TorrentSearchService;
use App\Services\TorrentClientService;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TorrentRegistryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that search engines are correctly registered and tagged.
     */
    public function test_search_engines_are_registered(): void
    {
        $searchService = $this->app->make(TorrentSearchService::class);
        $engines = $searchService->getSearchEngines();

        $this->assertArrayHasKey('ThePirateBay', $engines);
        $this->assertArrayHasKey('1337x', $engines);
        $this->assertArrayHasKey('LimeTorrents', $engines);
        $this->assertArrayHasKey('Nyaa', $engines);
        $this->assertArrayHasKey('theRARBG', $engines);
        $this->assertArrayHasKey('IsoHunt', $engines);
        $this->assertArrayHasKey('Idope', $engines);
        $this->assertArrayHasKey('KAT', $engines);
        $this->assertArrayHasKey('ShowRSS', $engines);
        $this->assertArrayHasKey('Knaben', $engines);
        $this->assertArrayHasKey('PiratesParadise', $engines);
        $this->assertArrayHasKey('TorrentDownloads', $engines);
        $this->assertArrayHasKey('Uindex', $engines);
        $this->assertArrayHasKey('ETag', $engines);
        $this->assertArrayHasKey('FileMood', $engines);

        $this->assertInstanceOf(ThePirateBayEngine::class, $engines['ThePirateBay']);
        $this->assertInstanceOf(OneThreeThreeSevenXEngine::class, $engines['1337x']);
        $this->assertInstanceOf(LimeTorrentsEngine::class, $engines['LimeTorrents']);
        $this->assertInstanceOf(NyaaEngine::class, $engines['Nyaa']);
        $this->assertInstanceOf(TheRARBGEngine::class, $engines['theRARBG']);
        $this->assertInstanceOf(IsoHuntEngine::class, $engines['IsoHunt']);
        $this->assertInstanceOf(IdopeEngine::class, $engines['Idope']);
        $this->assertInstanceOf(KATEngine::class, $engines['KAT']);
        $this->assertInstanceOf(ShowRSSEngine::class, $engines['ShowRSS']);
        $this->assertInstanceOf(KnabenEngine::class, $engines['Knaben']);
        $this->assertInstanceOf(PiratesParadiseEngine::class, $engines['PiratesParadise']);
        $this->assertInstanceOf(TorrentDownloadsEngine::class, $engines['TorrentDownloads']);
        $this->assertInstanceOf(UindexEngine::class, $engines['Uindex']);
        $this->assertInstanceOf(ETagEngine::class, $engines['ETag']);
        $this->assertInstanceOf(FileMoodEngine::class, $engines['FileMood']);
    }

    /**
     * Test that torrent clients are correctly registered and tagged.
     */
    public function test_torrent_clients_are_registered(): void
    {
        $clientService = $this->app->make(TorrentClientService::class);
        $availableClients = $clientService->getAvailableClients();

        $this->assertContains('qBittorrent 4.1+', $availableClients);
        $this->assertContains('Transmission', $availableClients);
        $this->assertContains('uTorrent', $availableClients);

        $this->assertInstanceOf(QBittorrentClient::class, $clientService->getClient('qBittorrent 4.1+'));
        $this->assertInstanceOf(TransmissionClient::class, $clientService->getClient('Transmission'));
        $this->assertInstanceOf(UTorrentClient::class, $clientService->getClient('uTorrent'));
    }

    /**
     * Test active client resolution based on settings.
     */
    public function test_active_client_resolution(): void
    {
        $clientService = $this->app->make(TorrentClientService::class);

        // Default should be uTorrent (per SettingsService.php defaults)
        $this->assertEquals('uTorrent', $clientService->getActiveClient()->getName());

        // Change setting and check again
        $settings = $this->app->make(\App\Services\SettingsService::class);
        $settings->set('torrenting.client', 'Transmission');

        // Refresh service to ensure setting is picked up (or if it's not a singleton that caches the client)
        $this->assertEquals('Transmission', $clientService->getActiveClient()->getName());
    }
}
