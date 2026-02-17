<div class="leftpanel serie-overview">
  <div class="serie-bg-img" style="background-image: url('{{ $serie->poster }}');"></div>
  <button type="button" class="close" data-sidepanel-show="{{ route('series.show', $serie->id) }}" title="{{ __('Close') }} {{ $serie->name }}{{ $episode->formatted_episode ? ' - ' : '' }}{{ $episode->formatted_episode }}" data-toggle="tooltip" data-placement="left">&times;</button>
  
  @if($episode->filename)
    <div class="episode-img large" style="background-image: url('{{ $episode->filename }}');"></div>
  @else
    <center>
        <img src="{{ $serie->poster }}" style="margin:0 auto; max-width: 150px; display: block; box-shadow: 0px 0px 10px rgba(0,0,0,0.5);">
    </center>
  @endif

  <h2>
    <span>{{ $serie->name }}{{ $episode->formatted_episode ? ' - ' : '' }}{{ $episode->formatted_episode }}</span>
  </h2>

  <h3>{{ $episode->episodename }}</h3>
  <h5 style="text-align: center;">{{ $episode->getFormattedAirDate() }}</h5>
  <p class="overview" style="text-align:justify">{{ $episode->overview }}</p>

  <table class="buttons" width="100%" border="0">
    <tr>
      <td colspan="2">
        <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.details', $serie->id) }}">
            <i class="glyphicon glyphicon-info-sign"></i><strong>{{ __('Series Details') }}</strong>
        </a>
      </td>
    </tr>
    <tr class="two-face">
      <td>
        <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.seasons', $serie->id) }}">
            <i class="glyphicon glyphicon-info-sign"></i><strong>{{ __('Seasons') }}</strong>
        </a>
      </td>
      <td>
        <a href="javascript:void(0)" data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $episode->season_id]) }}">
            <i class="glyphicon glyphicon-info-sign"></i><strong>{{ __('Episodes') }}</strong>
        </a>
      </td>
    </tr>
    <tr class="two-face">
      <td>
        @if($episode->hasAired() || $episode->isLeaked())
             <a class="mark-button mark-downloaded-button" onclick="window.SidePanel.toggleEpisodeDownloaded({{ $episode->id }}, this)">
              <table>
                <tr>
                  <td class="left-glyph">
                    <i class="glyphicon glyphicon-floppy-{{ $episode->downloaded ? 'saved' : 'disk' }}" style="{{ $episode->downloaded ? 'color:green' : '' }}"></i>
                  </td>
                  <td class="right-text-glyph">
                    <strong>{{ __('MARK AS') }}</strong>
                    <i class="glyphicon glyphicon-floppy-{{ $episode->downloaded ? 'disk' : 'saved' }}"></i>
                  </td>
                </tr>
              </table>
            </a>
        @endif
      </td>
      <td>
        @if($episode->hasAired() || $episode->isLeaked())
             <a class="mark-button mark-watched-button" onclick="window.SidePanel.toggleEpisodeWatched({{ $episode->id }}, this)">
              <table>
                <tr>
                  <td class="left-glyph">
                     <i class="glyphicon glyphicon-eye-{{ $episode->watched ? 'open' : 'close' }}" style="{{ $episode->watched ? 'color:green' : '' }}"></i>
                  </td>
                  <td class="right-text-glyph">
                    <strong>{{ __('MARK AS') }}</strong>
                    <i class="glyphicon glyphicon-eye-{{ $episode->watched ? 'close' : 'open' }}"></i>
                  </td>
                </tr>
              </table>
            </a>
        @endif
      </td>
    </tr>
    @if(!$episode->hasAired() && !$episode->isLeaked())
    <tr>
        <td colspan="2" class="buttons">
             <a href="javascript:void(0)" onclick="window.SidePanel.toggleEpisodeLeaked({{ $episode->id }})">
                <i class="glyphicon {{ $episode->isLeaked() ? 'glyphicon-ban-circle' : 'glyphicon-flash' }}"></i>
                <strong>{{ $episode->isLeaked() ? __('UNMARK LEAKED') : __('MARK LEAKED') }}</strong>
            </a>
            <p style='font-weight:normal; text-align:center; padding-top:10px'>{{ $episode->getAirDate() instanceof \Carbon\Carbon ? $episode->getAirDate()->format('n/j/y') : '' }}</p>
        </td>
    </tr>
    @endif
    
    @if(settings('torrenting.enabled') && ($episode->hasAired() || $episode->isLeaked()))
    <tr>
      <td colspan="2" class="two-face-torrent" style="position:relative">
        <table style="width:100%;margin: 5px 0px 5px 0px">
          <tr>
            <td @if($serie->tvdb_id) style="width:100%;padding-left:15px" @endif>
               @if($serie->tvdb_id)
              <a class="torrent-dialog download" onclick="TorrentSearch.open('{{ route('torrents.search-dialog', ['query' => $searchQuery, 'episode_id' => $episode->id]) }}')">
                <i class="glyphicon glyphicon-download"></i><strong style="padding-left:21px">{{ __('Find a torrent') }}</strong>
              </a>
               @else
                <a class="download btn btn-danger" href='https://github.com/SchizoDuckie/DuckieTV/wiki/FAQ#why-is-the-episode-find-a-torrent-button-not-working' target='_blank'>
                  <i class="glyphicon glyphicon-ban-circle"></i><strong style="display:flex">&nbsp;<del>&nbsp;TVDB_ID&nbsp;</del>&nbsp;<i class="glyphicon glyphicon-info-sign"></i></strong>
                </a>
               @endif
            </td>
            <td @if($serie->tvdb_id) style="padding-right:6px" @endif>
               @if($serie->tvdb_id)
              <a class="auto-download" onclick="window.SidePanel.autoDownload({{ $episode->id }})" title="{{ __('Auto Download') }}">
                <i class="glyphicon glyphicon-cloud-download"></i><strong style="display:flex">&nbsp;</strong>
              </a>
               @else
              <a class="auto-download btn btn-danger" href='https://github.com/SchizoDuckie/DuckieTV/wiki/FAQ#why-is-the-episode-find-a-torrent-button-not-working' target='_blank'>
                <i class="glyphicon glyphicon-ban-circle"></i><strong style="display:flex">&nbsp;</strong>
              </a>
               @endif
            </td>
            <td style="padding-right:15px">
              <a class="torrent-settings" style="text-decoration:none" href="javascript:void(0)" onclick="window.SidePanel.torrentSettings({{ $serie->id }})" title="{{ __('Settings for') }} {{ $serie->name }}">
                <i class="glyphicon glyphicon-cog"></i><strong style="display:flex">&nbsp;</strong>
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    @endif

    @if(settings('torrenting.enabled') && $episode->magnetHash)
    <tr>
      <td colspan="2" class="buttons">
             @include('torrents._mini_remote_control', ['torrent' => $torrent])
      </td>
    </tr>
    @endif
    
    @if(settings('torrenting.enabled') && ($episode->hasAired() || $episode->isLeaked()))
    <tr>
      <td colspan="2" class="buttons">
        <a href="javascript:void(0)" onclick="Subtitles.search({{ $episode->id }})">
            <i class="glyphicon glyphicon-text-width"></i><strong>{{ __('COMMON/find-subtitle/lbl') }}</strong>
        </a>
      </td>
    </tr>
    @endif
  </table>
</div>
<div class="rightpanel"></div>

