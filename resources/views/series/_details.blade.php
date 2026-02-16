{{--
    Series Overview — Left Panel

    Shows the series summary with last/next episode info and action buttons.
    This is the primary landing view when clicking a series from the favorites
    list. Loaded into the sidepanel left panel via data-sidepanel-show.

    Equivalent to the original DuckieTV 'serie' state, which renders
    serie-overview.html in the left panel with the right panel empty.

    Variables:
        $serie — The Serie model with seasons.episodes eager-loaded

    @see templates/sidepanel/serie-overview.html in DuckieTV-angular
    @see serieSidepanelCtrl in DuckieTV-angular/js/controllers/sidepanel/
--}}
<div class="serie-bg-img" style="background-image: url('{{ $serie->poster }}');"></div>

<button type="button" class="close" onclick="window.SidePanel.hide()" title="Close {{ $serie->name }}">&times;</button>

<h2>
    <span>{{ $serie->name }}</span>
</h2>

<table width="100%" border="0">
    <tbody class="metadata">
        <tr>
            <td colspan="2" class="overview">
                <p>{{ $serie->overview }}</p>
            </td>
        </tr>
        @php
            $prev = $serie->getLastEpisode();
            $next = $serie->getNextEpisode();
        @endphp

        {{-- Last Episode --}}
        <tr>
            <td>
                <h2 style="margin-top:0">LAST EPISODE</h2>
                @if($prev)
                    <h3 style="margin-bottom:13px">
                        <a href="javascript:void(0)" data-sidepanel-show="{{ route('episodes.show', $prev->id) }}">{{ $prev->formatted_episode }}</a>
                    </h3>
                @endif
            </td>
            @if($prev)
                <td>
                    {{ $prev->episodename }}<br>
                    <strong>AIRDATE:</strong> {{ $prev->getAirDate() instanceof \Carbon\Carbon ? $prev->getAirDate()->format('d-m-Y') : '?' }}
                </td>
            @else
                <td>UNKNOWN</td>
            @endif
        </tr>

        {{-- Next Episode (only if show is not ended) --}}
        @if($serie->status !== 'ended')
            <tr>
                <td valign="top" style="text-align: center; padding-bottom:15px;">
                    <h2 style="margin-top:0">NEXT EPISODE</h2>
                    @if($next)
                        <h3>
                            <a href="javascript:void(0)" data-sidepanel-show="{{ route('episodes.show', $next->id) }}">{{ $next->formatted_episode }}</a>
                        </h3>
                    @endif
                </td>
                @if($next)
                    <td style="padding-bottom:15px;">
                        {{ $next->episodename }}<br>
                        <strong>AIRDATE:</strong> {{ $next->getAirDate() instanceof \Carbon\Carbon ? $next->getAirDate()->format('d-m-Y') : '?' }}
                    </td>
                @else
                    <td style="padding-bottom:15px;">UNKNOWN</td>
                @endif
            </tr>
        @endif
    </tbody>

    <tbody class="buttons">
        {{-- Row 1: Series Details (full width) --}}
        <tr>
            <td colspan="2">
                <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.details', $serie->id) }}">
                    <i class="glyphicon glyphicon-info-sign"></i> <strong>SERIES DETAILS</strong>
                </a>
            </td>
        </tr>

        {{-- Row 2: Seasons + Episodes (two-face) --}}
        <tr class="two-face">
            <td>
                <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.seasons', $serie->id) }}">
                    <i class="glyphicon glyphicon-th"></i> <strong>SEASONS</strong>
                </a>
            </td>
            <td>
                <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', $serie->id) }}">
                    <i class="glyphicon glyphicon-list"></i> <strong>EPISODES</strong>
                </a>
            </td>
        </tr>

        {{-- Row 3: Mark All Watched (full width) --}}
        <tr>
            <td colspan="2">
                <div id="mark-all-watched-btn-group-overview">
                    <a href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-btn-group-overview').style.display='none'; document.getElementById('mark-all-watched-confirm-overview').style.display='table';">
                        <i class="glyphicon glyphicon-eye-open"></i> <strong>MARK ALL WATCHED</strong>
                    </a>
                </div>

                <table id="mark-all-watched-confirm-overview" class="buttons" width="100%" border="0" style="display:none">
                    <tr>
                        <td>
                            <a class="btn btn-danger" href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-form-overview').submit()">
                                <i class="glyphicon glyphicon-question-sign spin"></i> <strong>ARE YOU SURE?</strong>&nbsp;<strong>YES</strong>
                                <form id="mark-all-watched-form-overview" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="mark_watched"></form>
                            </a>
                        </td>
                        <td>
                            <a class="btn btn-success" href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-confirm-overview').style.display='none'; document.getElementById('mark-all-watched-btn-group-overview').style.display='block';">
                                <i class="glyphicon glyphicon-ban-circle"></i> <strong>CANCEL</strong>
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Row 4: Auto-Download toggle (conditional on torrenting.enabled) --}}
        @if(settings('torrenting.enabled'))
            <tr>
                <td colspan="2">
                    <a href="javascript:void(0)" onclick="document.getElementById('toggle-autodownload-form').submit()">
                        <i class="glyphicon {{ $serie->autoDownload ? 'glyphicon-cloud-download' : 'glyphicon-cloud' }}" style="{{ $serie->autoDownload ? 'color:green' : 'color:white' }}"></i>
                        <strong>AUTO-DOWNLOAD: {{ $serie->autoDownload ? 'ENABLED' : 'DISABLED' }}</strong>
                        <form id="toggle-autodownload-form" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_autodownload"></form>
                    </a>
                </td>
            </tr>
        @endif

        {{-- Row 5: Refresh (full width) --}}
        <tr>
            <td colspan="2">
                <a href="javascript:void(0)" onclick="document.getElementById('refresh-serie-form-overview').submit()">
                    <i class="glyphicon glyphicon-refresh"></i> <strong>REFRESH</strong>
                    <form id="refresh-serie-form-overview" method="POST" action="{{ route('series.refresh', $serie->id) }}" style="display:none;">@csrf @method('PUT')</form>
                </a>
            </td>
        </tr>

        {{-- Row 6: Calendar Hide/Show (full width) --}}
        <tr>
            <td colspan="2">
                <a href="javascript:void(0)" onclick="document.getElementById('toggle-calendar-form').submit()">
                    <i class="glyphicon {{ $serie->displaycalendar ? 'glyphicon-eye-close' : 'glyphicon-eye-open' }}"></i>
                    <strong>{{ $serie->displaycalendar ? 'HIDE FROM CALENDAR' : 'SHOW ON CALENDAR' }}</strong>
                    <form id="toggle-calendar-form" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_calendar"></form>
                </a>
            </td>
        </tr>

        {{-- Row 7: Settings (conditional on torrenting.enabled) --}}
        @if(settings('torrenting.enabled'))
            <tr>
                <td colspan="2">
                    <a class="torrent-settings" href="javascript:void(0)" onclick="window.SidePanel.torrentSettings({{ $serie->id }})">
                        <i class="glyphicon glyphicon-cog"></i> <strong>SETTINGS {{ $serie->name }}</strong>
                    </a>
                </td>
            </tr>
        @endif

        {{-- Row 7: Delete Series (danger, full width) --}}
        <tr>
            <td colspan="2">
                <a href="javascript:void(0)" onclick="if(confirm('Remove {{ addslashes($serie->name) }} from favorites?')) document.getElementById('remove-serie-form-overview').submit()" class="btn-danger">
                    <i class="glyphicon glyphicon-trash"></i> <strong>DELETE SERIES</strong>
                    <form action="{{ route('series.remove', $serie->id) }}" method="POST" id="remove-serie-form-overview" style="display:none">
                        @csrf @method('DELETE')
                    </form>
                </a>
            </td>
        </tr>
    </tbody>
</table>
