<?php

namespace App\Http\Controllers;

use App\Services\FavoritesService;
use App\Services\PosterService;
use App\Services\TraktService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected TraktService $trakt;

    protected FavoritesService $favorites;

    protected PosterService $posters;

    public function __construct(TraktService $trakt, FavoritesService $favorites, PosterService $posters)
    {
        $this->trakt = $trakt;
        $this->favorites = $favorites;
        $this->posters = $posters;
    }

    /**
     * Display the search page with trending shows.
     * Loads from cache or Trakt API if bundled JSON is missing/outdated.
     */
    public function index(Request $request)
    {
        $trending = $this->posters->getCached('trending');

        if (! $trending) {
            $trending = $this->loadTrending();
            if (empty($trending)) {
                $trending = $this->trakt->trending();
            }
            $trending = $this->posters->enrich($trending);
            $this->posters->cacheResults('trending', $trending);
        }

        $favoriteIds = $this->favorites->getFavoriteIds();

        if ($request->ajax()) {
            return view('search.partial', [
                'results' => $trending,
                'query' => null,
                'favoriteIds' => $favoriteIds,
            ]);
        }

        return view('search.index', [
            'results' => $trending, // Pass trending as results for the initial view
            'query' => null,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    /**
     * Perform a search on Trakt.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        if (empty($query)) {
            return redirect()->route('search.index');
        }

        $results = $this->posters->getCached($query);

        if (! $results) {
            $results = $this->trakt->search($query);
            $results = $this->posters->enrich($results);
            $this->posters->cacheResults($query, $results);
        }

        $favoriteIds = $this->favorites->getFavoriteIds();

        if ($request->ajax()) {
            return view('search.partial', [
                'results' => $results,
                'query' => $query,
                'favoriteIds' => $favoriteIds,
            ]);
        }

        return view('search.index', [
            'results' => $results,
            'query' => $query,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    /**
     * Add a show to favorites.
     */
    public function add(Request $request)
    {
        $traktId = $request->get('trakt_id');

        try {
            $data = $this->trakt->serie($traktId);
            $serie = $this->favorites->addFavorite($data);

            return redirect()->route('calendar.index')->with('status', "Added {$serie->name} to favorites.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add show: '.$e->getMessage());
        }
    }

    /**
     * Load the trending shows from the bundled JSON file.
     * Ported from the original DuckieTV trakt-trending-500.json.
     */
    private function loadTrending(): array
    {
        $path = storage_path('app/trakt-trending-500.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }
}
