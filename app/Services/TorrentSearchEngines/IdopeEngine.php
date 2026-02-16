<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * Idope search engine implementation.
 */
class IdopeEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'Idope',
            'mirror' => $settings->get('mirror.Idope', 'https://idope.se'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/torrent-list/%s/?&o=%o',
            ],
            'selectors' => [
                'resultContainer' => 'div.resultdiv',
                'releasename' => ['div.resultdivtopname', 'innerText'],
                'seeders' => ['div.resultdivbottonseed', 'innerText'],
                'leechers' => ['div.resultdivbottonseed', 'innerText'],
                'size' => ['div.resultdivbottonlength', 'innerText'],
                'detailUrl' => ['div.resultdivtop a', 'href'],
                'magnetUrl' => ['.hideinfohash', 'innerText'],
            ],
            'orderby' => [
                'age' => ['d' => '-3', 'a' => '3'],
                'seeders' => ['d' => '-1', 'a' => '1'],
                'size' => ['d' => '-2', 'a' => '2'],
            ],
        ]);
    }

    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);

        if ($value && $propertyConfig && $propertyConfig[0] === '.hideinfohash') {
            return 'magnet:?xt=urn:btih:'.trim($value);
        }

        return $value;
    }
}
