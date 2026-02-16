<?php

namespace App\DTOs\TorrentData;

use App\Services\TorrentClientService;

/**
 * Base object for holding Torrent Data.
 * Individual clients extend this and implement the methods to adhere to the TorrentClient interface.
 */
abstract class TorrentData
{
    public array $files = [];

    /**
     * Properties that can be mass-assigned via update().
     */
    protected array $fillable = [];

    public function __construct(array $data = [])
    {
        $this->update($data);
    }

    /**
     * Get the active torrent client instance.
     */
    protected function getClient()
    {
        return app(TorrentClientService::class)->getActiveClient();
    }

    /**
     * Round a number with floor so that we don't lose precision on 99.7%.
     */
    public function round(float $x, int $n): float
    {
        return floor($x * pow(10, $n)) / pow(10, $n);
    }

    /**
     * Load a new batch of data into this object.
     * Only properties explicitly listed in $fillable can be set.
     * Values are cast to match the declared property type.
     */
    public function update(array $data): self
    {
        foreach ($data as $key => $value) {
            if (! in_array($key, $this->fillable, true)) {
                continue;
            }

            try {
                $type = (new \ReflectionProperty($this, $key))->getType();
            } catch (\ReflectionException) {
                continue;
            }

            $this->{$key} = $this->castValue($value, $type);
        }

        return $this;
    }

    /**
     * Cast a value to match the declared property type.
     */
    private function castValue(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($value === null || $type === null || ! $type instanceof \ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            default => $value,
        };
    }

    /**
     * Display name for torrent.
     */
    abstract public function getName(): string;

    /**
     * Progress percentage 0-100. Round to one digit to make sure that torrents are not stopped before 100%.
     */
    abstract public function getProgress(): float;

    /**
     * Send start command to the torrent client implementation for this torrent.
     */
    abstract public function start(): mixed;

    /**
     * Send stop command to the torrent client implementation for this torrent.
     */
    abstract public function stop(): mixed;

    /**
     * Send pause command to the torrent client implementation for this torrent.
     */
    abstract public function pause(): mixed;

    /**
     * Send remove command to the torrent client implementation for this torrent.
     */
    abstract public function remove(): mixed;

    /**
     * Send get files command to the torrent client implementation for this torrent.
     */
    abstract public function getFiles(): mixed;

    /**
     * Send isStarted query to the torrent client implementation for this torrent.
     */
    abstract public function isStarted(): bool;

    /**
     * Get torrent download speed in B/s.
     */
    abstract public function getDownloadSpeed(): int;

    /**
     * Get the download directory for this torrent.
     */
    abstract public function getDownloadDir(): ?string;

    public ?string $infoHash = null;

    public function getInfoHash()
    {
        return $this->infoHash;
    }

    /**
     * Check if the torrent is 100% downloaded.
     */
    public function isDownloaded(): bool
    {
        return $this->getProgress() == 100;
    }
}
