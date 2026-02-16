<?php

namespace App\DTOs\TorrentData;

/**
 * TixatiData is the main wrapper for a torrent info object coming from Tixati.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * None. Reverse engineered from Tixati base implementation.
 * Minimum supported version: 2.86.
 *
 * HTTP API listens on localhost:8888.
 *
 * Setup:
 * Enable web interface in Tixati options, set a username and password.
 * Make sure to use the default skin.
 *
 * - Does not support setting or fetching the download directory.
 * - Does not support setting or fetching Labels.
 */
class TixatiData extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?string $guid = null;

    public ?int $progress = null;

    public ?string $status = null;

    public ?int $downSpeed = null;

    public ?int $upSpeed = null;

    public ?string $bytes = null;

    public ?string $priority = null;

    public ?string $eta = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'guid',
        'progress',
        'status',
        'downSpeed',
        'upSpeed',
        'bytes',
        'priority',
        'eta',
        'files',
    ];

    /**
     * Display name for torrent.
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Progress percentage 0-100.
     */
    public function getProgress(): float
    {
        return (float) ($this->progress ?? 0);
    }

    /**
     * Get torrent download speed in B/s.
     * Note: actual unit is governed by Tixati settings (user interface, output formatting, bytes, largest unit).
     * Default is kB/s.
     */
    public function getDownloadSpeed(): int
    {
        return $this->downSpeed ?? 0;
    }

    /**
     * Send start command to the torrent client implementation for this torrent.
     */
    public function start(): mixed
    {
        return $this->getClient()->startTorrent($this->guid);
    }

    /**
     * Send stop command to the torrent client implementation for this torrent.
     */
    public function stop(): mixed
    {
        return $this->getClient()->stopTorrent($this->guid);
    }

    /**
     * Send pause command to the torrent client implementation for this torrent.
     */
    public function pause(): mixed
    {
        return $this->stop();
    }

    /**
     * Send remove command to the torrent client implementation for this torrent.
     */
    public function remove(): mixed
    {
        return $this->getClient()->removeTorrent($this->guid);
    }

    /**
     * Send isStarted query to the torrent client implementation for this torrent.
     * Started if status does not contain 'offline'.
     */
    public function isStarted(): bool
    {
        return stripos($this->status ?? 'offline', 'offline') === false;
    }

    /**
     * Send get files command to the torrent client implementation for this torrent.
     */
    public function getFiles(): mixed
    {
        return $this->getClient()->getTorrentFiles($this->guid);
    }

    /**
     * Get the download directory for this torrent.
     * Not supported by Tixati.
     */
    public function getDownloadDir(): ?string
    {
        return null;
    }
}
