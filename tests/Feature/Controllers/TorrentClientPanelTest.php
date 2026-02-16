<?php

namespace Tests\Feature\Controllers;

use App\Services\TorrentClientService;
use App\Services\TorrentClients\TorrentClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TorrentClientPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_renders_torrent_list()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([
            new \App\DTOs\TorrentData\TransmissionData([
                'infoHash' => 'abc123',
                'name' => 'Test Torrent',
                'progress' => 50,
                'status' => 4, // downloading
            ])
        ]);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->get(route('torrents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('torrents.index');
        $response->assertSee('Test Torrent');
        $response->assertSee('50%');
    }

    public function test_show_renders_torrent_details()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([
            new \App\DTOs\TorrentData\TransmissionData([
                'infoHash' => 'abc123',
                'name' => 'Test Torrent',
                'progress' => 50,
                'status' => 4, // downloading
                'downloadSpeed' => 102400, // 100 kB/s
                'files' => [['name' => 'file1.mkv']]
            ])
        ]);
        // Add this expectation:
        $mockClient->shouldReceive('getTorrentFiles')->with('abc123')->andReturn([['name' => 'file1.mkv']]);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->get(route('torrents.show', 'abc123'));

        $response->assertStatus(200);
        $response->assertViewIs('torrents.show');
        $response->assertSee('Test Torrent');
        $response->assertSee('102.4');
        $response->assertSee('kB/s');
        $response->assertSee('file1.mkv');
    }

    public function test_status_endpoint_returns_json_with_torrents()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([
            new \App\DTOs\TorrentData\TransmissionData([
                'infoHash' => 'abc123',
                'name' => 'Test Torrent',
                'progress' => 50,
                'status' => 4,
                'downloadSpeed' => 102400,
            ])
        ]);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->getJson(route('torrents.status'));

        $response->assertStatus(200);
        $response->assertJson([
            'connected' => true,
            'client' => 'MockClient',
            'active_count' => 1,
            'torrents' => [
                [
                    'infoHash' => 'abc123',
                    'name' => 'Test Torrent',
                    'progress' => 50.0,
                    'downloadSpeed' => 102400,
                    'status' => 4,
                ]
            ]
        ]);
    }

    public function test_index_renders_connecting_state()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andReturn(false);
        $mockClient->shouldReceive('isConnected')->andReturn(false);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->get(route('torrents.index'));

        $response->assertStatus(200);
        $response->assertSee('Connecting to MockClient');
        $response->assertSee('Please wait');
    }

    public function test_index_renders_empty_state_when_connected_but_no_torrents()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andReturn(true);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getTorrents')->andReturn([]);

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->get(route('torrents.index'));

        $response->assertStatus(200);
        $response->assertSee('Torrents found:');
    }

    public function test_status_endpoint_returns_error_when_connection_fails()
    {
        $mockClient = Mockery::mock(TorrentClientInterface::class);
        $mockClient->shouldReceive('getName')->andReturn('MockClient');
        $mockClient->shouldReceive('connect')->andThrow(new \Exception('Connection Timeout'));

        $mockService = Mockery::mock(TorrentClientService::class);
        $mockService->shouldReceive('getActiveClient')->andReturn($mockClient);
        $this->app->instance(TorrentClientService::class, $mockService);

        $response = $this->getJson(route('torrents.status'));

        $response->assertStatus(200);
        $response->assertJson([
            'connected' => false,
            'error' => 'Connection failed: Connection Timeout'
        ]);
    }
}

