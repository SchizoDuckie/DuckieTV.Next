<?php

namespace App\DTOs\TorrentData;

/**
 * UTorrentWebUIData is the main wrapper for a torrent info object coming from the uTorrent Web UI.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * https://forum.utorrent.com/topic/21814-web-ui-api/
 * https://github.com/bittorrent/webui/blob/master/webui.js
 *
 * - Does not support setting download directory.
 *   You can add sub-directories to the default download directory by appending
 *   '&download_dir=0,&path=' + urlencode(subdir)
 *   or select a predefined path using &download_dir=n (where n is the index to the path table).
 * - Does not support setting a Label during add.torrent.
 * - There is a maximum length limit of 1K on magnet strings.
 */
class UTorrentWebUIData extends TorrentData
{
    public ?string $name = null;

    public ?string $infoHash = null;

    public ?int $progress = null;

    public ?int $status = null;

    public ?int $downloadSpeed = null;

    public ?string $download_dir = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'progress',
        'status',
        'downloadSpeed',
        'download_dir',
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
     * uTorrent reports progress in per-mille (0-1000), so divide by 10.
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
     * The torrent is started if the status is uneven (odd).
     */
    public function isStarted(): bool
    {
        return ($this->status ?? 0) % 2 === 1;
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
        return $this->download_dir;
    }
}
