<?php

namespace Tests\Feature\Controllers;

use App\Services\TorrentClients\TorrentClientInterface;
use App\Services\TorrentClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TorrentAddTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_add_endpoint_successfully_adds_magnet()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('addMagnet')
            ->with('magnet:?xt=urn:btih:abc', null, 'DuckieTV')
            ->andReturn(true);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'magnet' => 'magnet:?xt=urn:btih:abc',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Torrent added successfully',
        ]);
    }

    public function test_add_endpoint_successfully_adds_torrent_url()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('addTorrentByUrl')
            ->with('http://example.com/file.torrent', '1234567890123456789012345678901234567890', 'Test.Release', null, 'DuckieTV')
            ->andReturn(true);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'url' => 'http://example.com/file.torrent',
            'infoHash' => '1234567890123456789012345678901234567890',
            'releaseName' => 'Test.Release',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_add_endpoint_returns_error_when_no_client_configured()
    {
        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn(null);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'magnet' => 'magnet:?xt=urn:btih:abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'No torrent client configured']);
    }

    public function test_add_endpoint_returns_error_when_connection_fails()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('connect')->andReturn(false);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->postJson(route('torrents.add'), [
            'magnet' => 'magnet:?xt=urn:btih:abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Could not connect to torrent client']);
    }

    public function test_add_endpoint_validates_required_fields_for_url()
    {
        $response = $this->postJson(route('torrents.add'), [
            'url' => 'http://example.com/file.torrent',
            // infohash and releasename are intentionally missing
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['releaseName']);
    }

    public function test_add_endpoint_validates_magnet_prefix()
    {
        $response = $this->postJson(route('torrents.add'), [
            'magnet' => 'not-a-magnet',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['magnet']);
    }
}
