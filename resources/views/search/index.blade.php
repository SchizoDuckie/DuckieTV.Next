@extends('layouts.app')

@php 
    $title = isset($query) ? __('SERIESLIST/TRAKT-SEARCHING/please-wait/lbl') : __('COMMON/addtrending/hdr');
    // Extract unique genres and statuses for the filter list if possible, or hardcode common ones for parity if dynamic extraction is too heavy here. 
    // For now, I'll assume we can pass them or just hardcode the common Trakt ones since we want layout parity first.
    // Actually, TraktTvTrending.js fetches these. I will hardcode the standard list for now or use what's available.
    $genres = ['action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 'drama', 'family', 'fantasy', 'history', 'horror', 'music', 'mystery', 'reality', 'romance', 'scifi', 'sport', 'suspense', 'thriller', 'war', 'western'];
    $statuses = ['returning series', 'in production', 'planned', 'canceled', 'ended'];
@endphp

@section('content')
<series-list>
    <div class="series-list-container active">
        
        <!-- Search Tools -->
        <div class="tools">
            <div class="search-box">
                <form action="{{ route('search.query') }}" method="GET" class="search-form">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
                        <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="{{ __('SERIESLIST/TOOLS/ADDING/addshow-type-series-name/placeholder') }}" 
                               class="form-control" autofocus>
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">{{ __('COMMON/search/btn') }}</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <div class="series adding">
            @if(!isset($query))
                <h1 style="margin-bottom:15px; margin-top:15px; color:rgb(225,225,225);">
                    {{ __('COMMON/addtrending/hdr') }} - {{ __('SERIESLIST/TRAKT-TRENDING/addtrending-help-click-to-show/hdr') }}
                </h1>

                <!-- Filters -->
                <div class="filters" style="margin-bottom: 20px;">
                    <!-- Genres -->
                    <div style="margin-bottom: 10px;">
                        <h3 style="margin-top:0">{{ __('COMMON/genre/hdr') }}</h3>
                        <div>
                            <button type="button" class="btn btn-xs btn-default filter-btn genre-btn active" data-filter="all">{{ __('COMMON/all/btn') }}</button>
                            @foreach($genres as $genre)
                                <button type="button" class="btn btn-xs btn-default filter-btn genre-btn" data-filter="{{ $genre }}">{{ ucfirst($genre) }}</button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Statuses -->
                    <div style="width: 200px; margin-left: 10px; display:inline-block; vertical-align:top;">
                         <h3 style="margin-top:0">{{ __('COMMON/status/hdr') }}</h3>
                         <div style="display: flex; flex-flow: column;">
                            <button type="button" class="btn btn-xs btn-default filter-btn status-btn active" data-filter="all" style="width:100%; text-align:left; margin-bottom:2px">{{ __('COMMON/all/btn') }}</button>
                            @foreach($statuses as $status)
                                <button type="button" class="btn btn-xs btn-default filter-btn status-btn" data-filter="{{ $status }}" style="width:100%; text-align:left; margin-bottom:2px">{{ ucfirst($status) }}</button>
                            @endforeach
                         </div>
                    </div>
                </div>

            @elseif(count($results) === 0)
                <div class="no-results">
                    <img src="{{ asset('img/trakt.png') }}" style="display:block; margin: 0 auto;">
                    <h3>{{ __('SERIESLIST/TRAKT-SEARCHING/no-results/lbl') }} {{ $query }}</h3>
                </div>
            @endif

            <!-- Series Grid -->
            <div class="series-grid" id="trending-grid" style="position:relative;width:100%;margin-top:10px">
                @foreach($results as $show)
                    <!-- SerieHeader Structure matching serieHeader.html -->
                    <div class="serieheader item" 
                         data-trakt-id="{{ $show['trakt_id'] }}"
                         data-genre="{{ implode(' ', $show['genres'] ?? []) }}"
                         data-status="{{ strtolower($show['status'] ?? '') }}">
                        
                        <!-- Poster -->
                        <a class="poster" href="#">
                            <figure>
                                <div class="img" style="{{ isset($show['poster']) ? 'background-image: url(\'' . $show['poster'] . '\');' : '' }}"></div>
                                <figcaption>
                                    <h3 class="title">{{ $show['name'] }}</h3>
                                </figcaption>
                            </figure>
                        </a>

                        <!-- Earmarks (Transcluded Content) -->
                        @if(in_array($show['trakt_id'], $favoriteIds))
                            <em class="earmark"><i class="glyphicon glyphicon-ok"></i></em>
                        @else
                            <form action="{{ route('search.add') }}" method="POST" class="add-favorite-form" style="display:none;">
                                @csrf
                                <input type="hidden" name="trakt_id" value="{{ $show['trakt_id'] }}">
                            </form>
                            <em class="earmark add" onclick="this.previousElementSibling.submit()">
                                <i class="glyphicon glyphicon-plus"></i>
                            </em>
                        @endif

                        <em class="earmark trailer">
                            @if(isset($show['trailer']) && $show['trailer'])
                                <a href="{{ $show['trailer'] }}" target="_blank" title="{{ __('COMMON/watch-trailer/tooltip') }}"><i class="glyphicon glyphicon-facetime-video"></i></a>
                            @else
                                <a href="https://www.youtube.com/results?search_query={{ urlencode($show['name']) }}+official+trailer" target="_blank" title="{{ __('COMMON/watch-trailer/tooltip') }}"><i class="glyphicon glyphicon-facetime-video"></i></a>
                            @endif
                        </em>
                    </div>
                @endforeach
            </div>

            <!-- Show More Button -->
            <button id="show-more-btn" class="btn btn-info" style="margin:5px auto;width:50%;opacity:0.8; display:none;">
                <i class="glyphicon glyphicon-plus"></i> {{ __('SERIESLIST/TRAKT-TRENDING/show-more/btn') }}
            </button>
        </div>
    </div>
</series-list>
@endsection

@push('styles')
<style>
    .series-list-container {
        position: fixed; top: 0; left: 58px; right: 0; bottom: 0;
        background: #141618; z-index: 1000;
    }
    .tools .search-box { max-width: 600px; margin: 0 auto; }
    .series.adding {
        overflow-y: auto; bottom: 12px; top: 70px; position: absolute;
        right: 20px; left: 58px; text-align: center;
        padding: 10px 25px 15px 25px; margin-top: 6px;
    }
    .filters { display: flex; justify-content: center; flex-wrap: wrap; }
    .filters .btn { margin: 2px; }
    .filters .btn.active { background-color: #337ab7; border-color: #2e6da4; color: white; }
    
    /* Grid Layout */
    .series-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    /* SerieHeader Styles matching Angular/Less */
    .serieheader {
        position: relative;
        display: inline-block;
        margin: 10px;
        width: 154px; /* Standard poster width */
        vertical-align: top;
    }
    
    .serieheader .poster {
        display: block;
        width: 100%;
        height: 231px; /* Standard poster height */
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        transition: transform 0.2s;
    }
    
    .serieheader .poster:hover {
        transform: scale(1.05);
        z-index: 10;
    }

    .serieheader figure { margin: 0; height: 100%; width: 100%; position: relative; }
    .serieheader .img {
        width: 100%; height: 100%;
        background-size: cover; background-position: center;
        background-color: #222;
    }
    
    .serieheader figcaption {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.8);
        padding: 5px;
        display: none; /* Only show on specific conditions or if image fails? Angular hides it usually unless no-title is false */
    }
    
    /* Earmarks - The buttons overlaying the poster */
    .serieheader .earmark {
        position: absolute;
        width: 30px; height: 30px;
        line-height: 30px;
        text-align: center;
        background: rgba(0,0,0,0.8);
        color: white;
        font-size: 16px;
        cursor: pointer;
        opacity: 0; /* Hidden by default, shown on hover */
        transition: opacity 0.2s;
        z-index: 20;
    }

    .serieheader:hover .earmark { opacity: 1; }

    .serieheader .earmark.add { top: 5px; right: 5px; background: rgba(0,100,0,0.8); }
    .serieheader .earmark.trailer { bottom: 5px; left: 5px; background: rgba(200,0,0,0.8); }
    .serieheader .earmark .glyphicon-ok { color: #5cb85c; }
    
    /* Override for already collected */
    .serieheader .earmark:has(.glyphicon-ok) { opacity: 1; background: transparent; top: 5px; right: 5px; text-shadow: 0 0 5px black; }

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('trending-grid');
    const items = Array.from(grid.getElementsByClassName('item'));
    const limit = 75; // Initial limit
    let currentLimit = limit;
    
    // Filtering State
    let activeGenre = 'all';
    let activeStatus = 'all';

    function filterItems() {
        let visibleCount = 0;
        let hiddenCount = 0;

        items.forEach(item => {
            const genres = item.getAttribute('data-genre').split(' ');
            const status = item.getAttribute('data-status');
            
            const genreMatch = activeGenre === 'all' || genres.includes(activeGenre);
            const statusMatch = activeStatus === 'all' || status === activeStatus;

            if (genreMatch && statusMatch) {
                if (visibleCount < currentLimit) {
                    item.style.display = 'inline-block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    hiddenCount++;
                }
            } else {
                item.style.display = 'none';
            }
        });

        const showMoreBtn = document.getElementById('show-more-btn');
        if (hiddenCount > 0) {
            showMoreBtn.style.display = 'block';
        } else {
            showMoreBtn.style.display = 'none';
        }
    }

    // Genre Buttons
    document.querySelectorAll('.genre-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.genre-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeGenre = this.getAttribute('data-filter');
            currentLimit = limit; // Reset limit on filter change
            filterItems();
        });
    });

    // Status Buttons
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeStatus = this.getAttribute('data-filter');
            currentLimit = limit; // Reset limit on filter change
            filterItems();
        });
    });

    // Show More
    document.getElementById('show-more-btn').addEventListener('click', function() {
        currentLimit += limit;
        filterItems();
    });

    // Initial Filter
    filterItems();
});
</script>
@endpush

