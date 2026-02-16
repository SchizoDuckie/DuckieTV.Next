<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Display the about page content.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return view('about.index');
        }

        return view('layouts.app', [
            'title' => 'About',
            'view' => 'about.index'
        ]);
    }
}
