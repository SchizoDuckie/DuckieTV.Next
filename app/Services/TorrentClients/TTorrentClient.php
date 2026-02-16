<?php

namespace App\Services\TorrentClients;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;
use Exception;
use App\DTOs\TorrentData\TTorrentData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * tTorrent Client Implementation (Android).
 * 
 * Handles communication with tTorrent via its HTML scraping interface.
 * 
 * @see tTorrent.js in DuckieTV-angular.
 */
class TTorrentClient extends BaseTorrentClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'tTorrent';
        $this->id = 'ttorrent';
    }

    public function getValidationRules(): array
    {
        return [
            'ttorrent.server' => 'nullable|url',
            'ttorrent.port' => 'nullable|integer',
            'ttorrent.use_auth' => 'boolean',
            'ttorrent.username' => 'nullable|string',
            'ttorrent.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for tTorrent.
     * 
     * @return array
     */
    protected function getConfigMappings(): array
    {
        return [
            'server'   => 'ttorrent.server',
            'port'     => 'ttorrent.port',
            'username' => 'ttorrent.username',
            'password' => 'ttorrent.password',
            'use_auth' => 'ttorrent.use_auth',
        ];
    }

    /**
     * Test connection to tTorrent.
     * 
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl() . '/');
            
            if (!$response->successful()) {
                return false;
            }

            $crawler = new Crawler($response->body());
            $header = $crawler->filter('.header');
            $this->connected = $header->count() > 0 && str_contains($header->text(), 'tTorrent web interface');
            return $this->connected;
        } catch (Exception $e) {
            $this->connected = false;
            return false;
        }
    }

    /**
     * Get list of torrents from tTorrent.
     * 
     * Scrapes HTML list of torrents.
     * 
     * @return array
     */
    public function getTorrents(): array
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl() . '/torrents');

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            
            return collect($crawler->filter('.torrent')->each(function (Crawler $node) {
                $name = $node->filter('.torrentTitle')->text();
                $action = $node->filter('.inlineForm')->attr('action');
                
                if (preg_match('/\/cmd\/remove\/([0-9ABCDEFabcdef]{40})/', $action, $hashMatch)) {
                    $hash = strtoupper($hashMatch[1]);
                    $progressNode = $node->filter('.torrentDetails .progress');
                    $progress = 0;
                    
                    if ($progressNode->count() > 0 && preg_match('/width:\s*(\d+)%/', $progressNode->attr('style'), $pMatch)) {
                        $progress = (float)$pMatch[1];
                    }

                    return new TTorrentData([
                        'infoHash' => $hash,
                        'name' => $name,
                        'progress' => $progress,
                        'status' => 'Unknown', // Scraping doesn't easily show this status text
                    ]);
                }
                return null;
            }))->filter()->values()->all();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Start a torrent by its infoHash.
     */
    public function startTorrent(string $infoHash): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl() . '/cmd/start/' . $infoHash);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Stop a torrent by its infoHash.
     */
    public function stopTorrent(string $infoHash): bool
    {
        return $this->pauseTorrent($infoHash);
    }

    /**
     * Pause a torrent by its infoHash.
     */
    public function pauseTorrent(string $infoHash): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl() . '/cmd/pause/' . $infoHash);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove a torrent by its infoHash.
     */
    public function removeTorrent(string $infoHash): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl() . '/cmd/remove/' . $infoHash);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of individual files for a torrent infoHash.
     * Not supported by tTorrent's webui.
     */
    public function getTorrentFiles(string $infoHash): array
    {
        return [];
    }

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool
    {
        try {
            $torrents = $this->getTorrents();
            $torrent = collect($torrents)->first(fn($t) => strtoupper($t->infoHash) === strtoupper($infoHash));
            return $torrent !== null; // assume started if present for now
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link to tTorrent.
     * 
     * @param string $magnet
     * @param string|null $dlPath
     * @param string|null $label
     * @return bool
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->post($this->getBaseUrl() . '/cmd/downloadFromUrl', [
                'url' => $magnet
            ]);

            return $response->successful();
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
            $request = Http::asMultipart();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->attach('torrentfile', $data, $releaseName . '.torrent')
                ->post($this->getBaseUrl() . '/cmd/downloadTorrent');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the base URL.
     * 
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['server'], '/') . ':' . $this->config['port'];
    }
}
