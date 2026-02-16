@extends('layouts.app')

@php $title = 'Search Shows'; @endphp

@section('content')
<div class="search-container" style="max-width: 1000px; margin: 0 auto; padding-top: 20px;">
    <div class="search-box" style="margin-bottom: 30px; text-align: center;">
        <h1 style="font-family: 'bebasbold'; letter-spacing: 2px;">Find New Shows</h1>
        <form action="{{ route('search.query') }}" method="GET" class="search-form" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Search Trakt..." 
                   class="form-control" style="max-width: 500px; font-style: normal; border-radius: 5px;" autofocus>
            <button type="submit" class="btn btn-primary" style="height: 40px; border-radius: 5px; font-family: 'bebasregular'; font-size: 1.2rem; letter-spacing: 1px;">SEARCH</button>
        </form>
    </div>

    @if(isset($results))
        <div class="series-grid" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px;">
            @foreach($results as $show)
                <div class="serieheader" style="width: 165px; cursor: pointer;">
                    <div class="poster" style="height: 236px; width: 165px; position: relative; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; background: #222;" title="{{ $show['name'] }}">
                        @if(isset($show['poster']))
                            <img src="{{ $show['poster'] }}" style="width: 100%; height: 100%; object-fit: cover;" alt="{{ $show['name'] }}">
                        @else
                            <div style="padding: 20px; text-align: center; color: #777;">
                                <i class="glyphicon glyphicon-picture" style="font-size: 3rem; margin-bottom: 10px; display: block;"></i>
                                <span style="font-size: 0.8rem;">{{ $show['name'] }}</span>
                            </div>
                        @endif
                        
                        <div class="poster-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 5px; transform: translateY(100%); transition: transform 0.2s;">
                           <form action="{{ route('search.add') }}" method="POST" style="margin:0">
                                @csrf
                                <input type="hidden" name="trakt_id" value="{{ $show['trakt_id'] }}">
                                <button type="submit" class="btn btn-xs btn-success" style="width: 100%;">ADD TO FAVORITES</button>
                            </form>
                        </div>
                    </div>
                    <div class="show-title" style="margin-top: 5px; text-align: center;">
                        <span style="font-family: 'bebasregular'; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; color: #ccc;">{{ $show['name'] }}</span>
                        @if(isset($show['year'])) <span style="font-size: 0.7rem; color: #666;">({{ $show['year'] }})</span> @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($results) === 0)
            <div class="no-results" style="text-align: center; padding: 50px; color: #777; font-family: 'bebasregular'; font-size: 1.5rem;">
                NO SHOWS FOUND MATCHING "{{ strtoupper($query) }}"
            </div>
        @endif
    @endif
</div>

@push('styles')
<style>
    .serieheader:hover .poster-overlay {
        transform: translateY(0) !important;
    }
    .serieheader:hover .poster {
        border-color: #5cb85c;
    }
    .btn-success {
        background-color: #5cb85c;
        border-color: #4cae4c;
        font-family: 'bebasregular';
        letter-spacing: 1px;
    }
</style>
@endpush
@endsection
