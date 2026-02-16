<?php

namespace App\DTOs\TorrentData;

/**
 * UTorrentData is the main wrapper for a torrent info object coming from uTorrent (Remote/Pairing API).
 * It extends the base TorrentData class.
 *
 * API Docs:
 * None. Reverse engineered from uTorrent remote implementation.
 *
 * - Does not support setting download directory.
 * - Does not support setting a Label.
 */
class UTorrentData extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?float $progress = null;

    public ?string $status = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'id',
        'percentage',
        'download_rate',
        'status',
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
     * Not directly available in the list response without more parsing.
     */
    public function getDownloadSpeed(): int
    {
        return 0;
    }

    /**
     * Send start (resume) command to the torrent client implementation for this torrent.
     */
    public function start(): mixed
    {
        return $this->getClient()->startTorrent($this->infoHash);
    }

    /**
     * Send stop command to the torrent client implementation for this torrent.
     */
    public function stop(): mixed
    {
        return $this->getClient()->stopTorrent($this->infoHash);
    }

    /**
     * Send pause command to the torrent client implementation for this torrent.
     */
    public function pause(): mixed
    {
        return $this->getClient()->pauseTorrent($this->infoHash);
    }

    /**
     * Send remove command to the torrent client implementation for this torrent.
     */
    public function remove(): mixed
    {
        return $this->getClient()->removeTorrent($this->infoHash);
    }

    /**
     * Send isStarted query to the torrent client implementation for this torrent.
     * Valid started states: 'downloading', 'seeding', 'started'.
     */
    public function isStarted(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['downloading', 'seeding', 'started'], true);
    }

    /**
     * Get files for this torrent.
     */
    public function getFiles(): mixed
    {
        return $this->getClient()->getTorrentFiles($this->infoHash);
    }

    /**
     * Get the download directory for this torrent.
     * Not supported by uTorrent Remote.
     */
    public function getDownloadDir(): ?string
    {
        return null;
    }
}
