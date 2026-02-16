<?php

namespace Tests\Feature\Controllers;

use App\Models\Episode;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use App\DTOs\TorrentData\TorrentData;
use App\Services\TorrentClients\TorrentClientInterface;
use App\Services\TorrentClientService;
use App\Services\SceneNameResolverService;

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

    public function test_show_matches_torrent_by_info_hash()
    {
        $serie = Serie::create(['name' => 'The Show', 'trakt_id' => 111]);
        $episode = Episode::create([
            'serie_id' => $serie->id,
            'episodename' => 'The Ep',
            'trakt_id' => 222,
            'magnetHash' => 'hash123',
        ]);

        $mockTorrent = Mockery::mock(TorrentData::class);
        $mockTorrent->shouldReceive('getInfoHash')->andReturn('hash123');
        $mockTorrent->shouldReceive('getName')->andReturn('The Show S01E01');
        $mockTorrent->shouldReceive('getProgress')->andReturn(50.0);
        $mockTorrent->shouldReceive('getFiles')->andReturn([]);
        $mockTorrent->shouldReceive('isStarted')->andReturn(true);
        $mockTorrent->shouldReceive('isDownloaded')->andReturn(false);

        $mockClient = Mockery::mock(\App\Services\TorrentClients\TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([$mockTorrent]);

        $mockService = Mockery::mock(\App\Services\TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(\App\Services\TorrentClientService::class, $mockService);

        $response = $this->get(route('episodes.show', $episode->id));

        $response->assertStatus(200);
        $response->assertViewHas('torrent', $mockTorrent);
    }

    public function test_show_matches_torrent_by_name_fallback()
    {
        $serie = Serie::create(['name' => 'The Show', 'trakt_id' => 111]);
        $episode = Episode::create([
            'serie_id' => $serie->id,
            'episodename' => 'The Ep',
            'trakt_id' => 222,
            'seasonnumber' => 1,
            'episodenumber' => 1,
            // no magnetHash
        ]);

        $mockTorrent = Mockery::mock(TorrentData::class);
        $mockTorrent->shouldReceive('getName')->andReturn('The.Show.S01E01.1080p.WebDL');

        $mockClient = Mockery::mock(\App\Services\TorrentClients\TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([$mockTorrent]);

        $mockService = Mockery::mock(\App\Services\TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(\App\Services\TorrentClientService::class, $mockService);

        $response = $this->get(route('episodes.show', $episode->id));

        $response->assertStatus(200);
        $response->assertViewHas('torrent', $mockTorrent);
    }

    public function test_show_returns_null_torrent_when_no_match()
    {
        $serie = Serie::create(['name' => 'The Show', 'trakt_id' => 111]);
        $episode = Episode::create([
            'serie_id' => $serie->id,
            'episodename' => 'The Ep',
            'trakt_id' => 222,
            'seasonnumber' => 1,
            'episodenumber' => 1,
        ]);

        $mockTorrent = Mockery::mock(TorrentData::class);
        $mockTorrent->shouldReceive('getName')->andReturn('Something.Else.S05E05');

        $mockClient = Mockery::mock(\App\Services\TorrentClients\TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([$mockTorrent]);

        $mockService = Mockery::mock(\App\Services\TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(\App\Services\TorrentClientService::class, $mockService);

        $response = $this->get(route('episodes.show', $episode->id));

        $response->assertStatus(200);
        $response->assertViewHas('torrent', null);
    }
}
