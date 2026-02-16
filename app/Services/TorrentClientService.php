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

    /** @var SettingsService */
    protected SettingsService $settings;

    /**
     * @param SettingsService $settings
     * @param iterable<TorrentClientInterface> $clients
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
     * 
     * @param string $name
     * @param TorrentClientInterface $client
     * @return void
     */
    public function registerClient(string $name, TorrentClientInterface $client): void
    {
        $this->clients[$name] = $client;
    }

    /**
     * Get a registered client by name.
     * 
     * @param string $name
     * @return TorrentClientInterface|null
     */
    public function getClient(string $name): ?TorrentClientInterface
    {
        return $this->clients[$name] ?? null;
    }

    /**
     * Get the currently active client based on settings.
     * 
     * @return TorrentClientInterface|null
     */
    public function getActiveClient(): ?TorrentClientInterface
    {
        $activeName = $this->settings->get('torrenting.client', 'qBittorrent 4.1+');
        return $this->getClient($activeName) ?? $this->getClient('qBittorrent 4.1+');
    }

    /**
     * Get all registered client names.
     * 
     * @return array
     */
    public function getAvailableClients(): array
    {
        return array_keys($this->clients);
    }
}
