<?php

namespace App\DTOs\TorrentData;

/**
 * NoneData is a dummy torrent client data object.
 * It responds as connected and returns as if all torrents are completed.
 *
 * For use by those that either: are using an unsupported torrent client or don't want
 * to connect to any of the existing ones.
 *
 * This has the benefit of preventing unnecessary log clutter with failed connection attempts,
 * and allows other processes to complete successfully, such as marking a torrent as downloaded
 * after the user launches a torrent manually.
 */
class NoneData extends TorrentData
{
    public ?string $name = null;

    public ?string $hash = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'hash',
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
     * Progress percentage 0-100. Always returns 100 (completed).
     */
    public function getProgress(): float
    {
        return 100;
    }

    /**
     * Get torrent download speed in B/s. Always returns 0.
     */
    public function getDownloadSpeed(): int
    {
        return 0;
    }

    /**
     * Send start command to the torrent client implementation for this torrent. No-op.
     */
    public function start(): mixed
    {
        return true;
    }

    /**
     * Send stop command to the torrent client implementation for this torrent. No-op.
     */
    public function stop(): mixed
    {
        return true;
    }

    /**
     * Send pause command to the torrent client implementation for this torrent. No-op.
     */
    public function pause(): mixed
    {
        return true;
    }

    /**
     * Send remove command to the torrent client implementation for this torrent.
     */
    public function remove(): mixed
    {
        return $this->getClient()->getAPI()->remove($this->hash);
    }

    /**
     * Send isStarted query to the torrent client implementation for this torrent. Always returns false.
     */
    public function isStarted(): bool
    {
        return false;
    }

    /**
     * Send get files command to the torrent client implementation for this torrent.
     */
    public function getFiles(): mixed
    {
        return $this->getClient()->getAPI()->getFiles($this->hash);
    }

    /**
     * Get the download directory for this torrent. Not supported.
     */
    public function getDownloadDir(): ?string
    {
        return null;
    }
}
