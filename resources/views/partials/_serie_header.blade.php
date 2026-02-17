@php
    /**
     * @var \App\Models\Show|array $show
     * @var bool $noBadge
     * @var bool $noTitle
     * @var bool $noOverview
     * @var string $mode (poster, list, etc)
     */
    $isModel = $show instanceof \App\Models\Show;
    $id = $isModel ? $show->id : ($show['trakt_id'] ?? $show['id'] ?? null);
    $name = $isModel ? $show->name : ($show['name'] ?? '');
    $poster = $isModel ? $show->poster : ($show['poster'] ?? '');
    $overview = $isModel ? $show->overview : ($show['overview'] ?? '');
    $notWatchedCount = $isModel ? ($show->notWatchedCount ?? 0) : 0;
    $displayCalendar = $isModel ? ($show->displaycalendar ?? true) : true;
    
    $genres = $isModel ? (str_replace('|', ' ', strtolower($show->genre ?? ''))) : (implode(' ', array_map('strtolower', $show['genres'] ?? [])));
    $status = strtolower($isModel ? ($show->status ?? '') : ($show['status'] ?? ''));

    // Uniform trigger for details. For search results, we point to the search detail which might eventually become a show page.
    $detailsUrl = $isModel ? route('series.show', $id) : route('search.index') . '/?trakt_id=' . $id; 
@endphp

<serieheader 
     class="{{ $mode ?? 'poster' }} {{ $isModel ? 'active' : '' }}" 
     mode="{{ $mode ?? 'poster' }}"
     data-id="{{ $id }}" 
     data-genre="{{ $genres }}"
     data-status="{{ $status }}"
     title="{{ $name }}"
     data-sidepanel-show="{{ $detailsUrl }}"
>
    <div class="serieheader">
        @if(!($noBadge ?? false))
            <div class="badges">
                @if($notWatchedCount > 0)
                    <em class="badge">
                        <i class="glyphicon glyphicon-eye-close"></i> {{ $notWatchedCount }}
                    </em>
                @endif
                @if(!$displayCalendar)
                    <em class="badge leftbadge" title="{{ __('COMMON/series-hidden/tooltip') }}">
                        <i class="glyphicon glyphicon-ban-circle" style="color:white !important"></i>
                    </em>
                @endif
            </div>
        @endif

        <a class="poster" href="{{ $detailsUrl }}" onclick="event.preventDefault()">
            <figure>
                <div class="img" style="background-image: url('{{ $poster }}');"></div>
                <figcaption>
                    @if(!($noTitle ?? false))
                        <h3 class="title">{{ $name }}</h3>
                    @endif
                </figcaption>
            </figure>
        </a>

        @if(!($noOverview ?? false))
            <div class="overview">
                <p>{{ $overview }}</p>
            </div>
        @endif

        {{ $slot ?? '' }}
    </div>
</serieheader>
