<?php

namespace App\DTOs\TorrentData;

/**
 * TransmissionData is the main wrapper for a torrent info object coming from Transmission.
 * It extends the base TorrentData class.
 *
 * Also used by BiglyBT and Vuze, which share the exact same API as Transmission.
 *
 * API Docs:
 * https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt
 *
 * - Supports setting download directory.
 * - Does not support setting a Label.
 */
class TransmissionData extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?int $id = null;

    public ?float $progress = null;

    public ?int $downloadSpeed = null;

    public ?int $status = null;

    public ?string $downloadDir = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'id',
        'progress',
        'downloadSpeed',
        'status',
        'downloadDir',
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
     * Transmission may report progress as 0-1 or 0-100 depending on the progressX100 config setting.
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
     * Send start command to the torrent client implementation for this torrent.
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
     */
    public function isStarted(): bool
    {
        return ($this->status ?? 0) > 0;
    }

    /**
     * Get files for this torrent.
     * Transmission includes files in the torrent data directly.
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
        return $this->downloadDir;
    }
}
