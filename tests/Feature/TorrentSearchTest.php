<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TorrentSearchService;

class TorrentSearchTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * A basic test to list registered search engines.
     */
    public function test_list_search_engines(): void
    {
        $service = app(TorrentSearchService::class);
        $engines = array_keys($service->getSearchEngines());
        
        // Dump the engines to stdout so we can see them
        fwrite(STDERR, print_r($engines, true));

        $this->assertNotEmpty($engines);
        $this->assertContains('ThePirateBay', $engines);
    }
}
