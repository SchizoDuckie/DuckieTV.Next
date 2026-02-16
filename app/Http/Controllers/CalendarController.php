<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected CalendarService $calendar;

    public function __construct(CalendarService $calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * Display the calendar in the requested view mode.
     * Supports year, month (default), and week views with hierarchical navigation:
     *   Year → Month → Week
     *
     * Matches the original DuckieTV datePicker.js view hierarchy.
     */
    public function index(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->get('date')) : now();
        $mode = $request->get('mode', 'month');

        return match ($mode) {
            'decade' => $this->decadeView($date),
            'year' => $this->yearView($date),
            'week' => $this->weekView($date),
            default => $this->monthView($date),
        };
    }

    /**
     * Decade overview: 12 years (start - 1 to start + 10).
     * Clicking a year drills down to year view.
     */
    private function decadeView(Carbon $date)
    {
        $year = $date->year;
        // Calculate start of decade (e.g. 2020 for 2026)
        $startYear = floor($year / 10) * 10 - 1; 
        $endYear = $startYear + 11;

        $years = $this->calendar->getEpisodeCountsByYear($startYear, $endYear);

        return view('calendar.index', [
            'mode' => 'decade',
            'years' => $years,
            'currentDate' => $date, // Keep the specific date for context
            'startYear' => $startYear,
            'endYear' => $endYear,
            'title' => $startYear . '-' . $endYear,
        ]);
    }

    /**
     * Year overview: 12 month cells with episode counts.
     * Clicking a month drills down to month view.
     * Prev/next navigates between years.
     * Clicking title drills up to decade view.
     */
    private function yearView(Carbon $date)
    {
        $months = $this->calendar->getEpisodeCountsByMonth($date->year);

        return view('calendar.index', [
            'mode' => 'year',
            'months' => $months,
            'currentDate' => $date,
            'title' => (string) $date->year,
        ]);
    }

    /**
     * Month grid: 6 weeks of days with episode cards.
     * Clicking the title drills up to year view.
     * Clicking a day drills down to week view for that week.
     * Prev/next navigates between months.
     */
    private function monthView(Carbon $date)
    {
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $events = $this->calendar->getEventsForDateRange($start, $end);

        return view('calendar.index', [
            'mode' => 'month',
            'events' => $events,
            'currentDate' => $date,
            'title' => $date->format('F Y'),
            'monthName' => $date->format('F Y'),
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Week strip: 7 days with full episode detail.
     * Clicking the title drills up to month view.
     * Prev/next navigates between weeks.
     */
    private function weekView(Carbon $date)
    {
        $start = $date->copy()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $events = $this->calendar->getEventsForDateRange($start, $end);

        return view('calendar.index', [
            'mode' => 'week',
            'events' => $events,
            'currentDate' => $date,
            'title' => $start->format('M d').' – '.$end->format('M d, Y'),
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Mark a whole day as watched.
     */
    public function markDayWatched(Request $request)
    {
        $date = Carbon::parse($request->get('date'));
        $this->calendar->markDayWatched($date);

        return back()->with('status', "Marked all episodes on {$date->toDateString()} as watched.");
    }

    /**
     * Mark a whole day as downloaded.
     */
    public function markDayDownloaded(Request $request)
    {
        $date = Carbon::parse($request->get('date'));
        $this->calendar->markDayDownloaded($date);

        return back()->with('status', "Marked all episodes on {$date->toDateString()} as downloaded.");
    }

    /**
     * Toggle between calendar and todo view modes.
     */
    public function toggleViewMode(Request $request)
    {
        $currentMode = session('viewmode', 'calendar');
        $newMode = ($currentMode === 'calendar') ? 'todo' : 'calendar';
        session(['viewmode' => $newMode]);

        return back()->with('status', "Switched to " . ucfirst($newMode) . " view.");
    }
}
