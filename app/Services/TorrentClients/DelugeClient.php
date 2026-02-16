<?php

namespace App\Services\TorrentClients;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;
use Exception;
use App\DTOs\TorrentData\DelugeData;

/**
 * Deluge Client Implementation.
 * 
 * Handles communication with Deluge via its JSON-RPC interface.
 * Requires session authentication.
 * 
 * @see Deluge.js in DuckieTV-angular.
 * @see http://deluge.readthedocs.io/en/develop/index.html
 */
class DelugeClient extends BaseTorrentClient
{
    /** @var int RPC request counter */
    protected int $requestCounter = 0;

    /** @var string|null Cookie for authentication */
    protected ?string $cookie = null;

    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'Deluge';
        $this->id = 'deluge';
    }

    public function getValidationRules(): array
    {
        return [
            'deluge.server' => 'nullable|url',
            'deluge.port' => 'nullable|integer',
            'deluge.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for Deluge.
     * 
     * @return array
     */
    protected function getConfigMappings(): array
    {
        return [
            'server'   => 'deluge.server',
            'port'     => 'deluge.port',
            'password' => 'deluge.password',
        ];
    }

    /**
     * Test connection to Deluge by performing login.
     * 
     * @return bool
     */
    public function connect(): bool
    {
        // Check if session is already valid
        $response = $this->rpc('auth.check_session');
        if ($response) {
            return true;
        }

        // Login if needed
        return $this->rpc('auth.login', [$this->config['password']]);
    }

    /**
     * Get list of torrents from Deluge.
     * 
     * @return array
     */
    public function getTorrents(): array
    {
        try {
            $data = $this->rpc('web.update_ui', [
                ['queue', 'hash', 'name', 'progress', 'state', 'save_path', 'download_payload_rate'], 
                []
            ]);

            if (!isset($data['torrents'])) {
                return [];
            }

            return collect($data['torrents'])->map(fn($task, $hash) => new DelugeData([
                'infoHash' => strtoupper($hash),
                'name' => $task['name'] ?? 'Unknown',
                'progress' => (float)($task['progress'] ?? 0),
                'state' => $task['state'] ?? 'Unknown',
                'download_payload_rate' => (int)($task['download_payload_rate'] ?? 0),
                'save_path' => $task['save_path'] ?? null,
            ]))->values()->all();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool
    {
        try {
            $this->rpc('core.resume_torrent', [[$infoHash]]);
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
        try {
            $this->rpc('core.pause_torrent', [[$infoHash]]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pause a torrent by its infohash.
     */
    public function pauseTorrent(string $infoHash): bool
    {
        return $this->stopTorrent($infoHash);
    }

    /**
     * Remove a torrent by its infohash.
     */
    public function removeTorrent(string $infoHash): bool
    {
        try {
            $this->rpc('core.remove_torrent', [$infoHash, false]);
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
            return $this->rpc('web.get_torrent_files', [$infoHash]);
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
            $torrents = $this->rpc('web.update_ui', [['hash', 'state'], ['hash' => $infoHash]]);
            $torrent = $torrents['torrents'][$infoHash] ?? null;
            return $torrent && in_array($torrent['state'], ['Downloading', 'Seeding', 'Active'], true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link to Deluge.
     * 
     * @param string $magnet
     * @param string|null $dlPath
     * @param string|null $label
     * @return bool
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        $options = [];
        if ($dlPath) {
            $options['download_location'] = $dlPath;
        }

        try {
            $this->rpc('web.add_torrents', [[
                [
                    'path' => $magnet,
                    'options' => $options
                ]
            ]]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a torrent by its URL.
     * 
     * @param string $url
     * @param string $infoHash
     * @param string $releaseName
     * @param string|null $dlPath
     * @param string|null $label
     * @return bool
     */
    public function addTorrentByUrl(string $url, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        return $this->addMagnet($url, $dlPath, $label);
    }

    /**
     * Add a torrent by uploading its raw binary data.
     * 
     * Deluge requires uploading to /upload first, then using the temporary file path in web.add_torrents.
     * 
     * @param string $data
     * @param string $infoHash
     * @param string $releaseName
     * @param string|null $dlPath
     * @param string|null $label
     * @return bool
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        try {
            $url = $this->getBaseUrl() . '/upload';
            
            $response = Http::withCookies($this->cookie ? ['_session_id' => $this->cookie] : [], $this->getBaseDomain())
                ->attach('file', $data, $releaseName . '.torrent')
                ->post($url);

            if (!$response->successful()) {
                return false;
            }

            $uploadResult = $response->json();
            if (!isset($uploadResult['files'][0])) {
                return false;
            }

            return $this->addMagnet($uploadResult['files'][0], $dlPath, $label);
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute a JSON-RPC method.
     * 
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    protected function rpc(string $method, array $params = []): mixed
    {
        $url = $this->getBaseUrl() . '/json';
        
        $request = Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);

        if ($this->cookie) {
            // Deluge uses a cookie named _session_id or similar. 
            // We need to handle cookie persistence.
            $request->withCookies(['_session_id' => $this->cookie], $this->getBaseDomain());
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $request->post($url, [
            'method' => $method,
            'params' => $params,
            'id'     => $this->requestCounter++,
        ]);

        if (!$response->successful()) {
            throw new Exception("Deluge RPC error: " . $response->status());
        }

        // Persistent session cookie
        if ($response->header('Set-Cookie')) {
            // Rough parsing of cookie string
            if (preg_match('/_session_id=([^;]+)/', $response->header('Set-Cookie'), $matches)) {
                $this->cookie = $matches[1];
            }
        }

        $data = $response->json();
        if (isset($data['error']) && $data['error'] !== null) {
            throw new Exception("Deluge RPC Error: " . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data['result'] ?? null;
    }

    /**
     * Get the base URL (protocol + host + port).
     * 
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['server'], '/') . ':' . $this->config['port'];
    }

    /**
     * Get the base domain for cookies.
     * 
     * @return string
     */
    protected function getBaseDomain(): string
    {
        return parse_url($this->config['server'], PHP_URL_HOST) ?? 'localhost';
    }
}
