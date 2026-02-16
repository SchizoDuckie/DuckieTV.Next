{{--
    Episode Detail View — Left Panel

    Shows full episode information with action buttons. Loaded into the
    sidepanel left panel when clicking an episode on the calendar (via
    data-sidepanel-show) or from the episodes list (via data-sidepanel-update).

    Matched to original DuckieTV-angular templates/sidepanel/episode-details.html
--}}
<div class="serie-bg-img" style="background-image: url('{{ $serie->poster }}');"></div>
<button type="button" class="close" onclick="SidePanel.hide()" title="{{ __('COMMON/close/btn') }} {{ $serie->name }} - {{ $episode->formatted_episode }}">&times;</button>

@if($episode->filename)
    <div class="episode-img large" style="background-image: url('{{ $episode->filename }}');"></div>
@endif

@if(!$episode->filename)
    <center>
        {{-- Fallback if no episode image (using series poster as header style) --}}
        <img src="{{ $serie->poster }}" style="margin:0 auto; max-width: 150px; display: block; box-shadow: 0px 0px 10px rgba(0,0,0,0.5);">
    </center>
@endif

<h2>
    <span>{{ $serie->name }} - {{ $episode->formatted_episode }}</span>
</h2>

<h3>{{ $episode->episodename }}</h3>
<h5 style="text-align: center;">{{ $episode->getAirDate() instanceof \Carbon\Carbon ? $episode->getAirDate()->format('F j, Y, g:i a') : 'Unknown' }}</h5>
<p class="overview" style="text-align:justify">{{ $episode->overview }}</p>

<table class="buttons" width="100%" border="0">
    {{-- Row 1: Series Details (full width) --}}
    <tr>
        <td colspan="2">
            <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.details', $serie->id) }}">
                <i class="glyphicon glyphicon-info-sign"></i> <strong>{{ __('COMMON/series-details/lbl') }}</strong>
            </a>
        </td>
    </tr>

    {{-- Row 2: Seasons + Episodes (two-face) --}}
    <tr class="two-face">
        <td>
            <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.seasons', $serie->id) }}">
                <i class="glyphicon glyphicon-th"></i> <strong>{{ __('COMMON/seasons/lbl') }}</strong>
            </a>
        </td>
        <td>
            <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', $serie->id) }}">
                <i class="glyphicon glyphicon-list"></i> <strong>{{ __('COMMON/episodes/lbl') }}</strong>
            </a>
        </td>
    </tr>

    {{-- Row 3: Mark Downloaded + Mark Watched (two-face, only if aired) --}}
    @if($episode->hasAired())
        <tr class="two-face">
            <td>
                <a href="javascript:void(0)" onclick="document.getElementById('toggle-download-form').submit()">
                    <i class="glyphicon {{ $episode->downloaded ? 'glyphicon-floppy-remove' : 'glyphicon-floppy-save' }}"></i>
                    <strong>{{ $episode->downloaded ? __('UNMARK DOWNLOADED') : __('MARK DOWNLOADED') }}</strong>
                    <form id="toggle-download-form" method="POST" action="{{ route('episodes.update', $episode->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_download"></form>
                </a>
            </td>
            <td>
                <a href="javascript:void(0)" onclick="document.getElementById('toggle-watched-form').submit()">
                    <i class="glyphicon {{ $episode->watched ? 'glyphicon-eye-close' : 'glyphicon-eye-open' }}"></i>
                    <strong>{{ $episode->watched ? __('UNMARK WATCHED') : __('MARK WATCHED') }}</strong>
                    <form id="toggle-watched-form" method="POST" action="{{ route('episodes.update', $episode->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_watched"></form>
                </a>
            </td>
        </tr>
    @endif

    @if(!$episode->hasAired() || $episode->isLeaked())
        <tr>
            <td colspan="2" class="buttons">
                <a href="javascript:void(0)" onclick="document.getElementById('toggle-leaked-form').submit()">
                    <i class="glyphicon {{ $episode->isLeaked() ? 'glyphicon-ban-circle' : 'glyphicon-flash' }}"></i>
                    <strong>{{ $episode->isLeaked() ? __('UNMARK LEAKED') : __('MARK LEAKED') }}</strong>
                    <form id="toggle-leaked-form" method="POST" action="{{ route('episodes.update', $episode->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_leaked"></form>
                </a>
            </td>
        </tr>
    @endif

    {{-- Row 5: Torrent Actions (Nested Table) --}}
    @if(settings('torrenting.enabled') && ($episode->hasAired() || $episode->isLeaked()))
        <tr>
            <td colspan="2" class="two-face-torrent" style="position:relative; padding: 0;">
                <table style="width:100%; margin: 5px 0px 5px 0px;">
                    <tr>
                        <td style="width:100%; padding-left:15px;">
                            @php
                                $searchQuery = ($serie->custom_search_string ?: $serie->name) . ' ' . $episode->formatted_episode;
                            @endphp
                            <a href="javascript:void(0)" onclick="TorrentSearch.open('{{ route('torrents.dialog', ['query' => $searchQuery, 'episode_id' => $episode->id]) }}')">
                                <i class="glyphicon glyphicon-magnet"></i> <strong>{{ __('SIDEPANEL/EPISODE-DETAILS/find-torrent/btn') }}</strong>
                            </a>
                        </td>
                        <td style="padding-right:6px;">
                            {{-- Auto Download (Placeholder) --}}
                            <a class="auto-download" href="javascript:void(0)" title="{{ __('COMMON/auto-download/lbl') }}" style="display: block; width: 40px; text-align: center;">
                                <i class="glyphicon glyphicon-cloud-download"></i>
                            </a>
                        </td>
                        <td style="padding-right:15px;">
                            {{-- Settings (Placeholder) --}}
                            <a class="torrent-settings" href="javascript:void(0)" title="{{ __('COMMON/settings/lbl') }}" style="display: block; width: 40px; text-align: center;">
                                <i class="glyphicon glyphicon-cog"></i>
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    {{-- Row 6: Torrent Remote Control (Placeholder/Skipped) --}}

    {{-- Row 7: Find Subtitle --}}
    @if(settings('torrenting.enabled') && $episode->hasAired())
        <tr>
            <td colspan="2" class="buttons">
                <a href="javascript:void(0)" title="{{ __('COMMON/find-subtitle/lbl') }}" onclick="window.Toast.info('Subtitle search not implemented yet.')">
                    <i class="glyphicon glyphicon-text-width"></i> <strong>{{ __('COMMON/find-subtitle/lbl') }}</strong>
                </a>
            </td>
        </tr>
    @endif
</table>
