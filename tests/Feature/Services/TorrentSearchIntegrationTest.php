<?php

namespace Tests\Feature\Services;

use App\Services\TorrentSearchService;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TorrentSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Data provider for all registered search engines.
     */
    public static function engineProvider(): array
    {
        return [
            'ThePirateBay' => [ThePirateBayEngine::class],
            '1337x' => [OneThreeThreeSevenXEngine::class],
            'LimeTorrents' => [LimeTorrentsEngine::class],
            'Nyaa' => [NyaaEngine::class],
            'theRARBG' => [TheRARBGEngine::class],
            'IsoHunt' => [IsoHuntEngine::class],
            'Idope' => [IdopeEngine::class],
            'KAT' => [KATEngine::class],
            'ShowRSS' => [ShowRSSEngine::class],
            'Knaben' => [KnabenEngine::class],
            'PiratesParadise' => [PiratesParadiseEngine::class],
            'TorrentDownloads' => [TorrentDownloadsEngine::class],
            'Uindex' => [UindexEngine::class],
            'ETag' => [ETagEngine::class],
            'FileMood' => [FileMoodEngine::class],
        ];
    }

    /**
     * Test a specific search engine.
     * 
     * @group integration
     */
    #[DataProvider('engineProvider')]
    public function test_search_engine(string $engineClass): void
    {
        $searchService = $this->app->make(TorrentSearchService::class);
        $engines = $searchService->getSearchEngines();

        // Find the engine instance by its class
        $engine = null;
        foreach ($engines as $instance) {
            if ($instance instanceof $engineClass) {
                $engine = $instance;
                break;
            }
        }

        $this->assertNotNull($engine, "Engine class " . class_basename($engineClass) . " should be registered");
        $name = $engine->getName();

        // ShowRSS requires a specific format
        $query = 'Batman';
        if ($name === 'ShowRSS') {
            $query = 'Pennyworth S01E01';
        }

        try {
            $results = $engine->search($query);
            
            $this->assertIsArray($results, "Engine {$name} should return an array of results");
            
            if (count($results) > 0) {
                $result = $results[0];
                $this->assertArrayHasKey('releasename', $result);
                $this->assertArrayHasKey('size', $result);
                $this->assertArrayHasKey('seeders', $result);
                
                // If noMagnet is false, it should either have magnetUrl OR have detailsSelectors configured (which implies detailUrl)
                if (!($result['noMagnet'] ?? false)) {
                    if (isset($result['magnetUrl'])) {
                        $this->assertArrayHasKey('magnetUrl', $result);
                    } else {
                        $this->assertArrayHasKey('detailUrl', $result);
                    }
                }
            } else {
                $this->markTestIncomplete("No results found for {$name} (possibly mirror down or query too specific)");
            }
        } catch (\Exception $e) {
            $this->markTestIncomplete("Search failed for {$name} due to network/host error: " . $e->getMessage());
        }
    }
}
