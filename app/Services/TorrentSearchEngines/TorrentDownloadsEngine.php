<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * TorrentDownloads search engine implementation.
 */
class TorrentDownloadsEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'TorrentDownloads',
            'mirror' => $settings->get('mirror.TorrentDownloads', 'https://torrentdownloads.pro'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search/?search=%s&s=0&new=1&o=%o'
            ],
            'selectors' => [
                'resultContainer' => 'div.grey_bar3',
                'releasename' => ['p:nth-of-type(1) a', 'innerText'],
                'size' => ['span:nth-of-type(3)', 'innerText'],
                'seeders' => ['span:nth-of-type(4)', 'innerText'],
                'leechers' => ['span:nth-of-type(5)', 'innerText'],
                'detailUrl' => ['p:nth-of-type(1) a', 'href']
            ],
            'detailsSelectors' => [
                'detailsContainer' => 'div.contact-form',
                'magnetUrl' => ['a[href^="magnet:?"]', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'added_desc', 'a' => 'added_asc'],
                'seeders' => ['d' => 'seeders_desc', 'a' => 'seeders_asc'],
                'leechers' => ['d' => 'leechers_desc', 'a' => 'leechers_asc'],
                'size' => ['d' => 'size_desc', 'a' => 'size_asc']
            ]
        ]);
    }
}
