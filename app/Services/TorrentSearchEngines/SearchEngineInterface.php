<?php

namespace App\Services\TorrentSearchEngines;

/**
 * Abstraction layer for torrent search engines.
 * 
 * Each search engine implementation provides a way to search for torrents
 * and retrieve additional details if necessary.
 */
interface SearchEngineInterface
{
    /**
     * Search for torrents using the given query and optional sorting.
     *
     * @param string $query The search query (e.g., show name + s01e01)
     * @param string|null $sortBy Sorting criteria in 'property.direction' format (e.g., 'seeders.d')
     * @return array Array of structured torrent results
     * @throws \Exception if the search request fails
     */
    public function search(string $query, ?string $sortBy = null): array;

    /**
     * Get details for a specific torrent result from a details page.
     * 
     * Required when magnet/torrent links are not available on the search results page.
     *
     * @param string $url The URL of the details page
     * @param string $releaseName The name of the release for fallback torrent URL building
     * @return array Array containing 'magnetUrl' and/or 'torrentUrl'
     * @throws \Exception if the details request fails
     */
    public function getDetails(string $url, string $releaseName): array;

    /**
     * Get the search engine configuration.
     *
     * @return array The configuration array including mirrors, selectors, and endpoints
     */
    public function getConfig(): array;

    public function setName(string $name): void;

    /**
     * Get the engine name.
     * 
     * @return string
     */
    public function getName(): string;
}
