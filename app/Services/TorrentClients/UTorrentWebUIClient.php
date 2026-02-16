<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\UTorrentWebUIData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * uTorrent Web UI Client Implementation.
 *
 * Handles communication with uTorrent via its Web UI token-based API.
 *
 * @see uTorrentWebUI.js in DuckieTV-angular.
 * @see https://forum.utorrent.com/topic/21814-web-ui-api/
 */
class UTorrentWebUIClient extends BaseTorrentClient
{
    /** @var string|null Security token required for requests */
    protected ?string $token = null;

    /** @var string|null Cookie for persistence */
    protected ?string $cookie = null;

    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'uTorrent Web UI';
        $this->id = 'utorrentwebui';
    }

    public function getValidationRules(): array
    {
        return [
            'utorrentwebui.server' => 'nullable|url',
            'utorrentwebui.port' => 'nullable|integer',
            'utorrentwebui.use_auth' => 'boolean',
            'utorrentwebui.username' => 'nullable|string',
            'utorrentwebui.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for uTorrent Web UI.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'utorrentwebui.server',
            'port' => 'utorrentwebui.port',
            'username' => 'utorrentwebui.username',
            'password' => 'utorrentwebui.password',
            'use_auth' => 'utorrentwebui.use_auth',
        ];
    }

    /**
     * Test connection and retrieve token.
     */
    public function connect(): bool
    {
        try {
            $url = $this->getBaseUrl().'/gui/token.html';

            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            $response = $request->get($url);
            if (! $response->successful()) {
                return false;
            }

            // Extract token from <div id='token'>TOKEN</div>
            $crawler = new Crawler($response->body());
            $tokenNode = $crawler->filter('#token');
            if ($tokenNode->count() > 0) {
                $this->token = $tokenNode->text();

                // Store cookie for subsequent requests if provided
                if ($response->header('Set-Cookie')) {
                    if (preg_match('/GUID=([^;]+)/', $response->header('Set-Cookie'), $matches)) {
                        $this->cookie = $matches[1];
                    }
                }
                $this->connected = true;

                return true;
            }

            $this->connected = false;

            return false;
        } catch (Exception $e) {
            $this->connected = false;

            return false;
        }
    }

    /**
     * Get list of torrents.
     */
    public function getTorrents(): array
    {
        if (! $this->token && ! $this->connect()) {
            return [];
        }

        try {
            $response = $this->request('list=1');
            if (! isset($response['torrents'])) {
                return [];
            }

            return collect($response['torrents'])->map(fn ($torrent) => new UTorrentWebUIData([
                'infoHash' => strtoupper($torrent[0]),
                'name' => $torrent[2],
                'progress' => (float) $torrent[4] / 10, // permille to percentage
                'status' => $torrent[21],
                'download_speed' => $torrent[9],
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
        try {
            $this->request('action=start&hash='.$infoHash);

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
            $this->request('action=stop&hash='.$infoHash);

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
        try {
            $this->request('action=pause&hash='.$infoHash);

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
        try {
            // action=remove removes from list, action=removedata removes from disk too.
            // Matching DuckieTV-angular preference usually being remove (keeping data).
            $this->request('action=remove&hash='.$infoHash);

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
            $response = $this->request('action=getfiles&hash='.$infoHash);

            return $response['files'] ?? [];
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
            $response = $this->request('list=1'); // uTorrent doesn't have a good "single torrent info" endpoint
            $torrent = collect($response['torrents'] ?? [])->first(fn ($t) => strtoupper($t[0]) === strtoupper($infoHash));

            return $torrent && $torrent[1] % 2 === 1;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link.
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        // uTorrent has a 1K limit on magnet strings in some versions
        if (strlen($magnet) > 1024) {
            // Basic trimming of trackers if too long (rough approximation)
            $parts = explode('&tr=', $magnet);
            $magnet = $parts[0];
            for ($i = 1; $i < count($parts); $i++) {
                if (strlen($magnet.'&tr='.$parts[$i]) < 1024) {
                    $magnet .= '&tr='.$parts[$i];
                }
            }
        }

        try {
            $this->request('action=add-url&s='.urlencode($magnet));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a torrent by its URL.
     */
    public function addTorrentByUrl(string $url, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        return $this->addMagnet($url, $dlPath, $label);
    }

    /**
     * Add a torrent by uploading its raw binary data.
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool
    {
        if (! $this->token && ! $this->connect()) {
            return false;
        }

        try {
            $url = $this->getBaseUrl().'/gui/?token='.$this->token.'&action=add-file';

            $request = Http::asMultipart();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            if ($this->cookie) {
                $request->withCookies(['GUID' => $this->cookie], parse_url($this->config['server'], PHP_URL_HOST));
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->attach('torrent_file', $data, $releaseName.'.torrent')
                ->post($url);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Perform a request to the uTorrent API.
     *
     * @throws Exception
     */
    protected function request(string $query): array
    {
        $url = $this->getBaseUrl().'/gui/?token='.$this->token.'&'.$query;

        $request = Http::asForm();
        if ($this->config['use_auth']) {
            $request->withBasicAuth($this->config['username'], $this->config['password']);
        }
        if ($this->cookie) {
            $request->withCookies(['GUID' => $this->cookie], parse_url($this->config['server'], PHP_URL_HOST));
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $request->get($url);

        if (! $response->successful()) {
            // If 400/401, token might have expired
            if ($response->status() === 400 || $response->status() === 401) {
                $this->connect();

                // retry once
                return $this->request($query);
            }
            throw new Exception('uTorrent API error: '.$response->status());
        }

        return $response->json();
    }

    /**
     * Get the base URL.
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['server'], '/').':'.$this->config['port'];
    }
}
