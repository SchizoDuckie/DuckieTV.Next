<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WatchlistService;

class WatchlistController extends Controller
{
    /**
     * Display the watchlist.
     */
    public function index(Request $request, WatchlistService $watchlistService)
    {
        $items = $watchlistService->getTop10Movies();
        
        if ($request->ajax()) {
            return view('watchlist.index', ['items' => $items]);
        }

        return view('layouts.app', [
            'title' => 'Watchlist',
            'view' => 'watchlist.index',
            'items' => $items
        ]);
    }
}
