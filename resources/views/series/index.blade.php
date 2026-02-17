@extends('layouts.app')

@php $title = 'Library'; @endphp

@section('content')
<div class="series-container" style="padding-top: 20px;">
    <div class="header" style="margin-left: 20px; margin-bottom: 20px;">
        <h1 style="font-family: 'bebasbold'; letter-spacing: 2px;">Your Library</h1>
    </div>

    <div class="series-grid" style="display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 20px; padding: 0 20px;">
        @foreach($series as $show)
            <div class="serieheader" 
                 style="width: 165px; cursor: pointer;" 
                 data-sidepanel-show="{{ route('series.show', $show->id) }}"
                 data-serie-id="{{ $show->id }}"
                 data-serie-name="{{ $show->name }}"
                 data-display-calendar="{{ $show->displaycalendar ? '1' : '0' }}"
            >
                <div class="poster" style="height: 236px; width: 165px; position: relative; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; background: #222;" title="{{ $show->name }}">
                    @if($show->poster)
                        <img src="{{ $show->poster }}" style="width: 100%; height: 100%; object-fit: cover;" alt="{{ $show->name }}">
                    @else
                        <div style="padding: 20px; text-align: center; color: #777;">
                            <i class="glyphicon glyphicon-picture" style="font-size: 3rem; margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 0.8rem;">{{ $show->name }}</span>
                        </div>
                    @endif
                    
                    <div class="poster-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 5px; transform: translateY(100%); transition: transform 0.2s;">
                         <div style="text-align: center; color: white; font-family: 'bebasregular'; font-size: 0.9rem;">
                             VIEW DETAILS
                         </div>
                    </div>
                </div>
                <div class="show-title" style="margin-top: 5px; text-align: center;">
                    <span style="font-family: 'bebasregular'; font-size: 1.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; color: #ccc;">{{ $show->name }}</span>
                </div>
            </div>
        @endforeach

        @if($series->isEmpty())
            <div style="text-align: center; width: 100%; padding: 100px; color: #555; font-family: 'bebasregular'; font-size: 2rem;">
                YOUR LIBRARY IS EMPTY. <br>
                <a href="{{ route('search.index') }}" style="color: #38bdf8; text-decoration: none;">SEARCH FOR SHOWS</a>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .serieheader:hover .poster-overlay {
        transform: translateY(0) !important;
    }
    .serieheader:hover .poster {
        border-color: #38bdf8;
    }
</style>
@endpush
@endsection
