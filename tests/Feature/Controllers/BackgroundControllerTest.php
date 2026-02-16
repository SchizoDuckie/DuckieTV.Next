<?php

use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a random serie with fanart', function () {
    Serie::create([
        'name' => 'Breaking Bad',
        'trakt_id' => 1388,
        'fanart' => 'https://image.tmdb.org/t/p/original/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg',
        'firstaired' => 1200787200000, // 2008-01-20
    ]);

    $response = $this->getJson('/api/background/random');

    $response->assertOk()
        ->assertJsonStructure(['id', 'name', 'fanart', 'year'])
        ->assertJson([
            'name' => 'Breaking Bad',
            'fanart' => 'https://image.tmdb.org/t/p/original/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg',
        ]);
});

it('returns 404 when no series have fanart', function () {
    Serie::create([
        'name' => 'No Images Show',
        'trakt_id' => 999,
        'fanart' => null,
    ]);

    $response = $this->getJson('/api/background/random');

    $response->assertNotFound();
});

it('excludes series with empty fanart string', function () {
    Serie::create([
        'name' => 'Empty Fanart',
        'trakt_id' => 888,
        'fanart' => '',
    ]);

    $response = $this->getJson('/api/background/random');

    $response->assertNotFound();
});
