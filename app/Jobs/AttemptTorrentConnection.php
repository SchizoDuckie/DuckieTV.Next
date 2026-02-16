<?php

namespace App\Jobs;

use App\Events\TorrentConnectionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttemptTorrentConnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $client,
        protected array $config
    ) {}

    public function handle(): void
    {
        // Broadcast connecting status
        TorrentConnectionStatus::dispatch('connecting', $this->client, "Attempting to connect to {$this->client}...");

        try {
            $host = $this->config['torrenting.host'] ?? 'localhost';
            $port = $this->config['torrenting.port'] ?? 'unknown';

            // Simulate connection delay for UI demonstration
            sleep(1);

            // Fail if no host or port provided
            if (empty($this->config['torrenting.host']) || empty($this->config['torrenting.port'])) {
                throw new \Exception("Host and Port are required.");
            }

            // Placeholder logic: fail if port is 9999
            if ($port == 9999) {
                 throw new \Exception("Connection refused to $host:$port (Simulated)");
            }

            TorrentConnectionStatus::dispatch('connected', $this->client, "Successfully connected to {$this->client} at $host:$port!");
            
        } catch (\Throwable $e) {
            TorrentConnectionStatus::dispatch('failed', $this->client, $e->getMessage());
        }
    }
}
