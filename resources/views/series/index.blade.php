@extends('layouts.app')

@php 
    $title = 'Library';
    $metaTranslator = app(\App\Services\SeriesMetaTranslations::class);
@endphp

@section('content')
<series-list class="active">
    <div class="series-list active">
        <div>
            <div class="tools">
                <div class="filtertools">
                    <div class="row">
                        <div class="col col-md-5">
                            <form action="{{ route('series.index') }}" method="GET">
                                <div class="input-group pull-left">
                                    <span class="input-group-addon">
                                        <i style='font-size:15px;' class="glyphicon glyphicon-search"></i>
                                    </span>
                                    <input type="text" name="q" value="{{ request('q') }}" 
                                           placeholder="{{ __('SERIESLIST/TOOLS/FAVORITES/filter-placeholder') }}"
                                           style="width:100%"
                                           oninput="this.form.submit()">
                                </div>
                            </form>
                        </div>

                        <div class="col col-md-5">
                            <!-- statusfilter list -->
                            <div class="btn-group sort-status dropdown">
                                <button type="button" class="btn dropdown-toggle" data-toggle="dropdown">
                                    <span>{{ __('COMMON/status/hdr') }}</span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu" style="padding: 10px; min-width: 200px;">
                                    @foreach($statuses as $status)
                                        <li style="padding: 3px 0;">
                                            <label style="font-weight: normal; cursor: pointer;">
                                                <input type="checkbox" class="status-filter" data-status="{{ $status }}"> 
                                                {{ $metaTranslator->translateStatus($status) }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <!-- genre filter list -->
                            <div class="btn-group sort-genre dropdown">
                                <button type="button" class="btn btn-large dropdown-toggle" data-toggle="dropdown">
                                    <span>{{ __('COMMON/genre/hdr') }}</span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu" style="padding: 10px; min-width: 200px; max-height: 400px; overflow-y: auto;">
                                    @foreach($genres as $genre)
                                        <li style="padding: 3px 0;">
                                            <label style="font-weight: normal; cursor: pointer;">
                                                <input type="checkbox" class="genre-filter" data-genre="{{ $genre }}"> 
                                                {{ $metaTranslator->translateGenre($genre) }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <!-- OrderBy library button -->
                            <div class="btn-group sort-btn dropdown">
                                <button type="button" class="btn dropdown-toggle" data-toggle="dropdown">
                                    <i class="glyphicon glyphicon-sort-by-attributes"></i>
                                    <span>{{ __('COMMON/orderby/glyph') }}</span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'name']) }}">{{ __('ORDERBYLIST') ? explode('|', __('ORDERBYLIST'))[0] : 'Name' }}</a></li>
                                    <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'added']) }}">{{ __('ORDERBYLIST') ? explode('|', __('ORDERBYLIST'))[1] : 'Added' }}</a></li>
                                    <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'first aired']) }}">{{ __('ORDERBYLIST') ? explode('|', __('ORDERBYLIST'))[2] : 'First Aired' }}</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col col-md-2" style="display: flex; height: 40px; justify-content: flex-end; align-items: center;">
                            <a onclick="document.querySelector('.series').classList.toggle('miniposter')" style="cursor:pointer;">
                                <i style='font-size:20px;top:3px;margin-left:8px;' class="glyphicon glyphicon-th"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="series" series-grid="false">
                <h1 style='margin-bottom:15px;margin-top:15px;color:rgb(225,225,225);'>
                    {{ __('SERIESLIST/FAVORITES/click-to-see/lbl') }}
                </h1>
                
                <div class="series-grid">
                    @forelse($series as $show)
                        @include('partials._serie_header', ['show' => $show])
                    @empty
                        <div style="padding: 20px; color: white; text-align: center;">
                            <h3>{{ __('SERIESLIST/TRAKT-TRENDING/series-no/hdr') }}</h3>
                            <p>{{ __('SERIESLIST/TRAKT-TRENDING/series-no/desc') }}</p>
                            <a href="{{ route('search.index') }}" class="btn btn-info">{{ __('SERIESLIST/TOOLS/FAVORITES/addshow-show/glyph') }}</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</series-list>

<script>
    document.body.classList.add('serieslistActive');
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.querySelector('.series-grid');
    if (!grid) return;
    const items = Array.from(grid.querySelectorAll('serieheader'));
    
    function filterItems() {
        const activeStatuses = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.dataset.status);
        const activeGenres = Array.from(document.querySelectorAll('.genre-filter:checked')).map(el => el.dataset.genre);

        items.forEach(item => {
            const status = item.getAttribute('data-status') ? item.getAttribute('data-status').toLowerCase() : '';
            const genres = item.getAttribute('data-genre') ? item.getAttribute('data-genre').toLowerCase().split(' ') : [];
            
            const statusMatch = activeStatuses.length === 0 || activeStatuses.includes(status);
            const genreMatch = activeGenres.length === 0 || activeGenres.some(g => genres.includes(g));

            item.style.display = (statusMatch && genreMatch) ? 'inline-block' : 'none';
        });
    }

    document.querySelectorAll('.status-filter, .genre-filter').forEach(el => {
        el.addEventListener('change', filterItems);
    });
});
</script>
@endpush
