<?php

namespace App\Presenters;

use App\Services\TorrentClients\TorrentClientInterface;
use Illuminate\Support\Str;

class TorrentClientPresenter
{
    private TorrentClientInterface $client;

    public function __construct(TorrentClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get the client ID.
     */
    public function getId(): string
    {
        return $this->client->getId();
    }

    /**
     * Get the client name.
     */
    public function getName(): string
    {
        return $this->client->getName();
    }

    /**
     * Get the icon filename.
     */
    public function getIcon(): string
    {
        return $this->getSlug().'-small.png';
    }

    /**
     * Get the CSS class.
     */
    public function getCssClass(): string
    {
        return $this->getSlug();
    }

    /**
     * Generate the slug for the client.
     * Handles legacy edge cases.
     */
    private function getSlug(): string
    {
        // Handle qBittorrent edge case for legacy matching
        if ($this->client->getId() === 'qbittorrent41plus') {
            return 'qbittorrent';
        }

        // Handle uTorrent Web UI sharing the same icon/class as uTorrent
        if ($this->client->getId() === 'utorrentwebui') {
            return 'utorrent';
        }

        return Str::slug($this->client->getName());
    }
}
