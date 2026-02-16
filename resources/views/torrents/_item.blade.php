<div class="torrent">
    <a href="#" class="{{ request()->route('infoHash') === $torrent->infoHash ? 'active' : '' }}" 
       data-sidepanel-expand="{{ route('torrents.show', $torrent->infoHash) }}">
        <strong>{{ $torrent->getName() }}</strong>
    </a>
    <div class="progress-striped progress" title="{{ $torrent->getProgress() }}%">
        <div class="progress-bar {{ !$torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-danger' : ($torrent->isStarted() && $torrent->getProgress() < 100 ? 'progress-bar-info' : (!$torrent->isStarted() && $torrent->getProgress() == 100 ? 'progress-bar-success' : 'progress-bar-warning')) }}" 
             style="width: {{ $torrent->getProgress() }}%">
            <span>{{ $torrent->getProgress() }}%</span>
        </div>
    </div>&nbsp;
</div>
