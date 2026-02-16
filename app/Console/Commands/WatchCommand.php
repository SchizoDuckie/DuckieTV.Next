<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Serie;
use Illuminate\Console\Command;

class WatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duckie:watch {query? : Search for a show or episode to mark watched}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark an episode as watched';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');

        if (!$query) {
            $query = $this->ask('Search for a show to mark an episode watched:');
        }

        $series = Serie::where('name', 'like', "%{$query}%")->get();

        if ($series->isEmpty()) {
            $this->error("No favorite shows found matching '{$query}'.");
            return 1;
        }

        if ($series->count() > 1) {
            $choice = $this->choice(
                'Select a show:',
                $series->pluck('name')->toArray()
            );
            $serie = $series->firstWhere('name', $choice);
        } else {
            $serie = $series->first();
        }

        $this->info("Selected: <fg=cyan>{$serie->name}</>");

        // Get unwatched episodes
        $episodes = Episode::where('serie_id', $serie->id)
            ->where('watched', 0)
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->orderBy('seasonnumber')
            ->orderBy('episodenumber')
            ->limit(20)
            ->get();

        if ($episodes->isEmpty()) {
            $this->warn("No unwatched (aired) episodes found for {$serie->name}.");
            return 0;
        }

        $epOptions = $episodes->mapWithKeys(function ($ep) {
            $label = sprintf("%s - %s", $ep->formatted_episode, $ep->episodename);
            return [$ep->id => $label];
        })->toArray();

        $selectedLabel = $this->choice(
            'Which episode did you watch?',
            array_values($epOptions)
        );

        $episodeId = array_search($selectedLabel, $epOptions);
        $episode = Episode::find($episodeId);

        $this->info("Marking {$episode->formatted_episode} as watched...");
        $episode->markWatched();

        $this->info("<fg=green;options=bold>Done!</>");

        return 0;
    }
}
