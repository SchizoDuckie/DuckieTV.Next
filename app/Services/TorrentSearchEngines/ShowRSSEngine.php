<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * ShowRSS.info search engine implementation.
 * Note: ShowRSS requires a specific query format: 'Showname SXXEXX'.
 * It first scrapes the show list to find the ID for the show,
 * then scrapes the show's page for the specific episode.
 */
class ShowRSSEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'ShowRSS',
            'mirror' => $settings->get('mirror.ShowRSS', 'https://showrss.info'),
        ]);
    }

    public function search(string $query, ?string $sortBy = null): array
    {
        $query = strtoupper($query);
        
        // Match SXXEXX format as per original JS implementation
        if (!preg_match('/S([0-9]{1,2})E([0-9]{1,3})/', $query, $parts)) {
            return [];
        }

        try {
            // Step 1: Get the show list to find the ID
            $response = Http::get($this->config['mirror'] . '/browse');
            if (!$response->successful()) {
                return [];
            }

            $crawler = new \Symfony\Component\DomCrawler\Crawler($response->body());
            $shows = [];
            $crawler->filter('select option')->each(function (\Symfony\Component\DomCrawler\Crawler $node) use (&$shows) {
                if ($node->attr('value') !== '') {
                    $shows[trim($node->text())] = $node->attr('value');
                }
            });

            // Find the show that starts the query
            $foundShowId = null;
            foreach ($shows as $name => $id) {
                if (str_starts_with($query, strtoupper($name))) {
                    $foundShowId = $id;
                    break;
                }
            }

            if (!$foundShowId) {
                return [];
            }

            // Step 2: Get the show's page
            $serieResponse = Http::get($this->config['mirror'] . '/browse/' . $foundShowId);
            if (!$serieResponse->successful()) {
                return [];
            }

            $crawler = new \Symfony\Component\DomCrawler\Crawler($serieResponse->body());
            $results = [];

            // Pattern for matching specific episode in ShowRSS format (e.g. 5x02)
            $season = (int)$parts[1];
            $episode = $parts[2];
            $showRSSMatch = "{$season}×{$episode}";

            $crawler->filter('div.col-md-10 ul.user-timeline li > a')->each(function (\Symfony\Component\DomCrawler\Crawler $node) use (&$results, $showRSSMatch, $query) {
                $releaseName = trim(str_replace("\n", " ", $node->text()));
                
                if (str_contains($releaseName, $showRSSMatch)) {
                    $magnetUrl = $node->attr('href');
                    $results[] = [
                        'releasename' => $releaseName,
                        'magnetUrl' => $magnetUrl,
                        'size' => 'n/a',
                        'seeders' => 1,
                        'leechers' => 0,
                        'detailUrl' => $this->config['mirror'] . '/browse/',
                        'noMagnet' => false,
                        'noTorrent' => false,
                        'torrentUrl' => $this->buildTorrentUrl($magnetUrl, $releaseName)
                    ];
                }
            });

            return $results;

        } catch (Exception $e) {
            return [];
        }
    }

    protected function buildTorrentUrl(string $magnetUrl, string $releaseName): string
    {
        if (preg_match('/([0-9ABCDEFabcdef]{40})/', $magnetUrl, $matches)) {
            return 'http://itorrents.org/torrent/' . strtoupper($matches[0]) . '.torrent?title=' . urlencode($releaseName);
        }
        return '';
    }
}
