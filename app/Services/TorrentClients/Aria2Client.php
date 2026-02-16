<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\Aria2Data;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Aria2 Client Implementation.
 *
 * Handles communication with Aria2 via its JSON-RPC interface.
 *
 * @see Aria2.js in DuckieTV-angular.
 * @see https://aria2.github.io/manual/en/html/aria2c.html#rpc-interface
 */
class Aria2Client extends BaseTorrentClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'Aria2';
        $this->id = 'aria2';
    }

    public function getValidationRules(): array
    {
        return [
            'aria2.server' => 'nullable|url',
            'aria2.port' => 'nullable|integer',
            'aria2.token' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for Aria2.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'aria2.server',
            'port' => 'aria2.port',
            'token' => 'aria2.token',
        ];
    }

    /**
     * Test connection to Aria2 by getting version info.
     */
    public function connect(): bool
    {
        $result = $this->rpc('getVersion');

        return isset($result['version']);
    }

    /**
     * Get list of torrents from Aria2.
     *
     * Aria2 requires multiple calls to get all active, waiting, and stopped tasks.
     */
    public function getTorrents(): array
    {
        try {
            // multicall across different statuses
            $paramArray = [
                ['methodName' => 'aria2.tellActive', 'params' => ['token:'.$this->config['token']]],
                ['methodName' => 'aria2.tellWaiting', 'params' => ['token:'.$this->config['token'], 0, 9999]],
                ['methodName' => 'aria2.tellStopped', 'params' => ['token:'.$this->config['token'], 0, 9999]],
            ];

            $response = Http::post($this->getRpcUrl(), [
                'jsonrpc' => '2.0',
                'method' => 'system.multicall',
                'id' => 'DuckieTV',
                'params' => [$paramArray],
            ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $torrents = [];

            if (! isset($data['result'])) {
                return [];
            }

            // Flatten the results from multicall and filter for bittorrent tasks
            return collect($data['result'])
                ->flatten(1) // Flatten one level to get all tasks in a single collection
                ->filter(fn ($task) => isset($task['bittorrent'])) // Keep only BitTorrent tasks
                ->map(fn ($task) => new Aria2Data([
                    'infoHash' => strtoupper($task['infoHash'] ?? $task['gid']),
                    'name' => $task['bittorrent']['info']['name'] ?? ($task['files'][0]['path'] ?? 'Unknown'),
                    'progress' => $task['totalLength'] > 0 ? (float) (($task['completedLength'] / $task['totalLength']) * 100) : 0,
                    'status' => $task['status'],
                ]))->all();

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Add a magnet link to Aria2.
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        $params = [[$magnet]];
        if ($dlPath) {
            $params[] = ['dir' => $dlPath];
        }

        try {
            $result = $this->rpc('addUri', $params);

            return ! empty($result);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a torrent by its URL.
     */
    public function addTorrentByUrl(string $url, ?string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        return $this->addMagnet($url, $dlPath, $label);
    }

    /**
     * Add a torrent by uploading its raw binary data.
     *
     * @param  string  $data  Raw .torrent file content
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        $params = [base64_encode($data)];
        if ($dlPath) {
            $params[] = []; // empty array for uris
            $params[] = ['dir' => $dlPath];
        }

        try {
            $result = $this->rpc('addTorrent', $params);

            return ! empty($result);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute a JSON-RPC method.
     *
     * @throws Exception
     */
    protected function rpc(string $method, array $params = []): mixed
    {
        // Add auth token if set
        array_unshift($params, 'token:'.($this->config['token'] ?? ''));

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::post($this->getRpcUrl(), [
            'jsonrpc' => '2.0',
            'method' => 'aria2.'.$method,
            'id' => 'DuckieTV',
            'params' => $params,
        ]);

        if (! $response->successful()) {
            throw new Exception('Aria2 RPC error: '.$response->status());
        }

        $data = $response->json();
        if (isset($data['error'])) {
            throw new Exception('Aria2 RPC Error: '.($data['error']['message'] ?? 'Unknown error'));
        }

        return $data['result'] ?? null;
    }

    /**
     * Start a torrent by its infohash (gid in Aria2).
     */
    public function startTorrent(string $infoHash): bool
    {
        try {
            $this->rpc('unpause', [$infoHash]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Stop a torrent by its infohash (gid in Aria2).
     */
    public function stopTorrent(string $infoHash): bool
    {
        try {
            $this->rpc('pause', [$infoHash]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pause a torrent by its infohash (gid in Aria2).
     */
    public function pauseTorrent(string $infoHash): bool
    {
        return $this->stopTorrent($infoHash);
    }

    /**
     * Remove a torrent by its infohash (gid in Aria2).
     */
    public function removeTorrent(string $infoHash): bool
    {
        try {
            $this->rpc('remove', [$infoHash]);

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
        try {
            $result = $this->rpc('getFiles', [$infoHash]);

            return $result ?? [];
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
            $result = $this->rpc('tellStatus', [$infoHash, ['status']]);

            return isset($result['status']) && $result['status'] === 'active';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the full RPC endpoint URL.
     */
    protected function getRpcUrl(): string
    {
        return rtrim($this->config['server'], '/').':'.$this->config['port'].'/jsonrpc';
    }
}
