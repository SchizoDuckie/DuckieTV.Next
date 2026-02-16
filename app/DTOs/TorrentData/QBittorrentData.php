<?php

namespace App\DTOs\TorrentData;

/**
 * qBittorrentData is the main wrapper for a torrent info object coming from qBittorrent 4.1+.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation (v4.1+ APIv2)
 */
class QBittorrentData extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?float $progress = null;

    public ?int $downloadSpeed = null;

    public ?string $state = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'progress',
        'downloadSpeed',
        'state',
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
     * qBittorrent reports progress as 0-1 float, so multiply by 100.
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
     * Valid started states: 'downloading', 'uploading', 'stalledDL', 'stalledUP'.
     */
    public function isStarted(): bool
    {
        return in_array($this->state, ['downloading', 'uploading', 'stalledDL', 'stalledUP'], true);
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
     * Fetched from the files response's downloaddir property.
     */
    public function getDownloadDir(): ?string
    {
        return $this->files['downloaddir'] ?? null;
    }
}
