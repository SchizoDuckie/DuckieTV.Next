@if($torrent)

<torrent-remote-control>
  <div class="torrent-mini-remote-control">
    <i class="glyphicon glyphicon-magnet" style="{{ !$torrent->getProgress() ? 'display:none' : '' }}"></i>
    <i class="glyphicon glyphicon-magnet spin" style="{{ $torrent->getProgress() ? 'display:none' : '' }}" title="{{ __('COMMON/please-wait/tooltip') }}"></i>
    <span>{{ __('COMMON/torrent/hdr') }}</span>
    <div>
      <a href="javascript:void(0)" onclick="TorrentClient.start('{{ $torrent->infoHash }}'); return false;" style="display:inline-block"><i class="glyphicon glyphicon-play"></i><span>{{ __('COMMON/start/btn') }}</span></a>
      <a href="javascript:void(0)" onclick="TorrentClient.pause('{{ $torrent->infoHash }}'); return false;" style="display:inline-block"><i class="glyphicon glyphicon-pause"></i><span>{{ __('COMMON/pause/btn') }}</span></a>
      <a href="javascript:void(0)" onclick="TorrentClient.stop('{{ $torrent->infoHash }}'); return false;" style="display:inline-block"><i class="glyphicon glyphicon-stop"></i><span>{{ __('COMMON/stop/btn') }}</span></a>
      <a href="javascript:void(0)" onclick="if(confirm('{{ __('Are you sure you want to remove this torrent?') }}')){ TorrentClient.remove('{{ $torrent->infoHash }}'); SidePanel.hide(); } return false;" style="display:inline-block"><i class="glyphicon glyphicon-eject"></i><span>{{ __('COMMON/remove/btn') }}</span></a>

      @if(settings('torrenting.streaming'))
      <a href="javascript:void(0)" style="margin-top:5px" onclick="window.Toast.info('Streaming not implemented yet.')">
        <i class="glyphicon glyphicon-bullhorn"></i> <span>{{ __('SIDEPANEL/TORRENTRC/stream-play/glyph') }}</span>
      </a>  
      @endif      @if(settings('torrenting.streaming'))
      <a href="javascript:void(0)" style="margin-top:5px" onclick="window.Toast.info('Streaming not implemented yet.')">
        <i class="glyphicon glyphicon-bullhorn"></i> <span>{{ __('SIDEPANEL/TORRENTRC/stream-play/glyph') }}</span>
      </a>  
      @endif

      <a href="javascript:void(0)" onclick="document.getElementById('torrent-files-{{ $torrent->infoHash }}').style.display = (document.getElementById('torrent-files-{{ $torrent->infoHash }}').style.display === 'none' ? 'block' : 'none')" style="margin-top:5px">
        <i class="glyphicon glyphicon-folder-open" style="padding-right:10px"></i><span>{{ __('COMMON/show-files/btn') }}</span>
      </a>

      <div id="torrent-files-{{ $torrent->infoHash }}" style="display: none; padding-left: 10px">
        @foreach($torrent->getFiles() as $file)
        <p class="torrent-file" style="word-break: break-all;">
            {{ is_array($file) ? ($file['name'] ?? 'Unknown') : ($file->name ?? 'Unknown') }}
        </p>
        @endforeach
      </div>
    </div>
    <p style="margin-bottom: 3px"><strong>{{ __('SIDEPANEL/TORRENTRC/download-progress/lbl') }}</strong></p>

    <small style="display: block; text-align: left; padding-bottom: 2px; word-break: break-all;">{{ $torrent->getName() }}</small>
    <div class="torrent-mini-remote-control-progress progress-striped progress {{ $torrent->isStarted() ? 'active' : '' }}" title="{{ $torrent->getProgress() }}%">
      <span></span>@if($torrent->getProgress() > 0 && $torrent->getProgress() != '')<span>&nbsp;({{ $torrent->getProgress() }}%)</span>@endif
      <div class="progress-bar {{ !$torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-danger' : ($torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-info' : (!$torrent->isStarted() && $torrent->getProgress() == 100 ? 'progress-bar-success' : 'progress-bar-warning')) }} {{ $torrent->isDownloaded() ? 'downloaded' : '' }}" style="width: {{ $torrent->getProgress() ?: 0 }}%">&nbsp;
      </div>
    </div>
  </div>
</torrent-remote-control>
@else
<div class="torrent-mini-remote-control">
    <p>{{ __('SIDEPANEL/TORRENTRC/torrent-not-found/lbl') }}</p>
</div>
@endif
