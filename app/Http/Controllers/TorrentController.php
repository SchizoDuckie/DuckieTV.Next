<?php

namespace App\Http\Controllers;

use App\Http\Requests\TorrentDetailsRequest;
use App\Http\Requests\TorrentDialogRequest;
use App\Http\Requests\TorrentSearchRequest;
use App\Services\SettingsService;
use App\Services\TorrentSearchService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles torrent search, details fetching, and engine listing.
 *
 * Provides both the server-rendered search dialog view (loaded into the
 * sidepanel right panel) and JSON API endpoints consumed by TorrentSearch.js
 * for interactive searching.
 *
 * Flow:
 * 1. User clicks "FIND TORRENT" on an episode → searchDialog() renders the form
 * 2. User types a query → TorrentSearch.js calls search() via AJAX
 * 3. If an engine lacks inline magnet links → TorrentSearch.js calls details()
 * 4. User clicks a magnet link → browser/client handles the magnet: protocol
 *
 * @see TorrentSearchEngines.js in DuckieTV-angular for original registry.
 * @see torrentDialogCtrl.js in DuckieTV-angular for original dialog controller.
 */
class TorrentController extends Controller
{
    /**
     * @param  TorrentSearchService  $searchService  Registry of all torrent search engines
     * @param  SettingsService  $settings  User settings (search quality, default engine, etc.)
     */
    public const DEFAULT_ENGINE = 'ThePirateBay';

    /**
     * @param  TorrentSearchService  $searchService  Registry of all torrent search engines
     * @param  SettingsService  $settings  User settings (search quality, default engine, etc.)
     */
    public function __construct(
        protected TorrentSearchService $searchService,
        protected SettingsService $settings,
        protected \App\Services\TorrentClientService $clientService,
    ) {}

    /**
     * Render the torrent search dialog view.
     *
     * Loaded into the sidepanel right panel via data-sidepanel-expand when the
     * user clicks "FIND TORRENT" on an episode detail view. The search query is
     * pre-populated with the show name, episode code, and quality setting.
     *
     * @param  TorrentDialogRequest  $request  Contains 'query' (pre-filled search) and 'episode_id'
     * @return View The torrents.search blade template
     */
    public function searchDialog(TorrentDialogRequest $request): View
    {
        $query = $request->validated('query', '');
        $episodeId = $request->validated('episode_id');
        $quality = $this->settings->get('torrenting.searchquality', '');

        $allEngines = array_keys($this->searchService->getSearchEngines());
        $enabledEngines = $this->settings->get('torrenting.search_enabled_engines', $allEngines);
        $defaultEngine = $this->settings->get('torrenting.searchprovider', self::DEFAULT_ENGINE);

        return view('torrents.search', [
            'query' => $query,
            'quality' => $quality,
            'episodeId' => $episodeId,
            'engines' => $enabledEngines,
            'allEngines' => $allEngines,
            'defaultEngine' => $defaultEngine,
            'qualityList' => $this->settings->get('torrenting.searchqualitylist', []),
        ]);
    }

    /**
     * Search for torrents via AJAX.
     *
     * Dispatches the search query to the specified engine (or the default)
     * via TorrentSearchService. Results are returned as JSON for client-side
     * rendering by TorrentSearch.js.
     *
     * Results structure per item:
     * - releasename: string — torrent release name
     * - size: string — human-readable size (e.g., "1.23 GB")
     * - seeders: int — number of seeders
     * - leechers: int — number of leechers
     * - magnetUrl: string|null — magnet link (if available from search page)
     * - torrentUrl: string|null — .torrent file URL
     * - detailUrl: string — link to the torrent detail page
     * - noMagnet: bool — true if magnet must be fetched from detail page
     * - noTorrent: bool — true if .torrent URL must be fetched from detail page
     *
     * @param  TorrentSearchRequest  $request  Validated search parameters
     * @return JsonResponse Search results or error
     */
    public function search(TorrentSearchRequest $request): JsonResponse
    {
        $query = $request->validated('query');
        $engine = $request->validated('engine');
        $sortBy = $request->validated('sortBy') ?? 'seeders.d';

        try {
            $results = $this->searchService->search($query, $engine, $sortBy);

            $html = view('torrents.results', ['results' => $results])->render();

            return response()->json([
                'results' => $results, // Keep for now in case other things use it, or for debugging
                'html' => $html,
                'engine' => $engine ?? $this->settings->get('torrenting.searchprovider', self::DEFAULT_ENGINE),
                'query' => $query,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'results' => [],
                'engine' => $engine,
                'query' => $query,
            ], 422);
        }
    }

    /**
     * Fetch magnet/torrent URL from a torrent's detail page.
     *
     * Some search engines (e.g., 1337x) don't include magnet links directly
     * in the search results HTML. For these engines, a second HTTP request to
     * the detail page is required. This endpoint performs that fetch server-side
     * and extracts the magnet/torrent URLs using the engine's detailsSelectors.
     *
     * @param  TorrentDetailsRequest  $request  Validated detail page parameters
     * @return JsonResponse Contains 'magnetUrl' and/or 'torrentUrl'
     */
    public function details(TorrentDetailsRequest $request): JsonResponse
    {
        try {
            $engine = $this->searchService->getSearchEngine($request->validated('engine'));
            $details = $engine->getDetails(
                $request->validated('url'),
                $request->validated('releasename'),
            );

            return response()->json($details);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Return the list of available search engines.
     *
     * Used by TorrentSearch.js to populate the engine selector dropdown.
     * Each engine entry includes its name and whether it's the user's
     * configured default.
     *
     * @return JsonResponse Array of {name: string, isDefault: bool}
     */
    public function engines(): JsonResponse
    {
        $engines = $this->searchService->getSearchEngines();
        $defaultEngine = $this->settings->get('torrenting.searchprovider', self::DEFAULT_ENGINE);

        $list = [];
        foreach ($engines as $name => $engine) {
            $list[] = [
                'name' => $name,
                'isDefault' => $name === $defaultEngine,
            ];
        }

        return response()->json($list);
    }

    /**
     * Add a torrent (magnet or URL) to the active client.
     */
    public function add(\App\Http\Requests\AddTorrentRequest $request): JsonResponse
    {
        try {
            $client = $this->clientService->getActiveClient();
            if (! $client) {
                return response()->json(['error' => 'No torrent client configured'], 422);
            }

            if (! $client->connect()) {
                return response()->json(['error' => 'Could not connect to torrent client'], 422);
            }

            $dlPath = $request->validated('dlPath');
            $label = $request->validated('label') ?? 'DuckieTV';
            $episodeId = $request->validated('episode_id');

            // Extract infoHash from magnet if not provided
            $infoHash = $request->validated('infoHash');
            if (! $infoHash && $request->has('magnet')) {
                $infoHash = \App\Support\MagnetUri::extractInfoHash($request->validated('magnet'));
            }

            // Link to episode if provided
            if ($episodeId) {
                /** @var \App\Models\Episode|null $episode */
                $episode = \App\Models\Episode::find($episodeId);
                if ($episode) {
                    // Update magnetHash if we found one (either passed or extracted)
                    if ($infoHash) {
                        $episode->update(['magnetHash' => $infoHash]);
                    }
                    $episode->markDownloaded();
                    // Optional: You might want to dispatch an event here if needed
                }
            }

            if ($request->has('magnet')) {
                $success = $client->addMagnet($request->validated('magnet'), $dlPath, $label);
            } elseif ($request->has('url')) {
                $success = $client->addTorrentByUrl(
                    $request->validated('url'),
                    $infoHash, // Use the resolved infoHash
                    $request->validated('releaseName'),
                    $dlPath,
                    $label
                );
            } else {
                return response()->json(['error' => 'No magnet or URL provided'], 422);
            }

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Torrent added successfully',
                    'infoHash' => $infoHash, // Return the hash so specific UI logic can use it if needed
                ]);
            }

            return response()->json(['error' => 'Failed to add torrent to client'], 422);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * Attempt to connect to the configured torrent client.
     */
    public function connect(Request $request): JsonResponse
    {
        $config = $request->all();
        $client = $config['torrenting.client'] ?? 'uTorrent';

        \App\Events\TorrentConnectionStatus::dispatch('connecting', $client, 'Connecting to '.$client.'...');
        \App\Jobs\AttemptTorrentConnection::dispatch($client, $config);

        return response()->json(['success' => true, 'message' => 'Connection attempt started...']);
    }

    /**
     * Get the current status of the torrent client.
     */
    public function status(): JsonResponse
    {
        try {
            /** @var \App\Services\TorrentClients\TorrentClientInterface|null $client */
            $client = app(\App\Services\TorrentClientService::class)->getActiveClient();

            if (! $client) {
                return response()->json([
                    'connected' => false,
                    'client' => 'None',
                    'active_count' => 0,
                    'error' => 'No client configured',
                ]);
            }

            // Attempt to connect. Most clients will return true/false or throw an Exception.
            $connected = false;
            $error = null;
            $activeCount = 0;

            try {
                $connected = $client->connect();
                if ($connected) {
                    $torrentList = $client->getTorrents();
                    $activeCount = count($torrentList);
                } else {
                    $error = 'Could not connect to '.$client->getName().'. Check your settings and ensure the client is running.';
                }
            } catch (Exception $e) {
                $connected = false;
                $error = 'Connection failed: '.$e->getMessage();
            }

            return response()->json([
                'connected' => $connected,
                'client' => $client->getName(),
                'active_count' => $activeCount,
                'torrents' => $torrentList ?? [],
                'error' => $error,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'connected' => false,
                'error' => $e->getMessage(),
                'client' => 'Unknown',
            ]);
        }
    }

    /**
     * Render the torrent client list view.
     */
    public function index(): View
    {
        /** @var \App\Services\TorrentClientService $clientService */
        $clientService = app(\App\Services\TorrentClientService::class);
        $client = $clientService->getActiveClient();

        $torrents = [];
        if ($client) {
            try {
                if ($client->connect()) {
                    $torrents = $client->getTorrents();
                }
            } catch (Exception $e) {
                // Silently fail, view will handle empty torrents
            }
        }

        return view('torrents.index', [
            'client' => $client,
            'torrents' => $torrents,
        ]);
    }

    /**
     * Render the torrent detail view.
     */
    public function show(string $infoHash): View
    {
        /** @var \App\Services\TorrentClientService $clientService */
        $clientService = app(\App\Services\TorrentClientService::class);
        $client = $clientService->getActiveClient();

        $torrent = null;
        if ($client) {
            try {
                if ($client->connect()) {
                    $torrents = $client->getTorrents();
                    // Search for the torrent with the matching infoHash
                    foreach ($torrents as $t) {
                        // Assuming the client returns objects with getInfoHash() or similar
                        // Let's check the TorrentClientInterface or a specific client to be sure
                        if (method_exists($t, 'getInfoHash') && $t->getInfoHash() === $infoHash) {
                            $torrent = $t;
                            break;
                        }
                        // Some clients might store it in a public property or another method
                        if (isset($t->infoHash) && $t->infoHash === $infoHash) {
                            $torrent = $t;
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                // Silently fail
            }
        }

        return view('torrents.show', [
            'client' => $client,
            'torrent' => $torrent,
        ]);
    }

    /**
     * Start a torrent by its infoHash.
     */
    public function start(string $infoHash): JsonResponse
    {
        try {
            $client = $this->clientService->getActiveClient();
            if (! $client) {
                return response()->json(['error' => 'No client configured'], 422);
            }
            if (! $client->connect()) {
                return response()->json(['error' => 'Could not connect'], 422);
            }

            $success = $client->startTorrent($infoHash);

            return response()->json(['success' => $success]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Stop a torrent by its infoHash.
     */
    public function stop(string $infoHash): JsonResponse
    {
        try {
            $client = $this->clientService->getActiveClient();
            if (! $client) {
                return response()->json(['error' => 'No client configured'], 422);
            }
            if (! $client->connect()) {
                return response()->json(['error' => 'Could not connect'], 422);
            }

            $success = $client->stopTorrent($infoHash);

            return response()->json(['success' => $success]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Pause a torrent by its infoHash.
     */
    public function pause(string $infoHash): JsonResponse
    {
        try {
            $client = $this->clientService->getActiveClient();
            if (! $client) {
                return response()->json(['error' => 'No client configured'], 422);
            }
            if (! $client->connect()) {
                return response()->json(['error' => 'Could not connect'], 422);
            }

            $success = $client->pauseTorrent($infoHash);

            return response()->json(['success' => $success]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a torrent by its infoHash.
     */
    public function remove(string $infoHash): JsonResponse
    {
        try {
            $client = $this->clientService->getActiveClient();
            if (! $client) {
                return response()->json(['error' => 'No client configured'], 422);
            }
            if (! $client->connect()) {
                return response()->json(['error' => 'Could not connect'], 422);
            }

            $success = $client->removeTorrent($infoHash);

            return response()->json(['success' => $success]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
