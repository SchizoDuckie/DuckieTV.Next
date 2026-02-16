<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * Knaben search engine implementation.
 */
class KnabenEngine extends GenericSearchEngine
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'Knaben',
            'mirror' => $settings->get('mirror.Knaben', 'https://knaben.eu'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search/torrents?q=%s&o=%o'
            ],
            'selectors' => [
                'resultContainer' => 'tr.text-nowrap.border-start',
                'releasename' => ['td.text-wrap.w-100 a', 'innerText'],
                'magnetUrl' => ['td.text-wrap.w-100 a[href^="magnet:?"]', 'href'],
                'size' => ['td:nth-last-child(3)', 'innerText'],
                'seeders' => ['td:nth-last-child(2)', 'innerText'],
                'leechers' => ['td:nth-last-child(1)', 'innerText'],
                'detailUrl' => ['td.text-wrap.w-100 a', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'age_desc', 'a' => 'age_asc'],
                'seeders' => ['d' => 'seeders_desc', 'a' => 'seeders_asc'],
                'size' => ['d' => 'size_desc', 'a' => 'size_asc']
            ]
        ]);
    }
}
