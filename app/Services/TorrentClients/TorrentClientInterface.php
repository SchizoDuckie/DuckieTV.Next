<?php

namespace App\Services\TorrentClients;

/**
 * Abstraction layer for torrent client implementations.
 *
 * This interface defines the contract that all torrent client drivers
 * (uTorrent, qBittorrent, Transmission, etc.) must follow to integrate
 * with DuckieTV.Next.
 *
 * Each implementation handles the specifics of the client's API (REST, JSON-RPC, XML-RPC, etc.)
 * while exposing a unified set of methods for DuckieTV's internal services.
 *
 * @see BaseTorrentClient.js in DuckieTV-angular for the original JavaScript implementation.
 */
interface TorrentClientInterface
{
    /**
     * Attempts to connect to the torrent client's API.
     *
     * Performs authentication if required and initializes the connection state.
     *
     * @return bool True if connection was successful and authenticated.
     */
    public function connect(): bool;

    /**
     * Check if the client is currently connected and authenticated.
     */
    public function isConnected(): bool;

    /**
     * Retrieve the list of active torrents from the client.
     *
     * @return array Array of torrent metadata (status, progress, hash, etc.)
     */
    public function getTorrents(): array;

    /**
     * Add a magnet link to the torrent client.
     *
     * @param  string  $magnet  The magnet URI
     * @param  string|null  $dlPath  Optional custom download path on the client's filesystem.
     * @param  string|null  $label  Optional label or category (e.g., 'DuckieTV') for the client.
     * @return bool True if the magnet was successfully accepted by the client.
     */
    public function addMagnet(string $magnet, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Add a torrent by its URL.
     *
     * Instructs the client to download a remote .torrent file.
     *
     * @param  string  $url  The .torrent file URL
     * @param  string  $infoHash  The infohash of the torrent for verification.
     * @param  string  $releaseName  The readable name of the release.
     * @param  string|null  $dlPath  Optional custom download path.
     * @param  string|null  $label  Optional label or category.
     */
    public function addTorrentByUrl(string $url, ?string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Add a torrent by uploading its raw binary data.
     *
     * Used when the .torrent file is already fetched or generated locally.
     *
     * @param  string  $data  The raw .torrent file content.
     * @param  string  $infoHash  The infohash of the torrent for verification.
     * @param  string  $releaseName  The readable name of the release.
     * @param  string|null  $dlPath  Optional custom download path.
     * @param  string|null  $label  Optional label or category.
     */
    public function addTorrentByUpload(string $data, string $infoHash, string $releaseName, ?string $dlPath = null, ?string $label = null): bool;

    /**
     * Get the unique ID of the torrent client implementation.
     *
     * @return string e.g., 'qbittorrent41plus', 'transmission', etc.
     */
    public function getId(): string;

    /**
     * Get the display name of the torrent client implementation.
     *
     * @return string e.g., 'qBittorrent 4.1+', 'Transmission', etc.
     */
    public function getName(): string;

    /**
     * Refresh the client's internal configuration from the settings service.
     */
    public function readConfig(): void;

    /**
     * Get the validation rules for the settings of this client.
     */
    public function getValidationRules(): array;

    /**
     * Start a torrent by its infohash.
     */
    public function startTorrent(string $infoHash): bool;

    /**
     * Stop a torrent by its infohash.
     */
    public function stopTorrent(string $infoHash): bool;

    /**
     * Pause a torrent by its infohash.
     */
    public function pauseTorrent(string $infoHash): bool;

    /**
     * Remove a torrent by its infohash.
     */
    public function removeTorrent(string $infoHash): bool;

    /**
     * Get the list of individual files for a torrent.
     */
    public function getTorrentFiles(string $infoHash): array;

    /**
     * Check if a specific torrent is started.
     */
    public function isTorrentStarted(string $infoHash): bool;
}
