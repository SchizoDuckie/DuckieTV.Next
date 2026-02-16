<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Serie;
use Carbon\Carbon;

class SceneNameResolverService
{
    /**
     * Credits to Sickbeard's exception list and DuckieTV's curated JSONs.
     * For now, we'll implement the core filtering logic. 
     * In a full implementation, these would be loaded from the JSON endpoints.
     */
    protected array $exceptions = [];
    protected array $episodesWithDateFormat = [];

    /**
     * Replace common diacritics.
     * Ported from SceneNameResolver.js:25
     */
    public function replaceDiacritics(string $source): string
    {
        $search = ['À','Á','Â','Ã','Ä','Å','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ç','à','á','â','ã','ä','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','õ','ö','ù','ú','û','ü','ç'];
        $replace = ['A','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C','a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'];
        
        return str_replace($search, $replace, $source);
    }

    /**
     * Strip bracketed year and special characters.
     * Ported from SceneNameResolver.js:32
     */
    public function filterName(string $source): string
    {
        $source = $this->replaceDiacritics($source);
        // replace /\(([12][09][0-9]{2})\)/ with ''
        $source = preg_replace('/\(([12][09][0-9]{2})\)/', '', $source);
        // replace /[^0-9a-zA-Z- ]/g with ''
        return preg_replace('/[^0-9a-zA-Z- ]/', '', $source);
    }

    /**
     * Get the search string for an episode.
     * Ported from SceneNameResolver.js:45
     */
    public function getSearchStringForEpisode(Serie $serie, Episode $episode): string
    {
        $append = (!empty($serie->custom_search_string)) ? ' ' . $serie->custom_search_string : '';
        $traktID = (int) $serie->trakt_id;

        // Note: In original, exceptions are loaded from remote JSON. 
        // We'll use the series name filter as default.
        $sceneName = (isset($this->exceptions[$traktID])) ? $this->exceptions[$traktID] . ' ' : $this->filterName($serie->name) . ' ';
        
        if (!empty($serie->alias)) {
            $sceneName = $serie->alias . ' ';
        }

        if (isset($this->episodesWithDateFormat[$traktID])) {
            $format = $this->episodesWithDateFormat[$traktID];
            $dt = Carbon::createFromTimestampMs($episode->firstaired);
            
            // Handle DD modifiers like DD[-1]
            if (str_contains($format, 'DD[')) {
                preg_match('/\[([+-][0-9]+)\]/', $format, $matches);
                if (!empty($matches)) {
                    $modifier = (int) $matches[1];
                    $format = str_replace($matches[0], '', $format);
                    $dt->addDays($modifier);
                }
            }
            
            return $sceneName . $dt->format($this->convertMomentToFileFormat($format)) . $append;
        }

        // Fallback to SxxExx format (matches SceneXemResolver fallback in original)
        return $sceneName . $episode->getFormattedEpisode() . $append;
    }

    /**
     * Basic conversion of Moment.js formats to PHP date formats.
     */
    protected function convertMomentToFileFormat(string $format): string
    {
        $map = [
            'YYYY' => 'Y',
            'YY' => 'y',
            'MMMM' => 'F',
            'MMM' => 'M',
            'MM' => 'm',
            'M' => 'n',
            'DD' => 'd',
            'D' => 'j',
            'YYYY-MM-DD' => 'Y-m-d',
        ];
        return strtr($format, $map);
    }
}
