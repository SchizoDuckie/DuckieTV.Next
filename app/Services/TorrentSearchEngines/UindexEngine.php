<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * Uindex search engine implementation.
 */
class UindexEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'Uindex',
            'mirror' => $settings->get('mirror.Uindex', 'http://uindex.net'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search.php?search=%s'
            ],
            'selectors' => [
                'resultContainer' => 'table.maintable > tbody > tr',
                'releasename' => ['a[href^="/details.php?id="]', 'innerText'],
                'seeders' => ['td:nth-child(4)', 'innerText'],
                'leechers' => ['td:nth-child(5)', 'innerText'],
                'size' => ['td:nth-child(3)', 'innerText'],
                'detailUrl' => ['a[href^="/details.php?id="]', 'href'],
                'magnetUrl' => ['a[href^="magnet:?xt="]', 'href']
            ],
        ]);
    }
}
