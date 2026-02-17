<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Services\SubtitlesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SubtitlesController extends Controller
{
    protected SubtitlesService $subtitlesService;

    public function __construct(SubtitlesService $subtitlesService)
    {
        $this->subtitlesService = $subtitlesService;
    }

    /**
     * Return the subtitles search overlay shell.
     */
    public function index()
    {
        return view('subtitles.index');
    }

    /**
     * Search for subtitles for a given episode.
     */
    public function search(Request $request)
    {
        $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'languages' => 'sometimes|array',
        ]);

        $episode = Episode::with('serie')->findOrFail($request->input('episode_id'));
        $languages = $request->input('languages', settings('subtitles.languages', ['eng']));

        $results = $this->subtitlesService->searchByEpisode($episode, $languages);

        // Sort by language name as per original Angular logic
        usort($results, fn($a, $b) => strcmp($a['attributes']['language_name'] ?? '', $b['attributes']['language_name'] ?? ''));

        if ($request->ajax()) {
            return view('subtitles._rows', [
                'results' => $results
            ])->render();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search for subtitles using a text query.
     */
    public function searchByQuery(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'languages' => 'sometimes|array',
        ]);

        $query = $request->input('query');
        $languages = $request->input('languages', settings('subtitles.languages', ['eng']));

        $results = $this->subtitlesService->searchByQuery($query, $languages);

        // Sort by language name as per original Angular logic
        usort($results, fn($a, $b) => strcmp($a['attributes']['language_name'] ?? '', $b['attributes']['language_name'] ?? ''));

        if ($request->ajax()) {
            return view('subtitles._rows', [
                'results' => $results
            ])->render();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
