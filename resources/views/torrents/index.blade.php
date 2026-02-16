<div class="leftpanel torrent-client" data-url="{{ route('torrents.index') }}">
    <div>
        <h2>DuckieTorrent {{ $client ? $client->getName() : 'Client' }} <span>{{ __('TORRENTCLIENT/hdr') }}</span></h2>

        <!-- top right settings hotbuttons -->
        <div class="settingsbtn" style="float:left">
            <a href="#" data-sidepanel-show="{{ route('settings.show', 'torrent') }}">
                <i class="glyphicon glyphicon-cog"></i> <span>{{ __('TORRENTCLIENT/choose-client/glyph') }}</span>
            </a>
        </div>
        @if($client)
        <div class="settingsbtn" style="float:right">
            <a href="#" data-sidepanel-show="{{ route('settings.show', strtolower(str_replace([' 4.1+', ' web ui'], '', $client->getName()))) }}">
                <i class="glyphicon glyphicon-cog"></i> <span>{{ $client->getName() }}</span> <span>{{ __('COMMON/settings/hdr') }}</span>
            </a>
        </div>
        @endif
        &nbsp;

        @php 
            $isConnected = $client && $client->isConnected();
        @endphp

        <!-- logo spinner that hides when connected -->
        @if(!$client)
            <div style='padding:40px; text-align:center'>
                <img src="{{ asset('img/torrentclients/none.png') }}" class="spin" style="width:100%;height:100%">
                <h2>{{ __('TORRENTCLIENT/no-client-configured/hdr') }}</h2>
            </div>
        @elseif(!$isConnected)
            <div style='padding:40px; text-align:center'>
                <img src="{{ asset('img/torrentclients/' . strtolower(str_replace([' 4.1+', ' web ui'], '', $client->getName())) . '.png') }}" class="spin" style="width:100%;height:100%">
                <h2>{{ __('TORRENTCLIENT/connecting/hdr') }} {{ $client->getName() }}</h2>
                <strong style=' display:block; text-align:center;'>{{ __('TORRENTCLIENT/please-wait/lbl') }}</strong>
            </div>
        @else
            <h4 id="getTorrentsCount">
                <span>{{ __('TORRENTCLIENT/torrents-found/hdr') }}</span> {{ count($torrents) }}
                <button type="button" class="close pull-right" onclick="SidePanel.hide()" title="{{ __('COMMON/close/btn') }} DuckieTorrent">&times;</button>
            </h4>
        @endif
    </div>

    <!-- torrent list -->
    @if($client && $isConnected)
        @foreach($torrents as $torrent)
            @include('torrents._item', ['torrent' => $torrent])
        @endforeach
    @endif
</div>
<div class="rightpanel torrent-client"></div>
