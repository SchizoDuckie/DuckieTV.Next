<?php

namespace App\Services\TorrentClients;

use Illuminate\Support\Facades\Http;
use App\Services\SettingsService;
use Exception;
use App\DTOs\TorrentData\QBittorrentData;

/**
 * qBittorrent 4.1+ Client Implementation.
 * 
 * This class handles communication with a qBittorrent server using its Web API v2.
 * It manages authentication, retrieving torrent lists, and adding torrents.
 * 
 * @see qBittorrent41plus.js in DuckieTV-angular for original implementation.
 */
class QBittorrentClient extends BaseTorrentClient
{
    /** @var string|null Authentication cookie */
    protected ?string $cookie = null;

    /**
     * @param SettingsService $settings
     */
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'qBittorrent 4.1+';
        $this->id = 'qbittorrent41plus';
    }

    public function getValidationRules(): array
    {
        return [
            'qbittorrent32plus.server' => 'nullable|url',
            'qbittorrent32plus.port' => 'nullable|integer',
            'qbittorrent32plus.use_auth' => 'boolean',
            'qbittorrent32plus.username' => 'nullable|string',
            'qbittorrent32plus.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for QBittorrent.
     * 
     * @return array
     */
    protected function getConfigMappings(): array
    {
        return [
            'server'   => 'qbittorrent32plus.server',
            'port'     => 'qbittorrent32plus.port',
            'username' => 'qbittorrent32plus.username',
            'password' => 'qbittorrent32plus.password',
            'use_auth' => 'qbittorrent32plus.use_auth',
        ];
    }

    /**
     * Construct a full URL for a qBittorrent API endpoint.
     * 
     * @param string $path
     * @return string
     */
    protected function getUrl(string $path): string
    {
        $server = $this->config['server'] ?? 'http://localhost';
        if (!preg_match('/^https?:\/\//', $server)) {
            $server = 'http://' . $server;
        }

        return rtrim($server, '/') . ':' . ($this->config['port'] ?? '8080') . '/api/v2/' . ltrim($path, '/');
    }

    /**
     * Authenticate with the qBittorrent server.
     * 
     * @return bool True if login was successful
     * @throws Exception if login request fails
     */
    public function connect(): bool
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::asForm()->post($this->getUrl('auth/login'), [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ]);

            if ($response->successful() && $response->body() === 'Ok.') {
                $this->cookie = $response->header('Set-Cookie');
                $this->connected = true;
                return true;
            }

            $this->connected = false;
            if (!$response->successful()) {
                throw new Exception("qBittorrent returned HTTP {$response->status()}: " . $response->body());
            }
            if ($response->body() !== 'Ok.') {
                throw new Exception("qBittorrent login failed: " . $response->body());
            }
            
            return false;
        } catch (Exception $e) {
            $this->connected = false;
            throw $e;
        }
    }


    /**
     * Retrieve the list of torrents from qBittorrent.
     * 
     * @return array
     */
    public function getTorrents(): array
    {
        if (!$this->connected && !$this->connect()) {
            return [];
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->get($this->getUrl('torrents/info'));

        $data = $response->json() ?? [];

        return collect($data)->map(fn ($torrent) => new QBittorrentData([
            'infoHash' => strtoupper($torrent['hash']),
            'name' => $torrent['name'],
            'progress' => (float)$torrent['progress'] * 100,
            'dlspeed' => $torrent['dlspeed'],
            'state' => $torrent['state'],
        ]))->all();
    }

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool
    {
        if (!$this->connected && !$this->connect()) return false;
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->asForm()
            ->post($this->getUrl('torrents/resume'), ['hashes' => $infoHash]);
        return $response->successful();
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
        if (!$this->connected && !$this->connect()) return false;
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->asForm()
            ->post($this->getUrl('torrents/pause'), ['hashes' => $infoHash]);
        return $response->successful();
    }

    /**
     * Remove a torrent by its infohash.
     */
    public function removeTorrent(string $infoHash): bool
    {
        if (!$this->connected && !$this->connect()) return false;
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->asForm()
            ->post($this->getUrl('torrents/delete'), [
                'hashes' => $infoHash,
                'deleteFiles' => 'true'
            ]);
        return $response->successful();
    }

    /**
     * Get the list of individual files for a torrent.
     */
    public function getTorrentFiles(string $infoHash): array
    {
        if (!$this->connected && !$this->connect()) return [];
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->get($this->getUrl('torrents/files'), ['hash' => $infoHash]);
        return $response->json() ?? [];
    }

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool
    {
        if (!$this->connected && !$this->connect()) return false;
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->get($this->getUrl('torrents/info'), ['hashes' => $infoHash]);
        $data = $response->json()[0] ?? null;
        return $data && !in_array($data['state'], ['pausedDL', 'pausedUP', 'checkingResumeData']);
    }

    /**
     * Add a magnet link to qBittorrent.
     * 
     * @param string $magnet
     * @param string|null $dlPath
     * @param string|null $label
     * @return bool
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        if (!$this->connected && !$this->connect()) {
            return false;
        }

        $params = [
            'urls' => $magnet,
        ];

        if ($dlPath) $params['savepath'] = $dlPath;
        if ($label) $params['category'] = $label;

        $response = Http::withHeaders(['Cookie' => $this->cookie])
            ->asForm()
            ->post($this->getUrl('torrents/add'), $params);

        return $response->successful();
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
        // qBittorrent handles URLs in the same 'add' endpoint as magnets
        return $this->addMagnet($url, $dlPath, $label);
    }

    /**
     * Add a torrent by uploading its raw data.
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
        if (!$this->connected && !$this->connect()) {
            return false;
        }

        $request = Http::withHeaders(['Cookie' => $this->cookie])
            ->attach('torrents', $data, $releaseName . '.torrent');

        if ($dlPath) $request->data(['savepath' => $dlPath]);
        if ($label) $request->data(['category' => $label]);

        $response = $request->post($this->getUrl('torrents/add'));

        return $response->successful();
    }
}
