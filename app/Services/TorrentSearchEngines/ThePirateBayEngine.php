<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * @see ThePirateBay.js in DuckieTV-angular for original implementation.
 */
class ThePirateBayEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'ThePirateBay',
            'mirror' => $settings->get('mirror.ThePirateBay', 'https://thepiratebay.org'),
            'includeBaseURL' => false,
            'endpoints' => [
                'search' => '/search/%s/0/%o/0',
            ],
            'selectors' => [
                'resultContainer' => '#searchResult tbody tr',
                'releasename' => ['td:nth-child(2) > div', 'innerText'],
                'magnetUrl' => ['td:nth-child(2) > a', 'href'],
                'size' => ['td:nth-child(2) .detDesc', 'innerText'], // Custom parser logic in PHP
                'seeders' => ['td:nth-child(3)', 'innerHTML'],
                'leechers' => ['td:nth-child(4)', 'innerHTML'],
                'detailUrl' => ['a.detLink', 'href'],
            ],
            'orderby' => [
                'age' => ['d' => '3', 'a' => '4'],
                'leechers' => ['d' => '9', 'a' => '10'],
                'seeders' => ['d' => '7', 'a' => '8'],
                'size' => ['d' => '5', 'a' => '6'],
            ],
        ]);
    }

    protected function getPropertyForSelector(\Symfony\Component\DomCrawler\Crawler $node, ?array $propertyConfig): ?string
    {
        $value = parent::getPropertyForSelector($node, $propertyConfig);

        if ($value && $propertyConfig === $this->config['selectors']['size']) {
            // Equivalent to text.split(', ')[1].split(' ')[1].replace('i', '')
            $parts = explode(', ', $value);
            if (count($parts) > 1) {
                $subParts = explode(' ', $parts[1]);
                if (count($subParts) > 1) {
                    return str_replace('i', '', $subParts[1]);
                }
            }
        }

        return $value;
    }
}
