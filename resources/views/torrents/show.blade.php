<div class="torrent-details" data-info-hash="{{ $torrent->getInfoHash() }}">
    <h1><span>{{ __('COMMON/torrent-details/lbl') }}</span>
        <span><button type="button" class="close" onclick="window.SidePanel.hide()" style="margin-top:15px" title="{{ __('COMMON/close/btn') }} {{ __('COMMON/torrent-details/lbl') }}">&times;</button></span>
    </h1>

    @if(!$torrent)
        <div class="alert alert-warning">
            Torrent details not found or client disconnected.
        </div>
    @else
        <h2>{{ $torrent->getName() }}</h2>

        <div class="dg">
            @php
                $progress = $torrent->getProgress();
                $speed = $torrent->getDownloadSpeed();
                $speedKB = floor(($speed / 1000) * 10) / 10;
            @endphp
            
            @include('partials._gauge', [
                'value' => $speedKB,
                'max' => 10,
                'relative' => true,
                'units' => 'kB/s',
                'title' => 'Download Speed',
                'color' => '#00FF00',
                'colorEnd' => '#FF0000',
                'id' => 'gauge1'
            ])

            @include('partials._gauge', [
                'value' => $progress,
                'max' => 100,
                'units' => '%',
                'title' => 'Progress',
                'color' => '#0000FF',
                'colorEnd' => '#00FF00',
                'id' => 'gauge2'
            ])
        </div>

        <div class="buttons">
            <div class="torrent-mini-remote-control" style="border:0;">
                @php
                    $clientName = strtolower($client->getName());
                    $clientIcon = '';
                    if (str_contains($clientName, 'transmission')) $clientIcon = 'T';
                    elseif (str_contains($clientName, 'utorrent')) $clientIcon = 'U';
                    elseif (str_contains($clientName, 'qbittorrent')) $clientIcon = 'Q';
                    elseif (str_contains($clientName, 'deluge')) $clientIcon = 'D';
                    elseif (str_contains($clientName, 'rtorrent')) $clientIcon = 'R';
                    elseif (str_contains($clientName, 'aria2')) $clientIcon = 'A';
                @endphp
                @if($clientIcon)
                    <div class="client-logo" style="float: left; font-family: 'bebasbold'; font-size: 40px; line-height: 1; opacity: 0.8; margin-right: 20px;">{{ $clientIcon }}</div>
                @else
                    <i class="glyphicon glyphicon-magnet"></i>
                @endif
                <div class="torrent-controls">
                    <!-- start button -->
                    <a href="#" onclick="TorrentClient.start('{{ $torrent->infoHash }}'); return false;" style='display:inline-block'>
                        <i class="glyphicon glyphicon-play"></i>
                        <span>{{ __('COMMON/start/btn') }}</span>
                    </a>
                    <!-- pause button -->
                    <a href="#" onclick="TorrentClient.pause('{{ $torrent->infoHash }}'); return false;" style='display:inline-block'>
                        <i class="glyphicon glyphicon-pause"></i>
                        <span>{{ __('COMMON/pause/btn') }}</span>
                    </a>
                    <!-- stop button -->
                    <a href="#" onclick="TorrentClient.stop('{{ $torrent->infoHash }}'); return false;" style='display:inline-block'>
                        <i class="glyphicon glyphicon-stop"></i>
                        <span>{{ __('COMMON/stop/btn') }}</span>
                    </a>
                    <!-- remove button -->
                    <a href="#" onclick="if(confirm('Are you sure you want to remove this torrent?')){ TorrentClient.remove('{{ $torrent->infoHash }}'); window.SidePanel.hide(); } return false;" style='display:inline-block'>
                        <i class="glyphicon glyphicon-eject"></i>
                        <span>{{ __('COMMON/remove/btn') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- torrent files list with subtitle dialog links -->
        <div class="torrent-file-details">
            @php 
                $files = $torrent->getFiles();
            @endphp
            @if(count($files) > 0)
                @foreach($files as $file)
                    <p>
                        {{ is_array($file) ? ($file['name'] ?? 'Unknown') : (isset($file->name) ? $file->name : 'Unknown') }}
                    </p>
                @endforeach
            @else
                <p>No files found.</p>
            @endif
        </div>
    @endif
</div>
