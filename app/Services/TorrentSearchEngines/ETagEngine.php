<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * ETag search engine implementation.
 */
class ETagEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'ETag',
            'mirror' => $settings->get('mirror.ETag', 'https://etag.to'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search/?search=%s&srt=%o&new=1&x=0&y=0'
            ],
            'selectors' => [
                'resultContainer' => 'tr[class^="tl"]',
                'releasename' => ['a[href^="/torrent/"]', 'innerText'],
                'magnetUrl' => ['a[href^="magnet:?xt="]', 'href'],
                'seeders' => ['td.sy, td.sn', 'innerText'],
                'leechers' => ['td.ly, td.ln', 'innerText'],
                'size' => ['td:nth-last-of-type(4)', 'innerText'],
                'detailUrl' => ['a[href^="/torrent/"]', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'added&order=desc', 'a' => 'added&order=asc'],
                'seeders' => ['d' => 'seeds&order=desc', 'a' => 'seeds&order=asc'],
                'leechers' => ['d' => 'leechers&order=desc', 'a' => 'leechers&order=asc'],
                'size' => ['d' => 'size&order=desc', 'a' => 'size&order=asc']
            ]
        ]);
    }
}
