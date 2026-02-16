<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\TransmissionData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Transmission torrent client implementation.
 *
 * @see Transmission.js in DuckieTV-angular for original implementation.
 * @see https://github.com/transmission/transmission/blob/main/docs/rpc-spec.md
 */
class TransmissionClient extends BaseTorrentClient
{
    /** @var string|null The active session ID required for requests */
    protected ?string $sessionId = null;

    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'Transmission';
        $this->id = 'transmission';
    }

    public function getValidationRules(): array
    {
        return [
            'transmission.server' => 'nullable|url',
            'transmission.port' => 'nullable|integer',
            'transmission.path' => 'nullable|string',
            'transmission.use_auth' => 'boolean',
            'transmission.username' => 'nullable|string',
            'transmission.password' => 'nullable|string',
            'transmission.progressX100' => 'boolean',
        ];
    }

    /**
     * Set up configuration mappings for Transmission.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'transmission.server',
            'port' => 'transmission.port',
            'path' => 'transmission.path',
            'username' => 'transmission.username',
            'password' => 'transmission.password',
            'use_auth' => 'transmission.use_auth',
        ];
    }

    /**
     * Test connection to Transmission.
     */
    public function connect(): bool
    {
        $response = $this->rpc('session-get');
        $this->connected = isset($response['result']) && $response['result'] === 'success';

        return $this->connected;
    }

    /**
     * Get list of torrents from Transmission.
     */
    public function getTorrents(): array
    {
        $response = $this->rpc('torrent-get', [
            'fields' => [
                'id', 'name', 'hashString', 'status', 'error', 'errorString', 'eta',
                'isFinished', 'isStalled', 'leftUntilDone', 'metadataPercentComplete',
                'percentDone', 'sizeWhenDone', 'files', 'rateDownload', 'rateUpload', 'downloadDir',
            ],
        ]);

        if (! isset($response['arguments']['torrents'])) {
            return [];
        }

        return collect($response['arguments']['torrents'])->map(fn ($torrent) => new TransmissionData([
            'infoHash' => strtoupper($torrent['hashString']),
            'name' => $torrent['name'],
            'progress' => (float) $torrent['percentDone'] * 100,
            'status' => $this->getTransmissionStatus($torrent['status']),
            'isStarted' => $torrent['status'] > 0,
            'downloadSpeed' => $torrent['rateDownload'],
        ]))->all();
    }

    /**
     * Add a magnet link to Transmission.
     *
     * @param  string  $magnet  Magnet link
     * @param  string|null  $downloadPath  Optional download path
     * @param  string|null  $label  Optional label (not supported by Transmission)
     */
    public function addMagnet(string $magnet, ?string $downloadPath = null, ?string $label = null): bool
    {
        $args = [
            'paused' => false,
            'filename' => $magnet,
        ];

        if ($downloadPath) {
            $args['download-dir'] = $downloadPath;
        }

        $response = $this->rpc('torrent-add', $args);

        return isset($response['result']) && $response['result'] === 'success';
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
     * Add a torrent by uploading its raw binary data.
     *
     * @param  string  $data  The raw .torrent file content.
     * @param  string  $infoHash  The infohash of the torrent for verification.
     * @param  string  $releaseName  The readable name of the release.
     * @param  string|null  $dlPath  Optional custom download path.
     * @param  string|null  $label  Optional label or category.
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        $args = [
            'paused' => false,
            'metainfo' => base64_encode($data),
        ];

        if ($dlPath) {
            $args['download-dir'] = $dlPath;
        }

        $response = $this->rpc('torrent-add', $args);

        return isset($response['result']) && $response['result'] === 'success';
    }

    protected function getTransmissionStatus(int $status): string
    {
        return match ($status) {
            0 => 'Stopped',
            1 => 'Check Wait',
            2 => 'Check',
            3 => 'Download Wait',
            4 => 'Downloading',
            5 => 'Seed Wait',
            6 => 'Seeding',
            default => 'Unknown',
        };
    }

    /**
     * Execute an RPC request to Transmission.
     *
     * @param  string  $method  RPC method name
     * @param  array  $args  Method arguments
     * @param  bool  $isRetry  Whether this is a retry attempt after 409/Session-Id update
     *
     * @throws Exception
     */
    protected function rpc(string $method, array $args = [], bool $isRetry = false): array
    {
        $url = rtrim($this->config['server'], '/').':'.$this->config['port'].'/'.ltrim($this->config['path'], '/');

        $request = Http::withHeaders([
            'X-Transmission-Session-Id' => $this->sessionId ?? '',
        ]);

        if (isset($this->config['use_auth']) && $this->config['use_auth']) {
            $request->withBasicAuth($this->config['username'], $this->config['password']);
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $request->post($url, [
            'method' => $method,
            'arguments' => $args,
        ]);

        // Handle Session-Id update (409 Conflict)
        if ($response->status() === 409) {
            $this->sessionId = $response->header('X-Transmission-Session-Id');
            if (! $isRetry) {
                return $this->rpc($method, $args, true);
            }
        }

        if (! $response->successful()) {
            throw new Exception('Transmission RPC error: '.$response->status().' '.$response->body());
        }

        return $response->json();
    }

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool
    {
        $response = $this->rpc('torrent-start', ['ids' => [$infoHash]]);

        return isset($response['result']) && $response['result'] === 'success';
    }

    /**
     * Stop a torrent by its infohash.
     */
    public function stopTorrent(string $infoHash): bool
    {
        $response = $this->rpc('torrent-stop', ['ids' => [$infoHash]]);

        return isset($response['result']) && $response['result'] === 'success';
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
        $response = $this->rpc('torrent-remove', ['ids' => [$infoHash], 'delete-local-data' => true]);

        return isset($response['result']) && $response['result'] === 'success';
    }

    /**
     * Get the list of individual files for a torrent.
     */
    public function getTorrentFiles(string $infoHash): array
    {
        $response = $this->rpc('torrent-get', [
            'ids' => [$infoHash],
            'fields' => ['files'],
        ]);

        return $response['arguments']['torrents'][0]['files'] ?? [];
    }

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool
    {
        $response = $this->rpc('torrent-get', [
            'ids' => [$infoHash],
            'fields' => ['status'],
        ]);

        $status = $response['arguments']['torrents'][0]['status'] ?? 0;

        return $status > 0;
    }
}
