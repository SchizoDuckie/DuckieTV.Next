<?php

namespace App\Services\TorrentSearchEngines;

use App\Services\SettingsService;

/**
 * LimeTorrents search engine implementation.
 * 
 * @see LimeTorrents.js in DuckieTV-angular for original implementation.
 */
class LimeTorrentsEngine extends GenericSearchEngine
{
    /**
     * @param SettingsService $settings
     */
    public function __construct(SettingsService $settings)
    {
        parent::__construct([
            'name' => 'LimeTorrents',
            'mirror' => $settings->get('mirror.LimeTorrents', 'https://www.limetorrents.info'),
            'includeBaseURL' => true,
            'endpoints' => [
                'search' => '/search/all/%s/%o'
            ],
            'selectors' => [
                'resultContainer' => 'tr[bgcolor^="#F"]',
                'releasename' => ['td div a:nth-child(2)', 'innerText'],
                'seeders' => ['td:nth-child(4)', 'innerText'],
                'leechers' => ['td:nth-child(5)', 'innerText'],
                'size' => ['td:nth-child(3)', 'innerText'],
                'detailUrl' => ['td div a:nth-child(2)', 'href']
            ],
            'detailsSelectors' => [
                'detailsContainer' => 'div.torrentinfo',
                'magnetUrl' => ['a[title$="agnet"]', 'href'],
                'torrentUrl' => ['a[title$="orrent"]', 'href']
            ],
            'orderby' => [
                'age' => ['d' => 'date/1/', 'a' => 'date/1/'],
                'seeders' => ['d' => 'seeds/1/', 'a' => 'seeds/1/'],
                'leechers' => ['d' => 'leechs/desc', 'a' => 'leechs/asc'],
                'size' => ['d' => 'size/1/', 'a' => 'size/1/']
            ]
        ]);
    }
}
