<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * KAT (Kickass Torrents) search engine implementation.
 */
class KATEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'KAT',
            'mirror' => $settings->get('mirror.KATws', 'https://kickasstorrents.to'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/usearch/%s/?%o'
            ],
            'selectors' => [
                'resultContainer' => 'tr.odd, tr.even',
                'releasename' => ['a.cellMainLink', 'innerText'],
                'size' => ['td:nth-child(2)', 'innerText'],
                'seeders' => ['td:nth-child(5)', 'innerText'],
                'leechers' => ['td:nth-child(6)', 'innerText'],
                'magnetUrl' => ['a[data-download]', 'href'],
                'detailUrl' => ['a.cellMainLink ', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'field=time_add&sorder=desc', 'a' => 'field=time_add&sorder=asc'],
                'leechers' => ['d' => 'field=leechers&sorder=desc', 'a' => 'field=leechers&sorder=asc'],
                'seeders' => ['d' => 'field=seeders&sorder=desc', 'a' => 'field=seeders&sorder=asc'],
                'size' => ['d' => 'field=size&sorder=desc', 'a' => 'field=size&sorder=asc']
            ]
        ]);
    }

    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);
        
        if ($value && $propertyConfig && ($propertyConfig[0] === 'a[data-download]' || $propertyConfig[0] === 'td:nth-child(1) > div > a[data-download=""]')) {
             $decoded = urldecode($value);
             if (str_contains($decoded, 'url=')) {
                 return substr($decoded, strpos($decoded, 'url=') + 4);
             }
             return $decoded;
        }

        return $value;
    }
}
