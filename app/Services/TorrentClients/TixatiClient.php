<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\TixatiData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tixati Client Implementation.
 *
 * Handles communication with Tixati via its HTML-based Web UI.
 *
 * @see Tixati.js in DuckieTV-angular.
 */
class TixatiClient extends BaseTorrentClient
{
    /** @var array Cache for infohashes by GUID */
    protected array $infohashCache = [];

    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'Tixati';
        $this->id = 'tixati';
    }

    public function getValidationRules(): array
    {
        return [
            'tixati.server' => 'nullable|url',
            'tixati.port' => 'nullable|integer',
            'tixati.use_auth' => 'boolean',
            'tixati.username' => 'nullable|string',
            'tixati.password' => 'nullable|string',
        ];
    }

    /**
     * Set up configuration mappings for Tixati.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'tixati.server',
            'port' => 'tixati.port',
            'use_auth' => 'tixati.use_auth',
            'username' => 'tixati.username',
            'password' => 'tixati.password',
        ];
    }

    /**
     * Test connection to Tixati.
     */
    public function connect(): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl().'/home');
            $this->connected = $response->successful();

            return $this->connected;
        } catch (Exception $e) {
            $this->connected = false;

            return false;
        }
    }

    /**
     * Get list of torrents from Tixati.
     *
     * Tixati returns HTML, which we must scrape.
     */
    public function getTorrents(): array
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl().'/transfers');

            if (! $response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());

            return collect($crawler->filter('.xferslist > tbody > tr')->each(function (Crawler $node) {
                $tds = $node->filter('td');
                if ($tds->count() < 9) {
                    return null;
                }

                $guidMatch = [];
                if (preg_match('/\/transfers\/([a-z-A-Z0-9]+)\/details/', $tds->eq(1)->filter('a')->attr('href'), $guidMatch)) {
                    $guid = $guidMatch[1];
                    $hash = $this->infohashCache[$guid] ?? '';

                    return new TixatiData([
                        'infoHash' => strtoupper($hash),
                        'name' => $tds->eq(1)->text(),
                        'bytes' => $tds->eq(2)->text(),
                        'progress' => (int) $tds->eq(3)->text(),
                        'status' => $tds->eq(4)->text(),
                        'downSpeed' => (int) (str_replace(',', '', $tds->eq(5)->text()) ?: 0) * 1000,
                        'upSpeed' => (int) (str_replace(',', '', $tds->eq(6)->text()) ?: 0) * 1000,
                        'priority' => $tds->eq(7)->text(),
                        'eta' => $tds->eq(8)->text(),
                        'guid' => $guid,
                    ]);
                }

                return null;
            }))->filter()->values()->all();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Start a torrent by its GUID.
     */
    public function startTorrent(string $guid): bool
    {
        try {
            return $this->execute($guid, ['start' => 'Start']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Stop a torrent by its GUID.
     */
    public function stopTorrent(string $guid): bool
    {
        try {
            return $this->execute($guid, ['stop' => 'Stop']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pause a torrent by its GUID.
     */
    public function pauseTorrent(string $guid): bool
    {
        return $this->stopTorrent($guid);
    }

    /**
     * Remove a torrent by its GUID.
     */
    public function removeTorrent(string $guid): bool
    {
        try {
            return $this->execute($guid, [
                'removeconf' => 'Remove Transfers',
                'remove' => 'Remove',
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of individual files for a torrent GUID.
     */
    public function getTorrentFiles(string $guid): array
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($this->getBaseUrl()."/transfers/{$guid}/files");
            if (! $response->successful()) {
                return [];
            }
            $crawler = new Crawler($response->body());

            return $crawler->filter('.listtable > tbody > tr')->each(fn (Crawler $node) => [
                'name' => $node->filter('td')->eq(1)->text(),
                'priority' => $node->filter('td')->eq(2)->text(),
                'bytes' => $node->filter('td')->eq(3)->text(),
                'progress' => $node->filter('td')->eq(4)->text(),
            ]);
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

            return $torrent && stripos($torrent->status, 'offline') === false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute a control action for a torrent.
     */
    protected function execute(string $guid, array $formData): bool
    {
        $url = $this->getBaseUrl()."/transfers/{$guid}/options/action";
        $request = Http::asForm();
        if ($this->config['use_auth']) {
            $request->withBasicAuth($this->config['username'], $this->config['password']);
        }
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $request->post($url, $formData);

        return $response->successful();
    }

    /**
     * Add a magnet link to Tixati.
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool
    {
        try {
            $request = Http::asForm();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->post($this->getBaseUrl().'/transfers/action', [
                'addlinktext' => $magnet,
                'addlink' => 'Add',
            ]);

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
        try {
            $request = Http::asMultipart();
            if ($this->config['use_auth']) {
                $request->withBasicAuth($this->config['username'], $this->config['password']);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->attach('metafile', $data, $releaseName.'.torrent')
                ->post($this->getBaseUrl().'/transfers/action', [
                    'addmetafile' => 'Add',
                ]);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the base URL.
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['server'], '/').':'.$this->config['port'];
    }
}
