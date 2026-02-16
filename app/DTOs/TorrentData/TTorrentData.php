<?php

namespace App\DTOs\TorrentData;

/**
 * TTorrentData is the main wrapper for a torrent info object coming from tTorrent (Android BitTorrent).
 * It extends the base TorrentData class.
 *
 * https://ttorrent.org/
 *
 * API Docs:
 * None found. The Web UI has been divined by examining the network traffic.
 *
 * - Does not support setting download directory.
 * - Does not support setting a Label.
 */
class TTorrentData extends TorrentData
{
    public ?string $name = null;
    public ?string $infoHash = null;
    public ?float $progress = null;
    public ?string $status = null;
    public ?int $downSpeed = null;
    public ?int $upSpeed = null;
    public ?string $size = null;
    public ?string $eta = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'progress',
        'status',
        'downSpeed',
        'upSpeed',
        'size',
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
     */
    public function getDownloadSpeed(): int
    {
        return $this->downSpeed ?? 0;
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
        return $this->pause();
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
     * Valid started states: 'downloading', 'seeding'.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['downloading', 'seeding'], true);
    }

    /**
     * Get files for this torrent.
     * Since files are not supported by tTorrent's webui, returns the size and ETA instead.
     */
    public function getFiles(): mixed
    {
        return [
            ['name' => implode(' ', ['Files: n/a | TotalSize:', $this->size, '| ETA:', $this->eta])],
        ];
    }

    /**
     * Get the download directory for this torrent.
     * Not supported by tTorrent.
     */
    public function getDownloadDir(): ?string
    {
        return null;
    }
}
