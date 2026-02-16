<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * TheRARBG search engine implementation.
 * Uses the GenericSearchEngine with theRARBG-specific configuration.
 */
class TheRARBGEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'theRARBG',
            'mirror' => $settings->get('mirror.theRARBG', 'https://therarbg.to'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/get-posts/keywords:%s:order:%o/'
            ],
            'selectors' => [
                'resultContainer' => 'tr.list-entry',
                'releasename' => ['a[href^="/post-detail/"]', 'innerText'],
                'seeders' => ['td:nth-child(7)', 'innerText'],
                'leechers' => ['td:nth-child(8)', 'innerText'],
                'size' => ['td.sizeCell', 'innerText'],
                'detailUrl' => ['a[href^="/post-detail/"]', 'href']
            ],
            'detailsSelectors' => [
                'detailsContainer' => 'table.detailTable',
                'magnetUrl' => ['a[href^="magnet:?"]', 'href']
            ],
            'orderby' => [
                'age' => ['d' => '-a', 'a' => 'a'],
                'seeders' => ['d' => '-se', 'a' => 'se'],
                'leechers' => ['d' => '-le', 'a' => 'le'],
                'size' => ['d' => '-s', 'a' => 's']
            ]
        ]);
    }
}
