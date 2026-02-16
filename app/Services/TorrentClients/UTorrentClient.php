<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\UTorrentData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * uTorrent / BitTorrent client implementation.
 *
 * This implementation follows the 'btapp' API pattern used in DuckieTV-angular,
 * which involves a pairing step to get an auth token, then session-based
 * RPC calls to the /btapp/ endpoint.
 *
 * @see uTorrent.js in DuckieTV-angular for original implementation.
 */
class UTorrentClient extends BaseTorrentClient
{
    /** @var string|null The pairing auth token */
    protected ?string $authToken = null;

    /** @var string|null The active session key */
    protected ?string $sessionKey = null;

    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'uTorrent';
        $this->id = 'utorrent';
        $this->authToken = $settings->get('utorrent.token');
    }

    public function getValidationRules(): array
    {
        return [
            'utorrent.token' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for uTorrent.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'utorrent.server', // usually localhost
            'port' => 'utorrent.port',   // dynamic, usually 10000+
        ];
    }

    /**
     * Connect and establish a session.
     *
     * If no auth token is present, pairing would normally be required.
     * In this server-side context, we assume the token is already configured
     * or will be retrieved via a separate setup process.
     */
    public function connect(): bool
    {
        if (! $this->authToken) {
            throw new Exception('uTorrent authentication token is missing. Please clear and re-connect.');
        }

        /** @var array $response */
        $response = $this->rpc('state', [
            'pairing' => $this->authToken,
            'queries' => '[["btapp"]]',
            'hostname' => 'localhost',
        ]);

        if (isset($response['session'])) {
            $this->sessionKey = $response['session'];

            return true;
        }

        return false;
    }

    /**
     * Get list of torrents from uTorrent.
     *
     * uTorrent's 'update' query returns a delta of the state tree.
     */
    public function getTorrents(): array
    {
        if (! $this->sessionKey) {
            $this->connect();
        }

        try {
            // The 'list' RPC call returns torrents in a specific format:
            // { "torrents": [ [hash, status, name, size, downloaded, uploaded, ratio, ...], ... ] }
            /** @var array $response */
            $response = $this->rpc('list', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'hostname' => 'localhost',
            ]);

            if (! isset($response['torrents']) || ! is_array($response['torrents'])) {
                return [];
            }

            return collect($response['torrents'])->map(fn ($torrent) => new UTorrentData([
                'infoHash' => strtoupper($torrent[0]),
                'name' => $torrent[2],
                'progress' => (float) ($torrent[4] / 10), // permille to percentage
                'status' => (string) ($torrent[21] ?? 'Unknown'),
            ]))->all();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool
    {
        if (! $this->sessionKey) {
            $this->connect();
        }
        try {
            $this->rpc('function', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'path' => '[["btapp","torrent","'.$infoHash.'","start"]]',
                'hostname' => 'localhost',
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Stop a torrent by its infohash.
     */
    public function stopTorrent(string $infoHash): bool
    {
        return $this->pauseTorrent($infoHash);
    }

    /**
     * Pause a torrent by its infohash.
     */
    public function pauseTorrent(string $infoHash): bool
    {
        if (! $this->sessionKey) {
            $this->connect();
        }
        try {
            $this->rpc('function', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'path' => '[["btapp","torrent","'.$infoHash.'","pause"]]',
                'hostname' => 'localhost',
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove a torrent by its infohash.
     */
    public function removeTorrent(string $infoHash): bool
    {
        if (! $this->sessionKey) {
            $this->connect();
        }
        try {
            $this->rpc('function', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'path' => '[["btapp","torrent","'.$infoHash.'","remove"]]',
                'hostname' => 'localhost',
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of individual files for a torrent.
     */
    public function getTorrentFiles(string $infoHash): array
    {
        if (! $this->sessionKey) {
            $this->connect();
        }
        try {
            // uTorrent Remote API for files is deeply nested
            /** @var array $response */
            $response = $this->rpc('function', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'path' => '[["btapp","torrent","'.$infoHash.'","file"]]',
                'hostname' => 'localhost',
            ]);

            return $response['result'] ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool
    {
        try {
            $torrents = $this->getTorrents();
            $torrent = collect($torrents)->first(fn ($t) => strtoupper($t->infoHash) === strtoupper($infoHash));

            return $torrent && in_array(strtolower($torrent->status), ['downloading', 'seeding', 'started'], true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link to uTorrent.
     *
     * @param  string  $magnet  Magnet link
     * @param  string|null  $downloadPath  Optional download path (not supported via this API)
     * @param  string|null  $label  Optional label (not supported via this API)
     */
    public function addMagnet(string $magnet, ?string $downloadPath = null, ?string $label = null): bool
    {
        if (! $this->sessionKey) {
            $this->connect();
        }

        try {
            $this->rpc('function', [
                'pairing' => $this->authToken,
                'session' => $this->sessionKey,
                'path' => '[["btapp","add","torrent"]]',
                'args' => json_encode([$magnet]),
                'hostname' => 'localhost',
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a torrent by its URL.
     *
     * @param  string  $url  The .torrent file URL
     * @param  string  $infoHash  The infohash of the torrent for verification.
     * @param  string  $releaseName  The readable name of the release.
     * @param  string|null  $dlPath  Optional custom download path.
     * @param  string|null  $label  Optional label or category.
     */
    public function addTorrentByUrl(string $url, ?string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        return $this->addMagnet($url, $dlPath, $label);
    }

    /**
     * Add a torrent by uploading its raw data. (Not supported by uTorrent Remote API)
     *
     * @param  string  $data  The raw binary content of the .torrent file.
     * @param  string  $infoHash  The infohash of the torrent for verification.
     * @param  string  $releaseName  The readable name of the release.
     * @param  string|null  $dlPath  Optional custom download path for the torrent.
     * @param  string|null  $label  Optional label or category to assign to the torrent.
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        return false; // Not implemented in original uTorrent remote.
    }

    /**
     * Execute an RPC request to uTorrent.
     *
     * @param  string  $type  Request type (state, update, function)
     * @param  array  $params  Query parameters
     *
     * @throws Exception
     */
    protected function rpc(string $type, array $params): array
    {
        $baseUrl = rtrim($this->config['server'], '/').':'.$this->config['port'].'/btapp/';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::get($baseUrl, array_merge(['type' => $type], $params));

        if (! $response->successful()) {
            throw new Exception('uTorrent API error: '.$response->status());
        }

        return $response->json();
    }
}
