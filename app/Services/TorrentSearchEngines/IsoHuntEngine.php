<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * IsoHunt search engine implementation.
 * Uses the GenericSearchEngine with IsoHunt-specific configuration.
 */
class IsoHuntEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'IsoHunt',
            'mirror' => $settings->get('mirror.IsoHunt2', 'https://isohunt.to'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/torrent/?ihq=%s&iht=0'
            ],
            'selectors' => [
                'resultContainer' => 'tr[data-key="0"]',
                'releasename' => ['td.title-row > a[href^="/torrent_details/"]', 'innerText'],
                'size' => ['td.size-row', 'innerText'],
                'seeders' => ['td.sn', 'innerText'],
                'leechers' => ['td.sn', 'innerText'],
                'detailUrl' => ['td.title-row > a[href^="/torrent_details/"]', 'href']
            ],
            'detailsSelectors' => [
                'detailsContainer' => 'div[class="row mt"]',
                'magnetUrl' => ['a:nth-of-type(2)', 'href']
            ]
        ]);
    }

    /**
     * Overriding to handle the specific magnet link extraction if needed,
     * but GenericSearchEngine might handle it if it's a simple attribute.
     * The JS had a custom function to decode mylink.cloud URLs.
     */
    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);
        
        if ($value && $propertyConfig && $propertyConfig[0] === 'a:nth-of-type(2)' && str_contains($value, 'mylink.cloud')) {
             return urldecode(str_replace('https://mylink.cloud/?url=', '', $value));
        }

        return $value;
    }
}
