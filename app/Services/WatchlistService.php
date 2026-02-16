<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class WatchlistService
{
    protected string $archiveUrl = 'https://torrentfreak.com/most-pirated-movies-of-%s/';

    /**
     * Fetch the TorrentFreak Top 10 archive for the current year.
     * Ported from TorrentFreak.js:65
     */
    public function getTop10Movies(): array
    {
        $year = date('Y');
        $url = str_replace('%s', $year, $this->archiveUrl);

        try {
            $response = Http::get($url);
            if (!($response instanceof \Illuminate\Http\Client\Response) || !$response->successful()) {
                return [];
            }

            return $this->parseTables($response->body());
        } catch (\Exception $e) {
            Log::error("Failed to fetch TorrentFreak Top 10: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse the HTML tables from TorrentFreak.
     * Ported from TorrentFreak.js:19
     */
    protected function parseTables(string $html): array
    {
        $crawler = new Crawler($html);
        $tables = $crawler->filter('table.css.hover');
        $titles = $crawler->filter('h2');
        
        $output = [];
        
        $tables->each(function (Crawler $table, $i) use ($titles, &$output) {
            $title = $titles->eq($i)->text();
            $movies = [];

            $table->filter('tbody tr')->each(function (Crawler $row) use (&$movies) {
                $cols = $row->filter('td');
                if ($cols->count() < 4) return;

                try {
                    $movies[] = [
                        'rank' => $cols->eq(0)->text(),
                        'prevRank' => str_replace(['(', ')'], '', $cols->eq(1)->text()),
                        'title' => $cols->eq(2)->text(),
                        'searchTitle' => $cols->eq(2)->filter('a')->count() > 0 
                            ? $cols->eq(2)->filter('a')->text() 
                            : $cols->eq(2)->text(),
                        'rating' => $cols->eq(3)->filter('a')->count() > 0 
                            ? $cols->eq(3)->filter('a')->first()->text() 
                            : '?',
                        'imdb' => $cols->eq(3)->filter('a')->count() > 0 
                            ? $cols->eq(3)->filter('a')->first()->attr('href') 
                            : '',
                        'trailer' => $cols->eq(3)->filter('a')->count() == 2 
                            ? $cols->eq(3)->filter('a')->eq(1)->attr('href') 
                            : '',
                    ];
                } catch (\Exception $e) {
                    Log::warning("Parse error in TorrentFreak row: " . $e->getMessage());
                }
            });

            if (!empty($movies)) {
                array_unshift($output, [
                    'title' => $title,
                    'top10' => $movies
                ]);
            }
        });

        return $output;
    }
}
