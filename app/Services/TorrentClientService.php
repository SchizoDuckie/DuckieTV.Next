<?php

namespace App\Services;

use App\Services\TorrentClients\TorrentClientInterface;

/**
 * Service to manage registered torrent clients.
 *
 * Acts as a registry where different torrent client implementations
 * (qBittorrent, Transmission, uTorrent, etc.) can be found.
 */
class TorrentClientService
{
    /** @var array<string, TorrentClientInterface> Registered clients */
    protected array $clients = [];

    protected SettingsService $settings;

    /**
     * @param  iterable<TorrentClientInterface>  $clients
     */
    public function __construct(SettingsService $settings, iterable $clients = [])
    {
        $this->settings = $settings;
        foreach ($clients as $client) {
            $this->registerClient($client->getName(), $client);
        }
    }

    /**
     * Register a torrent client implementation.
     */
    public function registerClient(string $name, TorrentClientInterface $client): void
    {
        $this->clients[$name] = $client;
    }

    /**
     * Get a registered client by name.
     */
    public function getClient(string $name): ?TorrentClientInterface
    {
        return $this->clients[$name] ?? null;
    }

    /**
     * Get the currently active client based on settings.
     */
    public function getActiveClient(): ?TorrentClientInterface
    {
        $activeName = $this->settings->get('torrenting.client', 'qBittorrent 4.1+');

        return $this->getClient($activeName) ?? $this->getClient('qBittorrent 4.1+');
    }

    /**
     * Get all registered client names.
     */
    public function getAvailableClients(): array
    {
        return array_keys($this->clients);
    }
}
