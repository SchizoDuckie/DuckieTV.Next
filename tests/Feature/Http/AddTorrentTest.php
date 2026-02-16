<?php

namespace Tests\Feature\Http;

use App\Models\User;
use App\Services\TorrentClientService;
use App\Services\TorrentClients\TorrentClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AddTorrentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @see \App\Http\Controllers\TorrentController::add
     */
    public function test_add_torrent_successfully()
    {
        $this->actingAs(User::factory()->create());
        
        // Mock the client service and active client
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getName')->andReturn('TestClient');
        $mockClient->shouldReceive('addTorrentByUrl')
            ->once()
            ->andReturn(true);
        
        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'url' => 'http://example.com/file.torrent',
            'infoHash' => str_repeat('a', 40), // Guarantee 40 chars
            'releaseName' => 'Test.Release',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
    public function test_add_torrent_with_nullable_infohash()
    {
        $this->actingAs(User::factory()->create());

        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getName')->andReturn('TestClient');
        $mockClient->shouldReceive('addTorrentByUrl')
            ->once()
            ->with('http://example.com/file.torrent', null, 'Test.Release', null, 'DuckieTV')
            ->andReturn(true);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);

        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'url' => 'http://example.com/file.torrent',
            'infoHash' => null, // Nullable
            'releaseName' => 'Test.Release',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_add_torrent_links_to_episode()
    {
        $this->actingAs(User::factory()->create());
        
        $serie = \App\Models\Serie::create(['name' => 'Test Series']);

        $episode = \App\Models\Episode::create([
            'serie_id' => $serie->id,
            'seasonnumber' => 1,
            'episodenumber' => 1,
            'episodename' => 'Test Episode',
            'firstaired' => now()->getTimestampMs(),
            'downloaded' => 0,
            'magnetHash' => null
        ]);

        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getName')->andReturn('TestClient');
        $mockClient->shouldReceive('addMagnet')->once()->andReturn(true);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        
        $this->app->instance(TorrentClientService::class, $mockService);

        $infoHash = str_repeat('b', 40);

        $response = $this->postJson(route('torrents.add'), [
            'magnet' => "magnet:?xt=urn:btih:$infoHash",
            'episode_id' => $episode->id,
            'infoHash' => $infoHash
        ]);

        $response->assertStatus(200);
        
        $episode->refresh();
        $this->assertEquals(1, $episode->downloaded);
        $this->assertEquals($infoHash, $episode->magnetHash);
    }

    public function test_add_torrent_magnet_updates_hash_without_explicit_infohash()
    {
        $this->actingAs(User::factory()->create());
        
        $serie = \App\Models\Serie::create(['name' => 'Test Series']);

        $episode = \App\Models\Episode::create([
            'serie_id' => $serie->id,
            'seasonnumber' => 1,
            'episodenumber' => 2,
            'episodename' => 'Test Episode 2',
            'firstaired' => now()->getTimestampMs(),
            'downloaded' => 0,
            'magnetHash' => null
        ]);

        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getName')->andReturn('TestClient');
        $mockClient->shouldReceive('addMagnet')->once()->andReturn(true);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        
        $this->app->instance(TorrentClientService::class, $mockService);

        $infoHash = str_repeat('c', 40);

        // Send ONLY magnet, NO infoHash
        $response = $this->postJson(route('torrents.add'), [
            'magnet' => "magnet:?xt=urn:btih:$infoHash&dn=Some+Release",
            'episode_id' => $episode->id,
            // 'infoHash' => null // purposefully omitted or null
        ]);

        $response->assertStatus(200);
        
        $episode->refresh();
        $this->assertEquals(1, $episode->downloaded);
        $this->assertEquals(strtoupper($infoHash), $episode->magnetHash);
    }
}
