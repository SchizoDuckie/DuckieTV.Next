<?php

namespace App\Services;

use App\Services\TorrentSearchEngines\SearchEngineInterface;
use App\Services\TorrentSearchEngines\GenericSearchEngine;
use Exception;

/**
 * Abstraction layer for the different torrent search engines that DuckieTV supports.
 * 
 * Search engines register themselves with this service. This service provides 
 * a central point for dispatching search queries to the appropriate engine.
 * 
 * @see TorrentSearchEngines.js in DuckieTV-angular for original implementation.
 */
class TorrentSearchService
{
    /** @var array<string, SearchEngineInterface> Registered search engines */
    protected array $engines = [];

    /** @var string|null The name of the default search engine */
    protected ?string $defaultEngineName = null;

    /** @var SettingsService */
    protected SettingsService $settings;

    /**
     * @param SettingsService $settings
     * @param iterable<SearchEngineInterface> $engines
     */
    public function __construct(SettingsService $settings, iterable $engines = [])
    {
        $this->settings = $settings;
        foreach ($engines as $engine) {
            $this->registerSearchEngine($engine->getName(), $engine);
        }
        $this->defaultEngineName = $settings->get('torrenting.searchprovider', 'ThePirateBay');
    }

    /**
     * Register a search engine instance.
     * 
     * @param string $name Unique name for the engine (e.g., '1337x')
     * @param SearchEngineInterface $engine The engine implementation
     * @return void
     */
    public function registerSearchEngine(string $name, SearchEngineInterface $engine): void
    {
        $engine->setName($name);
        $this->engines[$name] = $engine;
    }

    /**
     * Get all registered search engine instances.
     * 
     * @return array<string, SearchEngineInterface>
     */
    public function getSearchEngines(): array
    {
        return $this->engines;
    }

    /**
     * Get a search engine by name, falling back to the default if not found.
     * 
     * @param string $name
     * @return SearchEngineInterface
     * @throws Exception if neither the name nor the default engine is available
     */
    public function getSearchEngine(string $name): SearchEngineInterface
    {
        if (isset($this->engines[$name])) {
            return $this->engines[$name];
        }

        if ($this->defaultEngineName && isset($this->engines[$this->defaultEngineName])) {
            return $this->engines[$this->defaultEngineName];
        }

        throw new Exception("Search engine '{$name}' not found and no valid default available.");
    }

    /**
     * Get the currently configured default search engine.
     * 
     * @return SearchEngineInterface
     * @throws Exception if no default engine is configured or available
     */
    public function getDefaultEngine(): SearchEngineInterface
    {
        return $this->getSearchEngine($this->defaultEngineName);
    }

    /**
     * Perform a search across a specific engine or the default one.
     * 
     * @param string $query The search query
     * @param string|null $engineName Optional override for the engine to use
     * @param string|null $sortBy Optional sorting criteria
     * @return array Array of torrent results
     */
    public function search(string $query, ?string $engineName = null, ?string $sortBy = null): array
    {
        $engine = $this->getSearchEngine($engineName ?? $this->defaultEngineName);
        return $engine->search($query, $sortBy);
    }

    /**
     * Set the default search engine name.
     * 
     * @param string $name
     * @return void
     */
    public function setDefaultEngine(string $name): void
    {
        $this->defaultEngineName = $name;
    }
}
