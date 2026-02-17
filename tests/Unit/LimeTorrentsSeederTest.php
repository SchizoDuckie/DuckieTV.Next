<?php

namespace Tests\Unit;

use App\Services\SettingsService;
use App\Services\TorrentSearchEngines\LimeTorrentsEngine;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class LimeTorrentsSeederTest extends TestCase
{
    public function test_parses_seeders_correctly()
    {
        $html = <<<HTML
        <table class="table2">
            <tr bgcolor="#F4F4F4">
                <td>Category</td>
                <td>
                    <div class="tt-name"><span></span><a href="/torrent/LINKS">Release Name</a></div>
                </td>
                <td>1.5 GB</td>
                <td>4,000</td> <!-- Comma separate -->
                <td>100</td>
            </tr>
            <tr bgcolor="#F4F4F4">
                <td>Category</td>
                <td>
                    <div class="tt-name"><span></span><a href="/torrent/LINKS2">Release Name 2</a></div>
                </td>
                <td>1.5 GB</td>
                <td>5 000</td> <!-- Space separated -->
                <td>100</td>
            </tr>
             <tr bgcolor="#F4F4F4">
                <td>Category</td>
                <td>
                    <div class="tt-name"><span></span><a href="/torrent/LINKS3">Release Name 3</a></div>
                </td>
                <td>1.5 GB</td>
                <td>6.000</td> <!-- Dot separated (EU) -->
                <td>100</td>
            </tr>
             <tr bgcolor="#F4F4F4">
                <td>Category</td>
                <td>
                    <div class="tt-name"><span></span><a href="/torrent/LINKS4">Release Name 4</a></div>
                </td>
                <td>1.5 GB</td>
                <td>324</td> <!-- Normal -->
                <td>100</td>
            </tr>
        </table>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        // Mock SettingsService
        $settings = \Mockery::mock(SettingsService::class);
        $settings->shouldReceive('get')
            ->with('mirror.LimeTorrents', 'https://www.limetorrents.info')
            ->andReturn('https://www.limetorrents.info');

        $engine = new LimeTorrentsEngine($settings);

        $results = $engine->search('test');

        $this->assertCount(4, $results);

        // 4,000 -> 4000
        $this->assertEquals(4000, $results[0]['seeders'], 'Failed to parse comma-separated seeders');

        // 5 000 -> 5000 (Expectation: might fail if code only replaces commas)
        $this->assertEquals(5000, $results[1]['seeders'], 'Failed to parse space-separated seeders');

        // 6.000 -> 6000 (Expectation: might fail if code doesn't handle dots, or treats as decimal)
        $this->assertEquals(6000, $results[2]['seeders'], 'Failed to parse dot-separated seeders');

        $this->assertEquals(324, $results[3]['seeders'], 'Failed to parse normal seeders');
    }
}
