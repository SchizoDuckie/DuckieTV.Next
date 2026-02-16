<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * PiratesParadise search engine implementation.
 */
class PiratesParadiseEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'PiratesParadise',
            'mirror' => $settings->get('mirror.PiratesParadise', 'https://pirates-paradise.com'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search.php?q=%s&sort=%o',
            ],
            'selectors' => [
                'resultContainer' => 'table > tbody > tr',
                'releasename' => ['a.name-link', 'innerText'],
                'size' => ['td:nth-child(2)', 'innerText'],
                'seeders' => ['span.seeds', 'innerText'],
                'leechers' => ['span.peers', 'innerText'],
                'magnetUrl' => ['button.magnet-btn', 'onclick'],
                'detailUrl' => ['a.name-link', 'href'],
            ],
            'orderby' => [
                'age' => ['d' => 'fetch_date&order=desc', 'a' => 'fetch_date&order=asc'],
                'seeders' => ['d' => 'seeds&order=desc', 'a' => 'seeds&order=asc'],
                'leechers' => ['d' => 'peers&order=desc', 'a' => 'peers&order=asc'],
                'size' => ['d' => 'total_size&order=desc', 'a' => 'total_size&order=asc'],
            ],
        ]);
    }

    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);

        if ($value && $propertyConfig && $propertyConfig[0] === 'button.magnet-btn') {
            if (preg_match('/([0-9ABCDEFabcdef]{40})/', $value, $matches)) {
                return 'magnet:?xt=urn:btih:'.$matches[1];
            }
        }

        return $value;
    }
}
