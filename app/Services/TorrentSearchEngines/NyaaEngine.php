<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * Nyaa.si search engine implementation.
 * Uses the GenericSearchEngine with Nyaa-specific configuration.
 */
class NyaaEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'Nyaa',
            'mirror' => $settings->get('mirror.Nyaa', 'https://nyaa.si'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/?q=%s&f=0&c=0_0%o',
            ],
            'selectors' => [
                'resultContainer' => 'tr',
                'releasename' => ['td:nth-of-type(2) a:last-of-type', 'innerText'],
                'magnetUrl' => ['td:nth-of-type(3) a[href^="magnet:?"]', 'href'],
                'torrentUrl' => ['td:nth-of-type(3) a[href$=".torrent"]', 'href'],
                'size' => ['td:nth-of-type(4)', 'innerText'],
                'seeders' => ['td:nth-of-type(6)', 'innerText'],
                'leechers' => ['td:nth-of-type(7)', 'innerText'],
                'detailUrl' => ['td:nth-of-type(2) a:last-of-type', 'href'],
            ],
            'orderby' => [
                'age' => ['d' => '&s=id&o=desc', 'a' => '&s=id&o=asc'],
                'seeders' => ['d' => '&s=seeders&o=desc', 'a' => '&s=seeders&o=asc'],
                'leechers' => ['d' => '&s=leechers&o=desc', 'a' => '&s=leechers&o=asc'],
                'size' => ['d' => '&s=size&o=desc', 'a' => '&s=size&o=asc'],
            ],
        ]);
    }
}
