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
                    
                    // Priority 1: Match by InfoHash (if episode has one)
                    if ($episode->magnetHash) {
                        foreach ($torrents as $torrent) {
                            if ((method_exists($torrent, 'getInfoHash') && $torrent->getInfoHash() === $episode->magnetHash) || (isset($torrent->infoHash) && $torrent->infoHash === $episode->magnetHash)) {
                                $matchedTorrent = $torrent;
                                break;
                            }
                        }
                    }

                    // Priority 2: Fallback to name matching if no infoHash match
                    if (!$matchedTorrent) {
                        $showName = strtolower($serie->name);
                        $showNameDots = str_replace(' ', '.', $showName);
                        $episodeCode = strtolower($episode->formatted_episode);

                        foreach ($torrents as $torrent) {
                            $name = strtolower($torrent->getName());
                            if ((str_contains($name, $showName) || str_contains($name, $showNameDots)) && str_contains($name, $episodeCode)) {
                                $matchedTorrent = $torrent;
                                break;
                            }
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
            'searchQuery' => app(\App\Services\SceneNameResolverService::class)->getSearchStringForEpisode($serie, $episode),
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => "Updated {$episode->formatted_episode}."]);
        }

        return redirect()->back()->with('status', "Updated {$episode->formatted_episode}.");
    }

    /**
     * Trigger automated download for an episode.
     */
    public function autoDownload(int $id)
    {
        $episode = Episode::with('serie')->findOrFail($id);
        $service = app(\App\Services\AutoDownloadService::class);
        
        $success = $service->manualDownload($episode);

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Torrent launched successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'No suitable torrent found or already downloaded. Check Activity Log.'], 422);
    }
}
