@php 
    $metaTranslator = app(\App\Services\SeriesMetaTranslations::class);
    // Use original DuckieTV lists for Trending filters parity
    $genres = \App\Services\SeriesMetaTranslations::GENRES;
    $statuses = \App\Services\SeriesMetaTranslations::STATUSES;
@endphp

<div>
    <div class="tools">
        <div class="filtertools">
            <div class="row">
                <div class="col col-md-10">
                    <form id="search-form" onsubmit="window.Panels.show('seriesadding', '{{ route('search.query') }}?q=' + this.q.value); return false;">
                        <div class="input-group pull-left">
                            <span class="input-group-addon">
                                <i style='font-size:15px;' class="glyphicon glyphicon-search"></i>
                            </span>
                            <input type="text" name="q" value="{{ $query ?? '' }}" 
                                   placeholder="{{ __('SERIESLIST/TRAKT-SEARCHING/please-wait/lbl') }}"
                                   style="width:100%"
                                   id="search-input">
                        </div>
                    </form>
                </div>
                <div class="col col-md-2" style="display: flex; height: 40px; justify-content: flex-end; align-items: center;">
                    <a class="close-panel" style="cursor: pointer; color: white; font-size: 20px;">&times;</a>
                </div>
            </div>
        </div>
    </div>


    <div class="series adding miniposter">
        @if(empty($query))
            <h1 style="margin-bottom:15px;margin-top:15px;color:rgb(225,225,225);text-align: center;">{{ __('COMMON/addtrending/hdr') }} - {{ __('SERIESLIST/TRAKT-TRENDING/addtrending-help-click-to-show/hdr') }}</h1>
        @endif
        <div class="filters-container" style="display: flex; gap: 20px; padding: 10px; background: rgba(0,0,0,0.3); margin-bottom: 10px;">
            <div class="filter-group">
                <h3 style="margin-top: 0; font-size: 14px; color: #ccc;">{{ __('COMMON/genre/hdr') }}</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    @foreach($genres as $genre)
                        <button type="button" class="btn btn-xs btn-default filter-btn genre-btn" 
                                onclick="window.Panels.show('seriesadding', '{{ route('search.query') }}?q={{ $genre }}')">
                            {{ $metaTranslator->translateGenre($genre) }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="filter-group">
                <h3 style="margin-top: 0; font-size: 14px; color: #ccc;">{{ __('COMMON/status/hdr') }}</h3>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    @foreach($statuses as $status)
                        <button type="button" class="btn btn-xs btn-default filter-btn status-btn" 
                                onclick="window.Panels.show('seriesadding', '{{ route('search.query') }}?q={{ $status }}')"
                                style="text-align: left;">
                            {{ $metaTranslator->translateStatus($status) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="series-grid">
            @forelse($results as $show)
                @include('partials._serie_header', [
                    'show' => $show, 
                    'isFavorite' => in_array($show['trakt_id'] ?? null, $favoriteIds ?? []),
                    'noBadge' => true,
                    'noOverview' => true,
                    'noTitle' => true,
                    'mode' => 'poster'
                ])
            @empty
                <div style="padding: 20px; color: white; text-align: center;">
                    <h3>{{ __('SERIESLIST/TRAKT-SEARCHING/no-results/lbl') }}</h3>
                </div>
            @endforelse
        </div>
    </div>
</div>
