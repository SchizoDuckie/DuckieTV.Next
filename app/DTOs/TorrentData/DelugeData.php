<?php

namespace App\DTOs\TorrentData;

/**
 * DelugeData is the main wrapper for a torrent info object coming from Deluge.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * https://deluge.readthedocs.io/en/develop/index.html
 *
 * - Supports setting download directory.
 * - Does not support setting a label during add.torrent.
 */
class DelugeData extends TorrentData
{
    public ?string $name = null;
    public ?string $infoHash = null;
    public ?float $progress = null;
    public ?string $state = null;
    public ?int $downloadSpeed = null;
    public ?string $save_path = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'progress',
        'state',
        'downloadSpeed',
        'save_path',
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
     * Progress percentage 0-100. Round to one digit to make sure that torrents are not stopped before 100%.
     */
    public function getProgress(): float
    {
        return $this->round($this->progress ?? 0, 1);
    }

    /**
     * Get torrent download speed in B/s.
     */
    public function getDownloadSpeed(): int
    {
        return $this->downloadSpeed ?? 0;
    }

    /**
     * Send start (resume) command to the torrent client implementation for this torrent.
     */
    public function start(): mixed
    {
        return $this->getClient()->startTorrent($this->infoHash);
    }

    /**
     * Send stop (pause) command to the torrent client implementation for this torrent.
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
        return $this->stop();
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
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return in_array($this->state, ['Downloading', 'Seeding', 'Active'], true);
    }

    /**
     * Send get files command to the torrent client implementation for this torrent.
     */
    public function getFiles(): mixed
    {
        return $this->getClient()->getTorrentFiles($this->infoHash);
    }

    /**
     * Get the download directory for this torrent.
     */
    public function getDownloadDir(): ?string
    {
        return $this->save_path;
    }
}
