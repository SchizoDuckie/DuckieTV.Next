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
  <button type="button" class="close" data-sidepanel-show="{{ route('series.show', $serie->id) }}" title="{{ __('Close Episodes') }}" data-toggle="tooltip" data-placement="left">&times;</button>
  
  @if($activeSeason)
  <div class="season-control">
    <div class="season-control-left">
        @if($seasons->count() > 2)
            <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $seasons->last()->id]) }}" class="{{ $activeSeason->seasonnumber >= $seasons->last()->seasonnumber ? 'disabled' : '' }}" title="{{ __('First season') }}" data-toggle="tooltip" data-placement="right"><i class="glyphicon glyphicon-step-backward"></i></a>
        @endif
        @php
            $prevSeason = $seasons->where('seasonnumber', '<', $activeSeason->seasonnumber)->sortByDesc('seasonnumber')->first();
        @endphp
        <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $prevSeason ? $prevSeason->id : $activeSeason->id]) }}" class="{{ !$prevSeason ? 'disabled' : '' }}" title="{{ __('Previous season') }}" data-toggle="tooltip" data-placement="right"><i class="glyphicon glyphicon-chevron-left"></i></a>
    </div>
    <h2>{{ $activeSeason->seasonnumber == 0 ? __('Specials') : __('Season') . ' ' . $activeSeason->seasonnumber }}</h2>
    <div class="season-control-right">
        @if($seasons->count() > 2)
             <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $seasons->first()->id]) }}" class="{{ $activeSeason->seasonnumber <= $seasons->first()->seasonnumber ? 'disabled' : '' }}" title="{{ __('Last season') }}" data-toggle="tooltip" data-placement="left"><i class="glyphicon glyphicon-step-forward"></i></a>
        @endif
        @php
            $nextSeason = $seasons->where('seasonnumber', '>', $activeSeason->seasonnumber)->sortBy('seasonnumber')->first();
        @endphp
        <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $nextSeason ? $nextSeason->id : $activeSeason->id]) }}" class="{{ !$nextSeason ? 'disabled' : '' }}" title="{{ __('Next season') }}" data-toggle="tooltip" data-placement="left"><i class="glyphicon glyphicon-chevron-right"></i></a>
    </div>
  </div>
  <p class="overview" style="text-align:justify">{{ $activeSeason->overview }}</p>
  <strong><span>{{ __('EPISODES') }}</span></strong>
  
  <table class="table table-condensed light episodelist" style='margin:10px 0 10px 0; background-color:transparent'>
    @if($activeSeason->episodes->isEmpty())
    <tbody>
      <tr>
        <td>{{ __('Fetching episodes...') }}</td>
      </tr>
    </tbody>
    @else
    <tbody>
      @foreach($activeSeason->episodes->sortBy('episodenumber') as $episode)
      <tr class="episodecontainer active-season-episode">
        <td style="width:70px">
          <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}">{{ $episode->formatted_episode }}</a>
        </td>
        <td>
          <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}" title="{{ $episode->episodename }}<br>{{ $episode->overview }}" data-html="true" data-toggle="tooltip" data-placement="bottom" data-delay='{"show":500}'>{{ $episode->episodename }}</a>
        </td>
        <td class="nobreaks" align="right" style="width:66px">
          <a href="javascript:void(0)" data-sidepanel-update="{{ route('episodes.show', $episode->id) }}" title="{{ $episode->getAirDate() instanceof \Carbon\Carbon ? $episode->getAirDate()->format('F j, Y, g:i a') : '' }}" class="{{ ($episode->hasAired() || $episode->isLeaked()) ? 'airdate' : '' }}">{{ $episode->getAirDate() instanceof \Carbon\Carbon ? $episode->getAirDate()->format('n/j/y') : '?' }}</a>

          @if(($episode->hasAired() || $episode->isLeaked()) && settings('torrenting.enabled'))
          <div class="torrentctrls">
            <a class="torrent-dialog" onclick="TorrentSearch.open('{{ route('torrents.search-dialog', ['query' => $episode->search_query, 'episode_id' => $episode->id]) }}')">
                <i class="glyphicon glyphicon-download"></i>
            </a>
            <a class="auto-download-episode" onclick="window.SidePanel.autoDownload({{ $episode->id }})" title="{{ __('Auto Download') }}">
              <i class="glyphicon glyphicon-cloud-download"></i>
            </a>
          </div>
          @endif

        </td>
        <td style="width:58px;position:relative">
          @if($episode->hasAired() || $episode->isLeaked())
            <div class="episode-downloaded">
                 <a onclick="window.SidePanel.toggleEpisodeDownloaded({{ $episode->id }}, this)" 
                    class="glyphicon {{ $episode->downloaded ? 'glyphicon-floppy-saved' : 'glyphicon-floppy-disk' }}" 
                    style="width:100%"
                    title="{{ $episode->downloaded ? __('Unmark downloaded') : __('Mark downloaded') }}">
                 </a>
            </div>
            <div class="episode-watched">
                 <a onclick="window.SidePanel.toggleEpisodeWatched({{ $episode->id }}, this)" 
                    class="glyphicon {{ $episode->watched ? 'glyphicon-eye-open' : 'glyphicon-eye-close' }}" 
                    style="width:100%"
                    title="{{ $episode->watched ? __('Unmark watched') : __('Mark watched') }}">
                 </a>
            </div>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
    @endif
  </table>

  @if(settings('download.ratings') && isset($ratingPoints) && count($ratingPoints) > 0)
    <h2 style='border-bottom:1px solid white;padding:5px;margin-top:10px'>{{ __('Episode Ratings') }}</h2>
    <div class="chart">
      @foreach($ratingPoints as $index => $point)
          <div class="chartLine" style="height: {{ $point['y'] }}%; left: {{ (100 / count($ratingPoints)) * $index }}%; width: {{ 100 / count($ratingPoints) }}%;" title="{{ $point['label'] }}" data-toggle="tooltip"></div>
      @endforeach
    </div>
  @endif

  <table class="buttons" style="margin-top:10px;width:100%">
    <tr class="two-face-torrent">
      <td colspan="2">
        <table style="width:100%;margin: 5px 0px 5px 0px" @if(!settings('torrenting.enabled')) style="display:none" @endif>
          <tr>
            <td @if($serie->tvdb_id) style="width:100%;padding-left:20px" @endif>
               @if($serie->tvdb_id)
              <a class="download" onclick="window.SidePanel.autoDownloadAll()">
                <i class="glyphicon glyphicon-cloud-download"></i><strong>{{ __('Auto-download all') }}</strong>
              </a>
               @else
                <a class="download btn btn-danger" href='https://github.com/SchizoDuckie/DuckieTV/wiki/FAQ#why-is-the-episode-find-a-torrent-button-not-working' target='_blank'>
                  <i class="glyphicon glyphicon-ban-circle"></i><strong style="display:flex">&nbsp;<del>&nbsp;TVDB_ID&nbsp;</del>&nbsp;<i class="glyphicon glyphicon-info-sign"></i></strong>
                </a>
               @endif
            </td>
            <td @if($serie->tvdb_id) style="padding-right:20px" @endif>
               @if($serie->tvdb_id)
              <a class="torrent-dialog auto-download" onclick="TorrentSearch.open('{{ route('torrents.search-dialog', ['query' => $seasonSearchQuery]) }}')">
                <strong style="display:flex">&nbsp;</strong>
              </a>
               @else
              <a class="auto-download btn btn-danger" href='https://github.com/SchizoDuckie/DuckieTV/wiki/FAQ#why-is-the-episode-find-a-torrent-button-not-working' target='_blank'>
                <i class="glyphicon glyphicon-ban-circle"></i><strong style="display:flex">&nbsp;</strong>
              </a>
               @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr class="two-face">
      <td>
        <a onclick="window.SidePanel.markSeasonDownloaded({{ $serie->id }}, {{ $activeSeason->id }})">
          <table>
            <tr>
              <td style="width:22px">
                <i class="glyphicon glyphicon-floppy-saved" style="top:0"></i>
              </td>
              <td style="width:auto">
                <strong>{{ __('MARK ALL DOWNLOADED') }}</strong>
              </td>
            </tr>
          </table>
        </a>
      </td>
      <td>
        <a onclick="window.SidePanel.markSeasonWatched({{ $serie->id }}, {{ $activeSeason->id }})">
          <table>
            <tr>
              <td style="width:22px">
                <i class="glyphicon glyphicon-eye-open" style="top:0"></i>
              </td>
              <td style="width:auto">
                <strong>{{ __('MARK ALL WATCHED') }}</strong>
              </td>
            </tr>
          </table>
        </a>
      </td>
    </tr>
  </table>
  @else
    <h2>{{ __('No seasons found') }}</h2>
  @endif
</div>
