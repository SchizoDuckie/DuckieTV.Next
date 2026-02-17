@extends('layouts.app')

@php 
    $title = isset($query) ? __('SERIESLIST/TRAKT-SEARCHING/please-wait/lbl') : __('COMMON/addtrending/hdr');
    $metaTranslator = app(\App\Services\SeriesMetaTranslations::class);
    // Use original DuckieTV lists for Trending filters parity
    $genres = \App\Services\SeriesMetaTranslations::GENRES;
    $statuses = \App\Services\SeriesMetaTranslations::STATUSES;
@endphp

@section('content')
<series-list class="active">
    <div class="series-list active">
        <div>
            <div class="tools">
                <div class="filtertools">
                    <form action="{{ route('search.query') }}" method="GET">
                        <div class="row">
                            <div class="col col-md-8">
                                <div class="input-group pull-left">
                                    <span class="input-group-addon">
                                        <i style='font-size:15px;' class="glyphicon glyphicon-search"></i>
                                    </span>
                                    <input type="text" name="q" value="{{ $query ?? '' }}" 
                                           style='width:80%;' 
                                           placeholder="{{ __('SERIESLIST/TOOLS/ADDING/addshow-type-series-name/placeholder') }}"
                                           autofocus>
                                </div>
                            </div>
                            <div class="col col-md-2 pull-right" style="text-align:right">
                                <a onclick="document.querySelector('.series').classList.toggle('miniposter')" style="display:inline-block; cursor:pointer;">
                                    <i style='font-size:20px;top:3px;margin-left:8px;' class="glyphicon glyphicon-th"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="series adding miniposter" series-grid="false">
                @if(!isset($query))
                    <h1 style="margin-bottom:15px;margin-top:15px;color:rgb(225,225,225);text-align: center;">{{ __('COMMON/addtrending/hdr') }} - {{ __('SERIESLIST/TRAKT-TRENDING/addtrending-help-click-to-show/hdr') }}</h1>
      <div class="filters">
                        <div>
                            <h3>{{ __('COMMON/genre/hdr') }}</h3>
                            <div>
                                @foreach($genres as $genre)
                                    <button type="button" class="btn btn-xs btn-default filter-btn genre-btn" data-filter="{{ $genre }}">{{ $metaTranslator->translateGenre($genre) }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div style="width: 200px; margin-left: 10px;">
                            <h3>{{ __('COMMON/status/hdr') }}</h3>
                            <div style="display: flex; flex-flow: column;">
                                @foreach($statuses as $status)
                                    <button type="button" class="btn btn-xs btn-default filter-btn status-btn" data-filter="{{ $status }}" style="width: 100%; text-align: left; margin-bottom: 2px;">{{ $metaTranslator->translateStatus($status) }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif(count($results) === 0)
                    <div class="no-results" style="padding: 50px; text-align: center;">
                        <img src="{{ asset('img/trakt.png') }}" style="display:block; margin: 0 auto;">
                        <h3 style="color:white"><span>{{ __('SERIESLIST/TRAKT-SEARCHING/no-results/lbl') }}</span> {{ $query }}</h3>
                    </div>
                @endif

                <div class="series-grid" id="trending-grid" style="position:relative;width:100%;margin-top:10px">
                    @foreach($results as $show)
                        @component('partials._serie_header', [
                            'show' => $show, 
                            'noBadge' => true, 
                            'noOverview' => true,
                            'noTitle' => true,
                            'mode' => 'poster'
                        ])
                            @if(in_array($show['trakt_id'] ?? null, $favoriteIds))
                                <em class="earmark"><i class="glyphicon glyphicon-ok"></i></em>
                            @else
                                <form action="{{ route('search.add') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="trakt_id" value="{{ $show['trakt_id'] ?? '' }}">
                                    <em class="earmark add" onclick="this.parentElement.submit()" title="{{ __('COMMON/add-to-favorites/tooltip') }}">
                                        <i class="glyphicon glyphicon-plus"></i>
                                    </em>
                                </form>
                            @endif
                            <em class="earmark trailer">
                                <a href="{{ $show['trailer'] ?? 'https://www.youtube.com/results?search_query=' . urlencode($show['name'] ?? '') . '+official+trailer' }}" target="_blank" title="{{ __('COMMON/watch-trailer/tooltip') }}">
                                    <i class="glyphicon glyphicon-facetime-video"></i>
                                </a>
                            </em>
                        @endcomponent
                    @endforeach
                </div>

                @if(!isset($query) && count($results) > 0)
                    <button id="show-more-btn" class="btn btn-info" style="margin:5px auto;width:50%;opacity:0.8">
                        <i class="glyphicon glyphicon-plus"></i><span>{{ __('SERIESLIST/TRAKT-TRENDING/show-more/btn') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</series-list>

<script>
    document.body.classList.add('seriesaddingActive');
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('trending-grid');
    if (!grid) return;
    const items = Array.from(grid.querySelectorAll('serieheader'));
    const limit = 75;
    let currentLimit = limit;
    
    let activeGenre = 'all';
    let activeStatus = 'all';

    function filterItems() {
        let visibleCount = 0;
        let hiddenCount = 0;

        items.forEach(item => {
            const genres = (item.getAttribute('data-genre') || '').toLowerCase().split(' ');
            const status = (item.getAttribute('data-status') || '').toLowerCase();
            
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
        if (showMoreBtn) {
            showMoreBtn.style.display = hiddenCount > 0 ? 'block' : 'none';
        }
    }

    document.querySelectorAll('.genre-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('active');
            activeGenre = this.classList.contains('active') ? this.getAttribute('data-filter') : 'all';
            document.querySelectorAll('.genre-btn').forEach(b => { if(b !== this) b.classList.remove('active') });
            currentLimit = limit;
            filterItems();
        });
    });

    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('active');
            activeStatus = this.classList.contains('active') ? this.getAttribute('data-filter') : 'all';
            document.querySelectorAll('.status-btn').forEach(b => { if(b !== this) b.classList.remove('active') });
            currentLimit = limit;
            filterItems();
        });
    });

    const showMoreBtn = document.getElementById('show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            currentLimit += limit;
            filterItems();
        });
    }

    filterItems();
});
</script>
@endpush
