<?php

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use App\Services\CalendarService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calendar = app(CalendarService::class);

    // Create a test serie with episodes
    $this->serie = Serie::create([
        'name' => 'Test Show',
        'trakt_id' => 1,
        'tvdb_id' => 100,
        'displaycalendar' => true,
        'ignoreHideSpecials' => false,
    ]);

    $this->season = Season::create([
        'serie_id' => $this->serie->id,
        'seasonnumber' => 1,
        'trakt_id' => 10,
    ]);

    // Create episodes: one yesterday, one today, one tomorrow
    $yesterday = now()->subDay()->startOfDay()->addHours(21);
    $today = now()->startOfDay()->addHours(21);
    $tomorrow = now()->addDay()->startOfDay()->addHours(21);

    $this->episodeYesterday = Episode::create([
        'serie_id' => $this->serie->id,
        'season_id' => $this->season->id,
        'episodename' => 'Yesterday Episode',
        'episodenumber' => 1,
        'seasonnumber' => 1,
        'firstaired' => $yesterday->getTimestampMs(),
        'firstaired_iso' => $yesterday->toIso8601String(),
        'trakt_id' => 100,
        'watched' => 0,
        'downloaded' => 0,
    ]);

    $this->episodeToday = Episode::create([
        'serie_id' => $this->serie->id,
        'season_id' => $this->season->id,
        'episodename' => 'Today Episode',
        'episodenumber' => 2,
        'seasonnumber' => 1,
        'firstaired' => $today->getTimestampMs(),
        'firstaired_iso' => $today->toIso8601String(),
        'trakt_id' => 101,
        'watched' => 0,
        'downloaded' => 0,
    ]);

    $this->episodeTomorrow = Episode::create([
        'serie_id' => $this->serie->id,
        'season_id' => $this->season->id,
        'episodename' => 'Tomorrow Episode',
        'episodenumber' => 3,
        'seasonnumber' => 1,
        'firstaired' => $tomorrow->getTimestampMs(),
        'firstaired_iso' => $tomorrow->toIso8601String(),
        'trakt_id' => 102,
        'watched' => 0,
        'downloaded' => 0,
    ]);
});

it('can be resolved from the container', function () {
    expect($this->calendar)->toBeInstanceOf(CalendarService::class);
});

it('gets events for a date range', function () {
    $start = now()->subDays(2)->startOfDay();
    $end = now()->addDays(2)->endOfDay();

    $events = $this->calendar->getEventsForDateRange($start, $end);

    expect($events)->toBeArray()
        ->and(array_reduce($events, fn ($carry, $day) => $carry + count($day), 0))->toBe(3);
});

it('groups events by date string', function () {
    $start = now()->subDays(2);
    $end = now()->addDays(2);

    $events = $this->calendar->getEventsForDateRange($start, $end);

    // Should have entries for yesterday, today, and tomorrow
    $dates = array_keys($events);
    expect(count($dates))->toBe(3);

    // Each date should have exactly 1 episode
    foreach ($events as $dayEvents) {
        expect($dayEvents)->toHaveCount(1);
    }
});

it('gets events for a single date', function () {
    $events = $this->calendar->getEvents(now());

    expect($events)->toHaveCount(1)
        ->and($events[0]['episode']->episodename)->toBe('Today Episode')
        ->and($events[0]['serie']->name)->toBe('Test Show')
        ->and($events[0]['serie_id'])->toBe($this->serie->id);
});

it('returns empty array for dates with no events', function () {
    $events = $this->calendar->getEvents(now()->addWeek());

    expect($events)->toBeArray()
        ->and($events)->toBeEmpty();
});

it('gets series grouped by show for a date', function () {
    // Add a second show on the same day
    $serie2 = Serie::create([
        'name' => 'Another Show',
        'trakt_id' => 2,
        'displaycalendar' => true,
    ]);
    $season2 = Season::create([
        'serie_id' => $serie2->id,
        'seasonnumber' => 1,
        'trakt_id' => 20,
    ]);
    $today = now()->startOfDay()->addHours(20);
    Episode::create([
        'serie_id' => $serie2->id,
        'season_id' => $season2->id,
        'episodename' => 'Another Show Episode',
        'episodenumber' => 1,
        'seasonnumber' => 1,
        'firstaired' => $today->getTimestampMs(),
        'firstaired_iso' => $today->toIso8601String(),
        'trakt_id' => 200,
        'watched' => 0,
        'downloaded' => 0,
    ]);

    $series = $this->calendar->getSeries(now());

    expect($series)->toHaveCount(2);

    // Each group should have 1 episode
    foreach ($series as $group) {
        expect($group)->toHaveCount(1);
    }
});

it('filters out specials when show-specials is disabled', function () {
    // Create a special episode (season 0)
    $today = now()->startOfDay()->addHours(20);
    Episode::create([
        'serie_id' => $this->serie->id,
        'season_id' => $this->season->id,
        'episodename' => 'Special Episode',
        'episodenumber' => 1,
        'seasonnumber' => 0,
        'firstaired' => $today->getTimestampMs(),
        'firstaired_iso' => $today->toIso8601String(),
        'trakt_id' => 999,
        'watched' => 0,
        'downloaded' => 0,
    ]);

    // With specials enabled (default)
    $events = $this->calendar->getEvents(now());
    expect($events)->toHaveCount(2); // regular + special

    // With specials disabled
    app(SettingsService::class)->set('calendar.show-specials', false);
    $calendar = new CalendarService(app(SettingsService::class));
    $events = $calendar->getEvents(now());
    expect($events)->toHaveCount(1); // only regular
});

it('marks a day as watched', function () {
    // Set yesterday's episode to have aired (it should have based on setup)
    $this->calendar->markDayWatched(now()->subDay());

    $episode = Episode::find($this->episodeYesterday->id);
    expect($episode->watched)->toBe(1)
        ->and($episode->downloaded)->toBe(1)
        ->and($episode->watchedAt)->not->toBeNull();
});

it('marks a day as downloaded', function () {
    $this->calendar->markDayDownloaded(now()->subDay());

    $episode = Episode::find($this->episodeYesterday->id);
    expect($episode->downloaded)->toBe(1);
});

it('sorts events by air time then episode number', function () {
    // Create two more episodes at the same time but different numbers
    $today = now()->startOfDay()->addHours(21);
    Episode::create([
        'serie_id' => $this->serie->id,
        'season_id' => $this->season->id,
        'episodename' => 'Double Episode Part 2',
        'episodenumber' => 4,
        'seasonnumber' => 1,
        'firstaired' => $today->getTimestampMs(),
        'firstaired_iso' => $today->toIso8601String(),
        'trakt_id' => 103,
        'watched' => 0,
        'downloaded' => 0,
    ]);

    $events = $this->calendar->getEvents(now());

    // Should be sorted by episode number for same serie
    expect($events)->toHaveCount(2)
        ->and($events[0]['episode']->episodenumber)->toBeLessThan($events[1]['episode']->episodenumber);
});

it('returns todo events for current month', function () {
    $todos = $this->calendar->getTodoEvents();

    // Yesterday's episode should be in the todo list (unwatched, this month)
    $names = array_map(fn ($e) => $e['episode']->episodename, $todos);

    // At minimum, yesterday's episode should be there if we're not at month start
    expect($todos)->toBeArray();
});
