<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\KtorrentData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * KTorrent Client Implementation.
 *
 * Handles communication with KTorrent via its XML-based Web API.
 *
 * @see Ktorrent.js in DuckieTV-angular.
 * @see https://github.com/KDE/ktorrent
 */
class KTorrentClient extends BaseTorrentClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'KTorrent';
        $this->id = 'ktorrent';
    }

    public function getValidationRules(): array
    {
        return [
            'ktorrent.server' => 'nullable|url',
            'ktorrent.port' => 'nullable|integer',
            'ktorrent.username' => 'nullable|string',
            'ktorrent.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for KTorrent.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'ktorrent.server',
            'port' => 'ktorrent.port',
            'username' => 'ktorrent.username',
            'password' => 'ktorrent.password',
        ];
    }

    /**
     * Test connection to KTorrent by performing login.
     */
    public function connect(): bool
    {
        try {
            // First get the challenge
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/login/challenge.xml');
            if (! $response->successful()) {
                return false;
            }

            $crawler = new Crawler($response->body());
            $challenge = $crawler->filter('challenge')->text();

            $sha = sha1($challenge.$this->config['password']);
            $request = Http::asForm();
            /** @var \Illuminate\Http\Client\Response $loginResponse */
            $loginResponse = $request->post($this->getBaseUrl().'/login?page=interface.html', [
                'username' => $this->config['username'],
                'password' => '', // empty password field as per JS implementation
                'Login' => 'Sign in',
                'challenge' => $sha,
            ]);

            return $loginResponse->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get list of torrents from KTorrent.
     */
    public function getTorrents(): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/data/torrents.xml');
            if (! $response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());

            return collect($crawler->filter('torrents torrent')->each(fn (Crawler $node, $index) => new KtorrentData([
                'infoHash' => strtoupper($node->filter('info_hash')->text()),
                'name' => $node->filter('name')->text(),
                'percentage' => $node->filter('percentage')->text(),
                'status' => $node->filter('status')->text(),
                'download_rate' => $node->filter('download_rate')->text(),
                'id' => $index,
            ])))->all();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Start a torrent by its infoHash or ID.
     */
    public function startTorrent(string $infoHash): bool
    {
        $id = $this->resolveId($infoHash);
        if ($id === null) {
            return false;
        }
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/action?start='.$id);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Stop a torrent by its infoHash or ID.
     */
    public function stopTorrent(string $infoHash): bool
    {
        $id = $this->resolveId($infoHash);
        if ($id === null) {
            return false;
        }
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/action?stop='.$id);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pause a torrent by its infoHash or ID.
     */
    public function pauseTorrent(string $infoHash): bool
    {
        return $this->stopTorrent($infoHash);
    }

    /**
     * Remove a torrent by its infoHash or ID.
     */
    public function removeTorrent(string $infoHash): bool
    {
        $id = $this->resolveId($infoHash);
        if ($id === null) {
            return false;
        }
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/action?remove='.$id);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of individual files for a torrent infoHash or ID.
     */
    public function getTorrentFiles(string $infoHash): array
    {
        $id = $this->resolveId($infoHash);
        if ($id === null) {
            return [];
        }
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/data/torrent/files.xml?torrent='.$id);
            if (! $response->successful()) {
                return [];
            }
            $crawler = new Crawler($response->body());

            return $crawler->filter('torrent file')->each(fn (Crawler $node) => [
                'name' => $node->filter('path')->text(),
                'percentage' => $node->filter('percentage')->text(),
                'size' => $node->filter('size')->text(),
                'priority' => $node->filter('priority')->text(),
            ]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Resolve an infoHash or ID string to the numeric ID required by KTorrent.
     */
    protected function resolveId(string $infoHashOrId): ?int
    {
        if (ctype_digit($infoHashOrId)) {
            return (int) $infoHashOrId;
        }
        $torrents = $this->getTorrents();
        $torrent = collect($torrents)->first(fn ($t) => strtoupper($t->infoHash) === strtoupper($infoHashOrId));

        return $torrent ? $torrent->id : null;
    }

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool
    {
        try {
            $torrents = $this->getTorrents();
            $torrent = collect($torrents)->first(fn ($t) => strtoupper($t->infoHash) === strtoupper($infoHash));

            return $torrent && in_array(strtolower($torrent->status), ['stalled', 'downloading'], true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link to KTorrent.
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->getBaseUrl().'/action?load_torrent='.urlencode($magnet));

            return $response->successful();
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
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        // KTorrent seems to support loading by URL/Magnet primarily in the JS.
        // If upload is needed, it would likely involve another endpoint.
        // For now, mirroring the JS implementation which falls back to load_torrent if possible.
        return false;
    }

    /**
     * Get the base URL.
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['server'], '/').':'.$this->config['port'];
    }
}
