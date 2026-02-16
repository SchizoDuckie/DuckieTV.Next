<?php

namespace App\DTOs\TorrentData;

/**
 * rTorrentData is the main wrapper for a torrent info object coming from rTorrent.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * https://github.com/rakshasa/rtorrent/wiki/RPC-Setup-XMLRPC
 * https://github.com/rakshasa/rtorrent/wiki/rTorrent-0.9-Comprehensive-Command-list-(WIP)
 *
 * - Supports setting download directory.
 * - Does not support setting a Label.
 */
class RTorrentData extends TorrentData
{
    public ?string $name = null;
    public ?string $infoHash = null;
    public ?int $bytes_done = null;
    public ?int $size_bytes = null;
    public ?int $downloadSpeed = null;
    public ?float $progress = null;
    public ?int $state = null;
    public ?string $base_filename = null;
    public ?string $directory_base = null;

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [
        'name',
        'infoHash',
        'bytes_done',
        'size_bytes',
        'downloadSpeed',
        'progress',
        'state',
        'base_filename',
        'directory_base',
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
        if ($this->progress !== null) {
            return $this->round($this->progress, 1);
        }
        $size = $this->size_bytes ?? 0;
        if ($size <= 0) {
            return 0;
        }
        return $this->round(($this->bytes_done ?? 0) / $size * 100, 1);
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
     * Send remove (erase) command to the torrent client implementation for this torrent.
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
        return ($this->state ?? 0) > 0;
    }

    /**
     * Get files for this torrent.
     * rTorrent cannot easily return file lists without parsing the .torrent,
     * so returns the base filename as a single-file array.
     */
    public function getFiles(): mixed
    {
        return [['name' => $this->base_filename]];
    }

    /**
     * Get the download directory for this torrent.
     */
    public function getDownloadDir(): ?string
    {
        return $this->directory_base;
    }
}
