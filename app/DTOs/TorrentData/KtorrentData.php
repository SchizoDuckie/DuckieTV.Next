<?php

namespace App\DTOs\TorrentData;

/**
 * KtorrentData is the main wrapper for a torrent info object coming from Ktorrent.
 * It extends the base TorrentData class.
 *
 * API Docs:
 * None. Reverse engineered from Ktorrent base implementation webui traffic.
 * https://github.com/KDE/ktorrent
 *
 * XMLHTTP API listens on localhost:8080.
 *
 * - Does not support setting or fetching the download directory.
 * - Does not support setting or fetching a Label.
 *
 * Torrent data contains:
 *   name, bytes_downloaded, bytes_uploaded, download_rate, info_hash,
 *   leechers, leechers_total, num_files, num_peers, percentage,
 *   running, seeders, seeders_total, status, total_bytes,
 *   total_bytes_to_download, upload_rate.
 */
class KtorrentData extends TorrentData
{
    public ?string $name = null;
    public ?string $infoHash = null;
    public ?int $id = null;
    public ?string $percentage = null;
    public ?string $download_rate = null;
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
     * Progress percentage 0-100. Round to one digit to make sure that torrents are not stopped before 100%.
     */
    public function getProgress(): float
    {
        return $this->round((float) ($this->percentage ?? 0), 1);
    }

    /**
     * Get torrent download speed in B/s.
     * Parses the human-readable rate string (e.g. "500 KiB/s") into bytes per second.
     */
    public function getDownloadSpeed(): int
    {
        if (!$this->download_rate) {
            return 0;
        }

        $parts = explode(' ', $this->download_rate);
        $rate = (int) ($parts[0] ?? 0);
        $units = $parts[1] ?? 'B/s';

        return match ($units) {
            'KiB/s' => $rate * 1024,
            'MiB/s' => $rate * 1024 * 1024,
            'GiB/s' => $rate * 1024 * 1024 * 1024,
            default => $rate,
        };
    }

    /**
     * Send start command to the torrent client implementation for this torrent.
     */
    public function start(): mixed
    {
        return $this->getClient()->startTorrent($this->id);
    }

    /**
     * Send stop command to the torrent client implementation for this torrent.
     */
    public function stop(): mixed
    {
        return $this->getClient()->stopTorrent($this->id);
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
        return $this->getClient()->removeTorrent($this->id);
    }

    /**
     * Send isStarted query to the torrent client implementation for this torrent.
     * Valid started states: 'stalled', 'downloading'.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['stalled', 'downloading'], true);
    }

    /**
     * Send get files command to the torrent client implementation for this torrent.
     */
    public function getFiles(): mixed
    {
        return $this->getClient()->getTorrentFiles($this->id);
    }

    /**
     * Get the download directory for this torrent.
     * Not supported by Ktorrent.
     */
    public function getDownloadDir(): ?string
    {
        return null;
    }
}
