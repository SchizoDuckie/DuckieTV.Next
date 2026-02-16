<?php

namespace Tests\Feature\Controllers;

use App\Models\Episode;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_leaked_action_updates_episode()
    {
        $serie = Serie::create(['name' => 'Leak Show', 'trakt_id' => 999]);
        $episode = Episode::create([
            'serie_id' => $serie->id,
            'episodename' => 'Leak Ep',
            'trakt_id' => 888,
            'firstaired' => now()->addDays(7)->getTimestampMs(), // Future
            'leaked' => 0,
        ]);

        $response = $this->patch(route('episodes.update', $episode->id), [
            'action' => 'toggle_leaked',
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $episode->fresh()->leaked);

        // Toggle back
        $this->patch(route('episodes.update', $episode->id), [
            'action' => 'toggle_leaked',
        ]);

        $this->assertEquals(0, $episode->fresh()->leaked);
    }
}
