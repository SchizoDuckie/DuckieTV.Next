<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Serie;

/**
 * CalendarService - Manages calendar data structure for episode scheduling.
 *
 * Ported from DuckieTV Angular CalendarEvents.js (286 lines).
 * Provides storage and retrieval of episodes grouped by date for calendar display.
 *
 * In the Angular version, this was an in-memory service with caching and
 * $rootScope event broadcasting. In Laravel, this is a stateless service
 * that queries the database and returns structured data for the frontend.
 *
 * Key operations:
 * - getEventsForDateRange(): Fetches episodes between two dates and groups by date
 * - getEvents(): Returns episodes for a single calendar date
 * - getSeries(): Groups episodes by serie per date (for calendar grid display)
 * - markDayWatched()/markDayDownloaded(): Batch operations on a day's episodes
 *
 * Calendar event structure (per entry):
 * [
 *     'start'      => Carbon date,
 *     'serie_id'   => int,
 *     'serie'      => Serie model,
 *     'episode'    => Episode model,
 * ]
 *
 * Sorting: by firstaired_iso time, then episode number, then serie name.
 * Ported from CalendarEvents.js calendarEpisodeSort() (lines 52-69).
 *
 * @see \App\Services\FavoritesService
 * @see \App\Models\Episode
 */
class CalendarService
{
    private SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get all calendar events for a date range, grouped by date string.
     * Each date key maps to an array of events sorted by air time, episode number, and serie name.
     *
     * Ported from CalendarEvents.js getEventsForDateRange() (lines 152-165)
     * combined with setEvents() (lines 177-190).
     *
     * @param  \Carbon\Carbon  $start  Start date
     * @param  \Carbon\Carbon  $end  End date
     * @return array<string, array> Hash of dateString => [events], sorted per date
     */
    public function getEventsForDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $showSpecials = (bool) $this->settings->get('calendar.show-specials', true);

        $episodes = Episode::with('serie')
            ->where('firstaired', '>=', $start->getTimestampMs())
            ->where('firstaired', '<=', $end->copy()->endOfDay()->getTimestampMs())
            ->get();

        $events = [];

        foreach ($episodes as $episode) {
            $serie = $episode->serie;
            if (! $serie) {
                continue;
            }

            // Filter specials based on settings (unless serie overrides with ignoreHideSpecials)
            if (! $showSpecials && $episode->seasonnumber == 0 && ! $serie->ignoreHideSpecials) {
                continue;
            }

            $date = \Carbon\Carbon::createFromTimestampMs($episode->firstaired)->toDateString();

            if (! isset($events[$date])) {
                $events[$date] = [];
            }

            $events[$date][] = [
                'start' => \Carbon\Carbon::createFromTimestampMs($episode->firstaired),
                'serie_id' => $episode->serie_id,
                'serie' => $serie,
                'episode' => $episode,
            ];
        }

        // Sort each day's events
        foreach ($events as &$dayEvents) {
            usort($dayEvents, [$this, 'calendarEpisodeSort']);
        }

        return $events;
    }

    /**
     * Get events for a single date.
     * Ported from CalendarEvents.js getEvents() (lines 238-241).
     *
     * @param  \Carbon\Carbon  $date  The date to get events for
     * @return array List of events for this date
     */
    public function getEvents(\Carbon\Carbon $date): array
    {
        $events = $this->getEventsForDateRange(
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay()
        );

        return $events[$date->toDateString()] ?? [];
    }

    /**
     * Get events for a date, grouped by serie. Each serie maps to an array of its episodes.
     * Used by the calendar grid to group episodes under their show headers.
     *
     * Ported from CalendarEvents.js getSeries() (lines 270-281).
     * Returns array sorted by the first episode's air time per serie.
     *
     * @param  \Carbon\Carbon  $date  The date to get series for
     * @return array<array> Array of [event, event, ...] arrays, one per serie, sorted by air time
     */
    public function getSeries(\Carbon\Carbon $date): array
    {
        $events = $this->getEvents($date);
        $bySerieId = [];

        foreach ($events as $event) {
            $serieId = $event['serie_id'];
            if (! isset($bySerieId[$serieId])) {
                $bySerieId[$serieId] = [];
            }
            $bySerieId[$serieId][] = $event;
        }

        $result = array_values($bySerieId);

        // Sort groups by their first event
        usort($result, function (array $a, array $b) {
            return $this->calendarEpisodeSort($a[0], $b[0]);
        });

        return $result;
    }

    /**
     * Mark all aired episodes on a given day as watched.
     * Ported from CalendarEvents.js markDayWatched() (lines 214-224).
     *
     * @param  \Carbon\Carbon  $day  The date whose episodes should be marked
     * @param  bool  $downloadedPaired  When true, also marks episodes as downloaded
     */
    public function markDayWatched(\Carbon\Carbon $day, bool $downloadedPaired = true): void
    {
        $events = $this->getEvents($day);

        foreach ($events as $event) {
            if ($event['episode']->hasAired()) {
                $event['episode']->markWatched($downloadedPaired);
            }
        }
    }

    /**
     * Mark all aired episodes on a given day as downloaded.
     * Ported from CalendarEvents.js markDayDownloaded() (lines 225-234).
     *
     * @param  \Carbon\Carbon  $day  The date whose episodes should be marked
     */
    public function markDayDownloaded(\Carbon\Carbon $day): void
    {
        $events = $this->getEvents($day);

        foreach ($events as $event) {
            if ($event['episode']->hasAired()) {
                $event['episode']->markDownloaded();
            }
        }
    }

    /**
     * Get unwatched episodes in the current calendar month for the todo/watchlist view.
     * Only includes episodes from series that have displaycalendar enabled.
     *
     * Ported from CalendarEvents.js getTodoEvents() (lines 243-264).
     *
     * @return array List of unwatched events from this month
     */
    public function getTodoEvents(): array
    {
        $now = now();
        $firstDay = $now->copy()->startOfMonth();
        $endOfToday = $now->copy()->endOfDay();

        $episodes = Episode::with('serie')
            ->where('firstaired', '>=', $firstDay->getTimestampMs())
            ->where('firstaired', '<', $endOfToday->getTimestampMs())
            ->where('watched', 0)
            ->get();

        $events = [];

        foreach ($episodes as $episode) {
            $serie = $episode->serie;
            if (! $serie || ! $serie->displaycalendar) {
                continue;
            }

            $events[] = [
                'start' => \Carbon\Carbon::createFromTimestampMs($episode->firstaired),
                'serie_id' => $episode->serie_id,
                'serie' => $serie,
                'episode' => $episode,
            ];
        }

        return $events;
    }

    /**
     * Get episode counts per month for a given year.
     * Used by the year overview calendar view to show activity per month.
     *
     * @param  int  $year  The year to get counts for
     * @return array<int, int> Month number (1-12) => episode count
     */
    public function getEpisodeCountsByMonth(int $year): array
    {
        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        $episodes = Episode::where('firstaired', '>=', $start->getTimestampMs())
            ->where('firstaired', '<=', $end->getTimestampMs())
            ->whereHas('serie', fn ($q) => $q->where('displaycalendar', true))
            ->get(['firstaired']);

        $counts = array_fill(1, 12, 0);

        foreach ($episodes as $ep) {
            $month = \Carbon\Carbon::createFromTimestampMs($ep->firstaired)->month;
            $counts[$month]++;
        }

        return $counts;
    }

    /**
     * Sort two calendar events.
     * First by air time (firstaired_iso), then by episode number if same serie,
     * then by serie name if different series at the same time.
     *
     * Ported from CalendarEvents.js calendarEpisodeSort() (lines 52-69).
     *
     * @param  array  $a  First event
     * @param  array  $b  Second event
     * @return int Sort comparison result (-1, 0, 1)
     */
    private function calendarEpisodeSort(array $a, array $b): int
    {
        if (! isset($a['serie']) || ! isset($b['serie'])) {
            return 0;
        }

        $ad = $a['episode']->firstaired_iso
            ? strtotime($a['episode']->firstaired_iso)
            : 0;
        $bd = $b['episode']->firstaired_iso
            ? strtotime($b['episode']->firstaired_iso)
            : 0;

        if ($ad < $bd) {
            return -1;
        }
        if ($ad > $bd) {
            return 1;
        }

        // Same air time: sort by episode number within same serie, or by name across series
        if ($a['serie_id'] === $b['serie_id']) {
            return ($a['episode']->episodenumber ?? 0) <=> ($b['episode']->episodenumber ?? 0);
        }

        return strcasecmp($a['serie']->name ?? '', $b['serie']->name ?? '');
    }
}
