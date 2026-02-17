<?php

namespace App\Services;

/**
 * Port of DuckieTV-angular/js/services/SeriesMetaTranslations.js
 */
class SeriesMetaTranslations
{
    public const GENRES = [
        'action', 'adventure', 'animation', 'anime', 'biography', 'children', 'comedy', 'crime', 'disaster', 'documentary', 'drama', 'eastern', 'family', 'fan-film', 'fantasy', 'film-noir', 'food', 'game-show', 'history', 'holiday', 'home-and-garden', 'horror', 'indie', 'mini-series', 'music', 'musical', 'mystery', 'news', 'none', 'reality', 'road', 'romance', 'science-fiction', 'short', 'soap', 'special-interest', 'sports', 'sporting-event', 'superhero', 'suspense', 'talk-show', 'thriller', 'travel', 'tv-movie', 'war', 'western'
    ];

    public const STATUSES = [
        'canceled', 'ended', 'in production', 'returning series', 'planned'
    ];

    public const DAYS_OF_WEEK = [
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
    ];

    /**
     * Translate a genre using the GENRELIST localization key.
     */
    public function translateGenre(?string $genre): string
    {
        if (!$genre) return '';
        $genre = strtolower(trim($genre));
        $idx = array_search($genre, self::GENRES);
        
        $translatedList = explode('|', __('GENRELIST'));
        return ($idx !== false && isset($translatedList[$idx])) ? $translatedList[$idx] : ucfirst($genre);
    }

    /**
     * Translate a status using the STATUSLIST localization key.
     */
    public function translateStatus(?string $status): string
    {
        if (!$status) return '';
        $status = strtolower(trim($status));
        $idx = array_search($status, self::STATUSES);
        
        $translatedList = explode('|', __('STATUSLIST'));
        return ($idx !== false && isset($translatedList[$idx])) ? $translatedList[$idx] : ucfirst($status);
    }

    /**
     * Translate a day of the week.
     * Note: Laravel/PHP handles this using Carbon's localization.
     */
    public function translateDayOfWeek(string $day): string
    {
        try {
            return \Carbon\Carbon::parse($day)->translatedFormat('l');
        } catch (\Exception $e) {
            return $day;
        }
    }
}
