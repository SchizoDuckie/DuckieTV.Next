<?php

namespace App\DTOs\TorrentData;

/**
 * Aria2Data is the main wrapper for a torrent info object coming from Aria2.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * https://aria2.github.io/manual/en/html/aria2c.html#rpc-interface
 *
 * JSON-RPC API listens on localhost:6800 by default.
 *
 * - Supports setting the download directory.
 * - Does not support setting or fetching a Label.
 *
 * @see https://aria2.github.io/manual/en/html/aria2c.html#aria2.tellStatus
 */
class Aria2Data extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?string $gid = null;

    public ?string $status = null;

    public ?float $progress = null;

    public ?int $downloadSpeed = null;

    public ?string $dir = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'progress',
        'gid',
        'status',
        'downloadSpeed',
        'dir',
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
     * Send start (unpause) command to the torrent client implementation for this torrent.
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
     */
    public function isStarted(): bool
    {
        return $this->status === 'active';
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
        return $this->dir;
    }
}
