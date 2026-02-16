<?php

namespace App\Services\TorrentClients;

use App\DTOs\TorrentData\RTorrentData;
use App\Services\SettingsService;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * rTorrent Client Implementation.
 *
 * Handles communication with rTorrent via XML-RPC.
 * Since no specialized XML-RPC client is available, we use manually constructed XML templates.
 *
 * @see rTorrent.js in DuckieTV-angular.
 * @see https://github.com/rakshasa/rtorrent/wiki/RPC-Setup-XMLRPC
 */
class RTorrentClient extends BaseTorrentClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'rTorrent';
        $this->id = 'rtorrent';
    }

    public function getValidationRules(): array
    {
        return [
            'rtorrent.server' => 'nullable|url',
            'rtorrent.port' => 'nullable|integer',
            'rtorrent.path' => 'nullable|string',
            'rtorrent.use_auth' => 'boolean',
        ];
    }

    /**
     * Set up configuration mappings for rTorrent.
     */
    protected function getConfigMappings(): array
    {
        return [
            'server' => 'rtorrent.server',
            'port' => 'rtorrent.port',
            'path' => 'rtorrent.path',
        ];
    }

    /**
     * Test connection to rTorrent.
     */
    public function connect(): bool
    {
        $result = $this->rpc('system.api_version');

        return ! empty($result);
    }

    /**
     * Get list of torrents from rTorrent.
     */
    public function getTorrents(): array
    {
        try {
            $hashes = $this->rpc('download_list');
            if (empty($hashes)) {
                return [];
            }

            // In rTorrent, it's more efficient to do a multicall for properties.
            $props = [
                'd.base_filename', 'd.base_path', 'd.bytes_done', 'd.completed_bytes',
                'd.directory', 'd.directory_base', 'd.down.rate', 'd.down.total',
                'd.hash', 'd.name', 'd.size_bytes', 'd.state', 'd.up.rate',
            ];

            $args = [];
            foreach ($hashes as $hash) {
                foreach ($props as $prop) {
                    $args[] = ['methodName' => $prop, 'params' => [$hash]];
                }
            }

            $multicallResults = $this->rpc('system.multicall', [$args]);

            $torrents = [];
            $propCount = count($props);
            foreach ($hashes as $index => $hash) {
                $torrentData = ['infoHash' => strtoupper($hash)];
                for ($i = 0; $i < $propCount; $i++) {
                    $propName = str_replace(['d.', '.rate'], ['', '_rate'], $props[$i]);
                    $val = $multicallResults[$index * $propCount + $i] ?? null;
                    // multicall returns results in nested array format sometimes [ [val] ]
                    $torrentData[$propName] = is_array($val) ? ($val[0] ?? null) : $val;
                }
                $size = $torrentData['size_bytes'] ?? 0;
                $torrentData['progress'] = $size > 0 ? (($torrentData['bytes_done'] ?? 0) / $size) * 100 : 0;
                $torrents[] = new RTorrentData($torrentData);
            }

            return $torrents;
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
            $this->rpc('d.start', [$infoHash]);

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
            $this->rpc('d.stop', [$infoHash]);

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
            $this->rpc('d.pause', [$infoHash]);

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
            $this->rpc('d.erase', [$infoHash]);

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
        // rTorrent cannot easily return file lists without parsing terminal output or .torrent
        // matching rTorrent.js behavior of returning base filename as a single file.
        try {
            $name = $this->rpc('d.base_filename', [$infoHash]);

            return [['name' => is_array($name) ? ($name[0] ?? '') : $name]];
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
            $state = $this->rpc('d.state', [$infoHash]);
            $val = is_array($state) ? ($state[0] ?? 0) : $state;

            return (int) $val > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a magnet link to rTorrent.
     */
    public function addMagnet(string $magnet, ?string $downloadPath = null, ?string $label = null): bool
    {
        try {
            if ($downloadPath) {
                // load.start with specialized command to set directory
                $this->rpc('load.start', ['', $magnet, 'd.directory_base.set="'.$downloadPath.'"']);
            } else {
                $this->rpc('load.start', ['', $magnet]);
            }

            return true;
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
            // load.raw_start accepts base64 data in XML-RPC
            // But we need to wrap it in <base64> tag in the XML.
            // Our rpc helper needs to know how to wrap it.
            $this->rpc('load.raw_start', ['', base64_encode($data)], true);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute an XML-RPC method.
     *
     * @param  bool  $hasBase64  Whether the last parameter is base64 data
     *
     * @throws Exception
     */
    protected function rpc(string $method, array $params = [], bool $hasBase64 = false): mixed
    {
        $xml = "<?xml version=\"1.0\"?>\n<methodCall>\n<methodName>{$method}</methodName>\n<params>\n";

        foreach ($params as $index => $param) {
            $xml .= '<param><value>';
            if ($hasBase64 && $index === count($params) - 1) {
                $xml .= "<base64>{$param}</base64>";
            } elseif (is_int($param)) {
                $xml .= "<i4>{$param}</i4>";
            } else {
                $xml .= '<string>'.htmlspecialchars($param).'</string>';
            }
            $xml .= "</value></param>\n";
        }

        $xml .= "</params>\n</methodCall>";

        $url = rtrim($this->config['server'], '/').':'.$this->config['port'].'/'.ltrim($this->config['path'], '/');

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBody($xml, 'text/xml')->post($url);

        if (! $response->successful()) {
            throw new Exception('rTorrent XML-RPC error: '.$response->status());
        }

        return $this->parseXmlRpcResponse($response->body());
    }

    /**
     * Basic parsing of XML-RPC response.
     */
    protected function parseXmlRpcResponse(string $xml): mixed
    {
        // This is a very basic parser. For a "pro" solution we'd use a real library.
        // But for rTorrent's typical responses (arrays of strings, single strings/ints)
        // this regex-based approach is often sufficient for a port.

        if (preg_match_all('/<string>([^<]*)<\/string>/', $xml, $matches)) {
            return count($matches[1]) === 1 ? $matches[1][0] : $matches[1];
        }

        if (preg_match('/<i4>(\d+)<\/i4>/', $xml, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
