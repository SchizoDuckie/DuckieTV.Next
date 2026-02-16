<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    /**
     * Show details for a specific episode.
     * Ported from episode-details.html logic.
     */
    public function show(int $id)
    {
        $episode = Episode::with(['serie', 'season'])->findOrFail($id);
        $serie = $episode->serie;

        /** @var \App\Services\TorrentClientService $clientService */
        $clientService = app(\App\Services\TorrentClientService::class);
        $client = $clientService->getActiveClient();
        $matchedTorrent = null;

        if ($client && settings('torrenting.enabled')) {
            try {
                if ($client->connect()) {
                    $torrents = $client->getTorrents();
                    $showName = strtolower($serie->name);
                    $episodeCode = strtolower($episode->formatted_episode);
                    
                    foreach ($torrents as $torrent) {
                        $name = strtolower($torrent->getName());
                        if (str_contains($name, $showName) && str_contains($name, $episodeCode)) {
                            $matchedTorrent = $torrent;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently fail, view will handle null torrent
            }
        }

        return view('episodes.show', [
            'episode' => $episode,
            'serie' => $serie,
            'torrent' => $matchedTorrent,
        ]);
    }

    /**
     * Update episode state (toggle watched/downloaded).
     */
    public function update(Request $request, int $id)
    {
        $episode = Episode::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'toggle_watched') {
            $episode->watched ? $episode->markNotWatched() : $episode->markWatched();
        } elseif ($action === 'toggle_download') {
            $episode->downloaded ? $episode->markNotDownloaded() : $episode->markDownloaded();
        } elseif ($action === 'toggle_leaked') {
            $episode->isLeaked() ? $episode->markNotLeaked() : $episode->markLeaked();
        }

        return redirect()->back()->with('status', "Updated {$episode->formatted_episode}.");
    }
}
