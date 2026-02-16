<?php

namespace App\Console\Commands;

use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalendarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duckie:calendar {date? : The date to show (YYYY-MM-DD or relative like "next week")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display a textual calendar of episodes';

    protected CalendarService $calendar;

    public function __construct(CalendarService $calendar)
    {
        parent::__construct();
        $this->calendar = $calendar;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();

        // Show current week by default
        $start = $date->copy()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $this->header('Calendar for '.$start->format('M d').' - '.$end->format('M d, Y'));

        $events = $this->calendar->getEventsForDateRange($start, $end);

        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $dateStr = $day->toDateString();
            $dayEvents = $events[$dateStr] ?? [];

            $this->line("<fg=yellow;options=bold>{$day->format('l, M j')}</>");

            if (empty($dayEvents)) {
                $this->line('  <fg=gray>No episodes airing today.</>');
            } else {
                foreach ($dayEvents as $event) {
                    $ep = $event['episode'];
                    $serie = $event['serie'];

                    $status = '';
                    if ($ep->watched) {
                        $status = ' <fg=green>[WATCHED]</>';
                    } elseif ($ep->downloaded) {
                        $status = ' <fg=blue>[DL]</>';
                    }

                    $this->line(sprintf(
                        '  <fg=cyan>%s</> - <fg=white>%s</> (%s)%s',
                        $ep->getAirTime(),
                        $serie->name,
                        $ep->formatted_episode,
                        $status
                    ));
                }
            }
            $this->line('');
        }

        return 0;
    }

    protected function header($text)
    {
        $this->line('');
        $this->line('<bg=blue;fg=white;options=bold> '.str_pad($text, 50).' </>');
        $this->line('');
    }
}
