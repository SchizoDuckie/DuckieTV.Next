<?php

namespace App\Services\TorrentClients;

use App\Services\SettingsService;
use Illuminate\Support\Str;

/**
 * Base class for Torrent Client implementations.
 * 
 * Provides common functionality and properties for all torrent client drivers.
 * Drivers should extend this class and implement the abstract methods
 * to provide client-specific API logic.
 * 
 * @see BaseTorrentClient.js in DuckieTV-angular for original implementation.
 */
abstract class BaseTorrentClient implements TorrentClientInterface
{
    /** @var array Internal configuration for the client */
    protected array $config = [];

    /** @var string The display name of the client */
    protected string $name = 'Base Torrent Client';

    /** @var bool Connection status flag */
    protected bool $connected = false;

    /** @var SettingsService */
    protected SettingsService $settings;

    /** @var string The unique ID of the client */
    protected string $id = '';

    /**
     * @param SettingsService $settings
     */
    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
        $this->readConfig();
    }

    /**
     * Populate the config array using defined mappings.
     * 
     * @return void
     */
    public function readConfig(): void
    {
        $mappings = $this->getConfigMappings();
        foreach ($mappings as $key => $settingKey) {
            $this->config[$key] = $this->settings->get($settingKey);
        }
    }

    /**
     * Define mappings between internal config keys and DuckieTV settings keys.
     * 
     * @return array<string, string>
     */
    protected function getConfigMappings(): array
    {
        return [];
    }

    /**
     * Get the unique ID of this torrent client.
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the display name of this torrent client.
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the client is currently connected.
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Attempts to connect to the torrent client's API.
     * 
     * @return bool True if connection was successful
     */
    abstract public function connect(): bool;

    /**
     * Retrieve the list of active torrents from the client.
     * 
     * @return array Array of torrent data
     */
    abstract public function getTorrents(): array;

    /**
     * Add a magnet link to the torrent client.
     *
     * This method sends a magnet URI to the torrent client for download.
     *
     * @param string $magnet The magnet URI to add.
     * @param string|null $dlPath Optional custom download path for the torrent.
     * @param string|null $label Optional label or category to assign to the torrent.
     * @return bool True if the magnet link was successfully added, false otherwise.
     */
    abstract public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Add a torrent by its URL.
     *
     * This method instructs the torrent client to download a .torrent file from a given URL
     * and start the torrent.
     *
     * @param string $url The URL pointing to the .torrent file.
     * @param string $infoHash The infohash of the torrent for verification.
     * @param string $releaseName The name of the release, used for display or folder naming.
     * @param string|null $dlPath Optional custom download path for the torrent.
     * @param string|null $label Optional label or category to assign to the torrent.
     * @return bool True if the torrent was successfully added by URL, false otherwise.
     */
    abstract public function addTorrentByUrl(string $url, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Add a torrent by uploading its raw data.
     *
     * This method sends the raw content of a .torrent file directly to the client.
     *
     * @param string $data The raw binary content of the .torrent file.
     * @param string $infoHash The infohash of the torrent for verification.
     * @param string $releaseName The name of the release, used for display or folder naming.
     * @param string|null $dlPath Optional custom download path for the torrent.
     * @param string|null $label Optional label or category to assign to the torrent.
     * @return bool True if the torrent was successfully added by upload, false otherwise.
     */
    abstract public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool
    {
        return false;
    }

    /**
     * Stop a torrent by its infohash.
     */
    public function stopTorrent(string $infoHash): bool
    {
        return false;
    }

    /**
     * Pause a torrent by its infohash.
     */
    public function pauseTorrent(string $infoHash): bool
    {
        return false;
    }

    /**
     * Remove a torrent by its infohash.
     */
    public function removeTorrent(string $infoHash): bool
    {
        return false;
    }

    /**
     * Get the list of individual files for a torrent.
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
        return false;
    }
}
