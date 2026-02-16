<div class="torrent-mini-remote-control-progress progress-striped progress {{ $torrent->isStarted() ? 'active' : '' }}">
    {{ $slot ?? '' }}
    @if($torrent->getProgress() > 0)
        &nbsp;({{ $torrent->getProgress() }}%)
    @else
        &nbsp;<i class="glyphicon glyphicon-magnet spin" style='float:right' title="{{ __('COMMON/please-wait/tooltip') }}"></i>
    @endif
    <div class="progress-bar {{ !$torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-danger' : ($torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-info' : (!$torrent->isStarted() && $torrent->getProgress() == 100 ? 'progress-bar-success' : 'progress-bar-warning')) }}" 
         style="width: {{ $torrent->getProgress() }}%">&nbsp;
    </div>
</div>
