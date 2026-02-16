<?php

namespace App\Http\Controllers;

use App\Services\FavoritesService;

class BackgroundController extends Controller
{
    protected FavoritesService $favorites;

    public function __construct(FavoritesService $favorites)
    {
        $this->favorites = $favorites;
    }

    /**
     * Get a random favorite show with fanart.
     * Used by the background rotator in the frontend.
     */
    public function getRandom()
    {
        $serie = $this->favorites->getRandomBackground();

        if (! $serie) {
            return response()->json(['error' => 'No favorites with fanart found'], 404);
        }

        return response()->json([
            'id' => $serie->id,
            'name' => $serie->name,
            'fanart' => $serie->fanart,
            'year' => $serie->firstaired->year,
        ]);
    }
}
