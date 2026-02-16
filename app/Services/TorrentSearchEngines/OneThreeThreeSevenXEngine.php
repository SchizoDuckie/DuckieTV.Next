<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 1337x search engine implementation.
 * 
 * @see 1337x.js in DuckieTV-angular for original implementation.
 */
class OneThreeThreeSevenXEngine extends GenericSearchEngine
{
    /**
     * @param SettingsService $settings
     */
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => '1337x',
            'mirror' => $settings->get('mirror.1337x', 'https://1337x.to'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/sort-search/%s/%o/1/'
            ],
            'selectors' => [
                'resultContainer' => 'tr',
                'releasename' => ['td.coll-1 a:nth-of-type(2)', 'innerText'],
                'seeders' => ['td.coll-2', 'innerText'],
                'leechers' => ['td.coll-3', 'innerText'],
                'size' => ['td.coll-4', 'innerHTML'],
                'detailUrl' => ['td.coll-1 a:nth-of-type(2)', 'href']
            ],
            'detailsSelectors' => [
                'detailsContainer' => 'div.no-top-radius',
                'magnetUrl' => ['ul li a[href^="magnet:?"]', 'href'],
                'torrentUrl' => ['ul li a[href^="http://itorrents.org/"]', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'time/desc', 'a' => 'time/asc'],
                'seeders' => ['d' => 'seeders/desc', 'a' => 'seeders/asc'],
                'leechers' => ['d' => 'leechers/desc', 'a' => 'leechers/asc'],
                'size' => ['d' => 'size/desc', 'a' => 'size/asc']
            ]
        ]);
    }

    /**
     * Custom property extractor for 1337x.
     * 
     * Handles specific parsing logic for the 'size' field.
     * 
     * @param Crawler $node
     * @param array|null $propertyConfig
     * @return string|null
     */
    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);
        
        if ($value && $propertyConfig === $this->config['selectors']['size']) {
            // Equivalent to text.split('<')[0]
            $parts = explode('<', $value);
            return trim($parts[0]);
        }
        
        return $value;
    }
}
