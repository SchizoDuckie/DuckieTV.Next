<?php

namespace App\Http\Controllers;

use App\Services\FavoritesService;
use App\Services\SceneNameResolverService;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    protected FavoritesService $favorites;
    protected SceneNameResolverService $sceneNameResolver;

    public function __construct(FavoritesService $favorites, SceneNameResolverService $sceneNameResolver)
    {
        $this->favorites = $favorites;
        $this->sceneNameResolver = $sceneNameResolver;
    }

    /**
     * List all favorite series.
     * Returns a dataset that can be rendered on both web and TUI.
     */
    public function index(Request $request)
    {
        $series = $this->favorites->getFilteredSeries($request->all());
        $genres = $this->favorites->getUniqueGenres();
        $statuses = $this->favorites->getUniqueStatuses();

        if ($request->ajax()) {
            return view('series.partial', compact('series', 'genres', 'statuses'));
        }

        return view('series.index', compact('series', 'genres', 'statuses'));
    }

    /**
     * Show details for a specific serie.
     * Includes seasons and episodes.
     */
    public function show(Request $request, int $id)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        // Ensure seasons and episodes are loaded for TUI/Web
        $serie->load(['seasons.episodes']);

        if ($request->ajax()) {
            return view('series._details', compact('serie'));
        }

        return view('series.show', compact('serie'));
    }

    /**
     * Show full details for a specific serie.
     * Ported from serie-details.html logic.
     */
    public function details(int $id)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        return view('series._details_full', compact('serie'));
    }

    /**
     * Show the seasons grid for a specific serie.
     * Ported from seasons.html logic — shows clickable season posters.
     * Displayed in the sidepanel right panel via data-sidepanel-expand.
     *
     * @see templates/sidepanel/seasons.html in DuckieTV-angular
     */
    public function seasons(int $id)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        $serie->load(['seasons' => fn($q) => $q->orderBy('seasonnumber')]);

        return view('series._seasons', compact('serie'));
    }

    /**
     * Show episodes list for a specific serie, optionally filtered to a single season.
     * Ported from episodes.html logic — shows one season at a time with navigation.
     *
     * When season_id is provided (e.g., from seasons grid click), shows that season.
     * Otherwise, shows the first season with unwatched episodes (or the last season).
     *
     * @see templates/sidepanel/episodes.html in DuckieTV-angular
     */
    public function episodes(int $id, ?int $season_id = null)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        $serie->load(['seasons.episodes']);

        // Determine which season to display
        $seasons = $serie->seasons->sortBy('seasonnumber');
        if ($season_id) {
            $activeSeason = $seasons->firstWhere('id', $season_id);
        }
        if (!isset($activeSeason) || !$activeSeason) {
            // Default: first season with unwatched episodes, or last season
            $activeSeason = $seasons->first(fn($s) => $s->episodes->where('watched', false)->isNotEmpty())
                ?? $seasons->last();
        }

        // Pre-calculate search queries for episodes
        foreach ($activeSeason->episodes as $episode) {
            $episode->search_query = $this->sceneNameResolver->getSearchStringForEpisode($serie, $episode);
        }

        // Calculate search query for the whole season
        $seasonSearchQuery = ($serie->customSearchString ?: $serie->name) . ' season ' . $activeSeason->seasonnumber;

        // Calculate ratings data for the chart
        $ratingPoints = $activeSeason->episodes->sortBy('episodenumber')->map(function ($episode) {
            return [
            'y' => $episode->rating ?? 0,
            'label' => $episode->formatted_episode . ' : ' . ($episode->rating ?? 0) . '% (' . ($episode->ratingcount ?? 0) . ' ' . __('votes') . ')',
            ];
        })->values();

        return view('series._episodes', [
            'serie' => $serie,
            'seasons' => $seasons,
            'activeSeason' => $activeSeason,
            'seasonSearchQuery' => $seasonSearchQuery,
            'ratingPoints' => $ratingPoints,
        ]);
    }

    /**
     * Update series state (mark watched, toggle auto-download, toggle calendar).
     *
     * Handles multiple actions via the 'action' input parameter:
     * - mark_watched: Marks all aired episodes as watched
     * - toggle_autodownload: Toggles the autoDownload flag
     * - toggle_calendar: Toggles the displaycalendar flag (show/hide from calendar)
     *
     * @see serieSidepanelCtrl in DuckieTV-angular for original action handlers
     */
    public function update(Request $request, int $id)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        $action = $request->input('action');

        if ($action === 'mark_watched') {
            $serie->markSerieAsWatched();
        }
        elseif ($action === 'mark_downloaded') {
            $serie->markSerieAsDownloaded();
        }
        elseif ($action === 'toggle_autodownload') {
            $serie->toggleAutoDownload();
        }
        elseif ($action === 'toggle_calendar') {
            $serie->toggleCalendarDisplay();
        }
        elseif ($action === 'mark_season_watched') {
            $seasonId = $request->input('season_id');
            $season = $serie->seasons()->find($seasonId);
            if ($season) {
                foreach ($season->episodes as $episode) {
                    if ($episode->hasAired()) {
                        $episode->markWatched();
                    }
                }
            }
        }
        elseif ($action === 'mark_season_downloaded') {
            $seasonId = $request->input('season_id');
            $season = $serie->seasons()->find($seasonId);
            if ($season) {
                foreach ($season->episodes as $episode) {
                    if ($episode->hasAired()) {
                        $episode->markDownloaded();
                    }
                }
            }
        }

        if ($request->ajax()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->back()->with('status', "Updated {$serie->name}.");
    }

    /**
     * Refresh series details from external source (Stub).
     */
    public function refresh(int $id)
    {
        $serie = $this->favorites->getById($id);

        if (!$serie) {
            return abort(404, 'Show not found');
        }

        // TODO: Implement actual refresh logic via TMDB/TVDB/Trakt services
        // For now, just touch the updated_at timestamp
        $serie->touch();

        return redirect()->back()->with('status', "Refreshed {$serie->name}.");
    }

    /**
     * Remove a serie from favorites.
     */
    public function remove(int $id)
    {
        $serie = $this->favorites->getById($id);

        if ($serie) {
            $this->favorites->remove($serie);

            return redirect()->route('series.index')->with('status', "Removed {$serie->name} from favorites.");
        }

        return redirect()->route('series.index')->with('error', 'Show not found.');
    }
}
