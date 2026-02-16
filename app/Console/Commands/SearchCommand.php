<?php

namespace App\Console\Commands;

use App\Services\FavoritesService;
use App\Services\TraktService;
use Illuminate\Console\Command;

class SearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duckie:search {query? : The show to search for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for TV shows on Trakt and add to favorites';

    protected TraktService $trakt;

    protected FavoritesService $favorites;

    public function __construct(TraktService $trakt, FavoritesService $favorites)
    {
        parent::__construct();
        $this->trakt = $trakt;
        $this->favorites = $favorites;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');

        if (! $query) {
            $query = $this->ask('What show are you looking for?');
        }

        $this->info("Searching Trakt for '{$query}'...");
        $results = $this->trakt->search($query);

        if (empty($results)) {
            $this->error('No shows found.');

            return 1;
        }

        $options = [];
        foreach ($results as $show) {
            $year = $show['year'] ?? '????';
            $options[$show['trakt_id']] = "{$show['name']} ({$year}) - {$show['status']}";
        }

        $selectedName = $this->choice(
            'Which show would you like to add?',
            array_values($options)
        );

        $traktId = array_search($selectedName, $options);

        $this->info("Adding '{$selectedName}' to favorites...");
        try {
            $data = $this->trakt->serie($traktId);
            $serie = $this->favorites->addFavorite($data);
            $this->info("Successfully added <fg=green;options=bold>{$serie->name}</>!");
        } catch (\Exception $e) {
            $this->error('Failed to add show: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
