<?php

namespace Tests\Feature\Services;

use App\Services\TorrentSearchEngines\GenericSearchEngine;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenericSearchEngineTest extends TestCase
{
    protected array $mockConfig = [
        'name' => 'MockEngine',
        'mirror' => 'https://mock.engine',
        'includeBaseURL' => true,
        'endpoints' => [
            'search' => '/search/%s'
        ],
        'selectors' => [
            'resultContainer' => '.result',
            'releasename' => ['.title', 'innerText'],
            'magnetUrl' => ['.magnet', 'href'],
            'size' => ['.size', 'innerText'],
            'seeders' => ['.seeders', 'innerText'],
            'leechers' => ['.leechers', 'innerText'],
            'detailUrl' => ['.title', 'href']
        ]
    ];

    public function test_it_can_parse_search_results()
    {
        $html = '
            <div class="result">
                <a class="title" href="/details/1">Release 1</a>
                <a class="magnet" href="magnet:?xt=urn:btih:HASH1">Magnet</a>
                <span class="size">1.5 GB</span>
                <span class="seeders">100</span>
                <span class="leechers">50</span>
            </div>
            <div class="result">
                <a class="title" href="/details/2">Release 2</a>
                <a class="magnet" href="magnet:?xt=urn:btih:HASH2">Magnet</a>
                <span class="size">800 MB</span>
                <span class="seeders">200</span>
                <span class="leechers">10</span>
            </div>
        ';

        Http::fake([
            'https://mock.engine/search/test_query' => Http::response($html, 200)
        ]);

        $engine = new GenericSearchEngine($this->mockConfig);
        $results = $engine->search('test_query');

        $this->assertCount(2, $results);
        
        $this->assertEquals('Release 1', $results[0]['releasename']);
        $this->assertEquals('1,500.00 MB', $results[0]['size']);
        $this->assertEquals(100, $results[0]['seeders']);
        $this->assertEquals(50, $results[0]['leechers']);
        $this->assertEquals('magnet:?xt=urn:btih:HASH1', $results[0]['magnetUrl']);
        $this->assertEquals('https://mock.engine/details/1', $results[0]['detailUrl']);

        $this->assertEquals('Release 2', $results[1]['releasename']);
        $this->assertEquals('800.00 MB', $results[1]['size']);
    }

    public function test_it_handles_size_conversion()
    {
        $engine = new GenericSearchEngine($this->mockConfig);
        
        // Use reflection to test protected sizeToMB method
        $reflection = new \ReflectionClass(GenericSearchEngine::class);
        $method = $reflection->getMethod('sizeToMB');
        $method->setAccessible(true);

        $this->assertEquals('1,000.00 MB', $method->invokeArgs($engine, ['1 GB']));
        $this->assertEquals('1,000.00 MB', $method->invokeArgs($engine, ['1000 MB']));
        $this->assertEquals('0.50 MB', $method->invokeArgs($engine, ['500 KB']));
        $this->assertEquals('1.05 MB', $method->invokeArgs($engine, ['1 MiB']));
        $this->assertEquals('1,073.74 MB', $method->invokeArgs($engine, ['1 GiB']));
    }
}
