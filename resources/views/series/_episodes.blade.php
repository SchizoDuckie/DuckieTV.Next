{{--
    Episodes List — Right Panel

    Shows episodes for a single season with navigation controls to switch
    between seasons. Each episode row includes inline action buttons for
    torrent search, mark downloaded, and mark watched. Bottom buttons
    provide bulk actions for the active season.

    Displayed in the sidepanel right panel via data-sidepanel-expand from
    the series overview or episode detail views.

    Matches the original DuckieTV 'serie.season' state, which renders
    episodes.html in the right panel with season navigation controls.

    Variables:
        $serie         — The Serie model
        $seasons       — Collection of all seasons, sorted by seasonnumber
        $activeSeason  — The currently displayed Season model

    @see templates/sidepanel/episodes.html in DuckieTV-angular
    @see EpisodesSidepanelCtrl in DuckieTV-angular/js/controllers/sidepanel/
--}}
<div class="episodes">
    <button type="button" class="close" onclick="SidePanel.show('{{ route('series.show', $serie->id) }}')" title="Close Episodes">&times;</button>

    {{-- Season Navigation Controls --}}
    @if($activeSeason)
        @php
            $seasonList = $seasons->values();
            $activeIndex = $seasonList->search(fn ($s) => $s->id === $activeSeason->id);
            $hasPrev = $activeIndex < $seasonList->count() - 1;
            $hasNext = $activeIndex > 0;
            $prevSeason = $hasPrev ? $seasonList[$activeIndex + 1] : null;
            $nextSeason = $hasNext ? $seasonList[$activeIndex - 1] : null;
            $firstSeason = $seasonList->last();
            $lastSeason = $seasonList->first();
        @endphp

        <div class="season-control">
            <div class="season-control-left">
                @if($seasonList->count() > 2 && $firstSeason)
                    <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $firstSeason->id]) }}" class="{{ !$hasPrev ? 'disabled' : '' }}" title="First season"><i class="glyphicon glyphicon-step-backward"></i></a>
                @endif
                @if($prevSeason)
                    <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $prevSeason->id]) }}" title="Previous season"><i class="glyphicon glyphicon-chevron-left"></i></a>
                @else
                    <a class="disabled"><i class="glyphicon glyphicon-chevron-left"></i></a>
                @endif
            </div>

            <h2>{{ $activeSeason->seasonnumber == 0 ? 'Specials' : 'Season ' . $activeSeason->seasonnumber }}</h2>

            <div class="season-control-right">
                @if($nextSeason)
                    <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $nextSeason->id]) }}" title="Next season"><i class="glyphicon glyphicon-chevron-right"></i></a>
                @else
                    <a class="disabled"><i class="glyphicon glyphicon-chevron-right"></i></a>
                @endif
                @if($seasonList->count() > 2 && $lastSeason)
                    <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $lastSeason->id]) }}" class="{{ !$hasNext ? 'disabled' : '' }}" title="Last season"><i class="glyphicon glyphicon-step-forward"></i></a>
                @endif
            </div>
        </div>

        @if($activeSeason->overview)
            <p class="overview" style="text-align:justify">{{ $activeSeason->overview }}</p>
        @endif

        <strong>EPISODES</strong>

        {{-- Episode List Table --}}
        <table class="table table-condensed light episodelist" style="margin:10px 0 10px 0; background-color:transparent">
            <tbody>
                @foreach($activeSeason->episodes->sortByDesc('episodenumber') as $episode)
                    <tr class="episodecontainer active-season-episode">
                        <td style="width:70px">
                            <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}">{{ $episode->formatted_episode }}</a>
                        </td>
                        <td>
                            <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}" title="{{ $episode->overview }}">{{ $episode->episodename }}</a>
                        </td>
                        <td class="nobreaks" align="right" style="width:66px">
                            <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}" class="{{ $episode->hasAired() ? 'airdate' : '' }}">
                                {{ $episode->getAirDate() instanceof \Carbon\Carbon ? $episode->getAirDate()->format('d-m-Y') : '?' }}
                            </a>

                            {{-- Per-episode torrent search (conditional on torrenting.enabled and episode aired) --}}
                            @if(($episode->hasAired()) && settings('torrenting.enabled'))
                                <div class="torrentctrls">
                                    @php
                                        $epSearchQuery = ($serie->custom_search_string ?: $serie->name) . ' ' . $episode->formatted_episode;
                                    @endphp
                                    <a href="javascript:void(0)" onclick="TorrentSearch.open('{{ route('torrents.dialog', ['query' => $epSearchQuery, 'episode_id' => $episode->id]) }}')" title="Find torrent" class="auto-download-episode">
                                        <i class="glyphicon glyphicon-magnet"></i>
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td style="width:58px; position:relative">
                            @if($episode->hasAired())
                                <a href="javascript:void(0)" onclick="this.querySelector('form').submit()" title="{{ $episode->downloaded ? 'Unmark downloaded' : 'Mark downloaded' }}">
                                    <i class="glyphicon glyphicon-floppy-saved" style="color: {{ $episode->downloaded ? '#5bc0de' : '#555' }};"></i>
                                    <form method="POST" action="{{ route('episodes.update', $episode->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_download"></form>
                                </a>
                                <a href="javascript:void(0)" onclick="this.querySelector('form').submit()" title="{{ $episode->watched ? 'Unmark watched' : 'Mark watched' }}">
                                    <i class="glyphicon glyphicon-eye-open" style="color: {{ $episode->watched ? '#5cb85c' : '#555' }};"></i>
                                    <form method="POST" action="{{ route('episodes.update', $episode->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="toggle_watched"></form>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Bottom Action Buttons --}}
        <table class="buttons" style="margin-top:10px; width:100%">
            @if(settings('torrenting.enabled'))
                <tr class="two-face-torrent">
                    <td colspan="2">
                        <table style="width:100%; margin: 5px 0px 5px 0px">
                            <tr>
                                <td style="width:100%; padding-left:20px">
                                    <a class="download" href="javascript:void(0)" onclick="
                                        const buttons = document.querySelectorAll('.active-season-episode .auto-download-episode');
                                        Array.from(buttons).reverse().forEach((btn, idx) => {
                                            setTimeout(() => btn.click(), (idx + 1) * 100);
                                        });
                                    ">
                                        <i class="glyphicon glyphicon-cloud-download"></i>
                                        <strong>AUTO-DOWNLOAD ALL</strong>
                                    </a>
                                </td>
                                <td style="padding-right:20px">
                                    @php
                                        $seasonSearchQuery = ($serie->custom_search_string ?: $serie->name) . ' season ' . $activeSeason->seasonnumber;
                                    @endphp
                                    <a class="torrent-dialog auto-download" href="javascript:void(0)" onclick="TorrentSearch.open('{{ route('torrents.dialog', ['query' => $seasonSearchQuery]) }}')" title="Search & Download: {{ $seasonSearchQuery }}">
                                        <i class="glyphicon glyphicon-download"></i>
                                        <strong style="display:flex">&nbsp;</strong>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif
            <tr class="two-face">
                <td>
                    <a href="javascript:void(0)" onclick="document.getElementById('mark-season-downloaded-{{ $activeSeason->id }}').submit()">
                        <i class="glyphicon glyphicon-floppy-saved" style="top:0"></i>
                        <strong>MARK ALL DOWNLOADED</strong>
                        <form id="mark-season-downloaded-{{ $activeSeason->id }}" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="mark_season_downloaded"><input type="hidden" name="season_id" value="{{ $activeSeason->id }}"></form>
                    </a>
                </td>
                <td>
                    <a href="javascript:void(0)" onclick="document.getElementById('mark-season-watched-{{ $activeSeason->id }}').submit()">
                        <i class="glyphicon glyphicon-eye-open" style="top:0"></i>
                        <strong>MARK ALL WATCHED</strong>
                        <form id="mark-season-watched-{{ $activeSeason->id }}" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="mark_season_watched"><input type="hidden" name="season_id" value="{{ $activeSeason->id }}"></form>
                    </a>
                </td>
            </tr>
        </table>
    @else
        <h2>No seasons found</h2>
    @endif
</div>
