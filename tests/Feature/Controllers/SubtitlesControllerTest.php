<?php

namespace Tests\Feature\Controllers;

use App\Models\Episode;
use App\Models\Serie;
use App\Services\SubtitlesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubtitlesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the SubtitlesService
        $this->subtitlesService = Mockery::mock(SubtitlesService::class);
        $this->app->instance(SubtitlesService::class, $this->subtitlesService);
    }

    public function test_search_by_episode_success()
    {
        $serie = Serie::create(['name' => 'The Show', 'trakt_id' => 111, 'imdb_id' => 'tt1234567']);
        $episode = Episode::create([
            'serie_id' => $serie->id,
            'episodename' => 'The Ep',
            'trakt_id' => 222,
            'seasonnumber' => 1,
            'episodenumber' => 1,
        ]);

        $mockResults = [
            ['attributes' => ['language' => 'en', 'release' => 'Rel1', 'ratings' => 5, 'url' => 'http://dl1']],
            ['attributes' => ['language' => 'en', 'release' => 'Rel2', 'ratings' => 4, 'url' => 'http://dl2']],
        ];

        $this->subtitlesService->shouldReceive('searchByEpisode')
            ->once()
            ->with(Mockery::on(fn($ep) => $ep->id === $episode->id), Mockery::any())
            ->andReturn($mockResults);

        $response = $this->post(route('subtitles.search'), [
            'episode_id' => $episode->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => $mockResults,
        ]);
    }

    public function test_search_by_episode_validation_failure()
    {
        $response = $this->post(route('subtitles.search'), [
            'episode_id' => 9999, // Non-existent
        ]);

        $response->assertStatus(302); // Redirects back on validation failure usually, or 422 if AJAX
        // Since we are using standard validation in Controller without AJAX check, it might redirect.
        // But our controller is intended for AJAX. Let's see how it behaves.
    }

    public function test_search_by_query_success()
    {
        $mockResults = [
            ['attributes' => ['language' => 'en', 'release' => 'QueryRel', 'ratings' => 5, 'url' => 'http://dlq']],
        ];

        $this->subtitlesService->shouldReceive('searchByQuery')
            ->once()
            ->with('The Show', Mockery::any())
            ->andReturn($mockResults);

        $response = $this->post(route('subtitles.search-query'), [
            'query' => 'The Show',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => $mockResults,
        ]);
    }

    public function test_search_by_query_validation_failure()
    {
        $response = $this->post(route('subtitles.search-query'), [
            'query' => 'ab', // Too short
        ]);

        $response->assertStatus(302);
    }
}
