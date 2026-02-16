@php
    $torrentEnabled = settings('torrenting.enabled');
    $currentClient = settings('torrenting.client');
    $progressEnabled = settings('torrenting.progress');
    $autostopEnabled = settings('torrenting.autostop');
    $autostopAllEnabled = settings('torrenting.autostop_all');
    $labelEnabled = settings('torrenting.label');
    $chromiumEnabled = settings('torrenting.launch_via_chromium');
@endphp


<div class="buttons">
    <h2>
        <span title="{{ $torrentEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
            <i class="glyphicon {{ $torrentEnabled ? 'glyphicon-ok alert-success' : 'glyphicon-remove alert-danger' }}"></i>
        </span>
        {{ __('SETTINGS/TORRENT/hdr') }}
    </h2>

    <p>{{ $torrentEnabled ? __('SETTINGS/TORRENT/functions-hide/desc') : __('SETTINGS/TORRENT/functions-show/desc') }}</p>
    <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
        {{ $torrentEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
    </p>
    
    <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.enabled', {{ $torrentEnabled ? 'false' : 'true' }})" class="btn btn-{{ $torrentEnabled ? 'danger' : 'success' }}">
        <i class="glyphicon {{ $torrentEnabled ? 'glyphicon-remove-sign' : 'glyphicon-ok-sign' }}"></i> 
        {{ $torrentEnabled ? __('SETTINGS/TORRENT/functions-hide/btn') : __('SETTINGS/TORRENT/functions-show/btn') }}
    </a>

    <div style="{{ $torrentEnabled ? '' : 'display:none' }}">
        <hr class="setting-divider">
        
        <h2>{{ __('SETTINGS/TORRENT/choose-client/hdr') }}</h2>
        
        @foreach($supportedClients as $key => $client)
            <a href="javascript:void(0)" 
               onclick="setTorrentClient('{{ $key }}')" 
               data-client-key="{{ $key }}"
               class="choose-client btn {{ $currentClient == $key ? 'btn-success' : '' }}">
                <i class="torrentlogo {{ $client['css_class'] }}"></i> {{ $client['name'] }}
            </a>
        @endforeach

        <hr class="setting-divider">

        <h2>
            <span title="{{ $progressEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ $progressEnabled ? 'glyphicon-ok text-success' : 'glyphicon-remove text-danger' }}"></i>
            </span>
            {{ __('SETTINGS/TORRENT/progressbar/hdr') }}
        </h2>
        <p>{{ $progressEnabled ? __('SETTINGS/TORRENT/progressbar-hide/desc') : __('SETTINGS/TORRENT/progressbar-show/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            {{ $progressEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
        </p>
        <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.progress', {{ $progressEnabled ? 'false' : 'true' }})" class="btn btn-{{ $progressEnabled ? 'danger' : 'success' }}">
            <i class="glyphicon {{ $progressEnabled ? 'glyphicon-ban-circle' : 'glyphicon-minus' }}"></i> 
            {{ $progressEnabled ? __('SETTINGS/TORRENT/progressbar-hide/btn') : __('SETTINGS/TORRENT/progressbar-show/btn') }}
        </a>

        <hr class="setting-divider">

        <h2>
            <span title="{{ $autostopEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ $autostopEnabled ? 'glyphicon-ok text-success' : 'glyphicon-remove text-danger' }}"></i>
            </span>
            {{ __('SETTINGS/TORRENT/autostop/hdr') }}
        </h2>
        <p>{{ $autostopEnabled ? __('SETTINGS/TORRENT/autostop-disabled/desc') : __('SETTINGS/TORRENT/autostop-enabled/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            {{ $autostopEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
        </p>
        <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.autostop', {{ $autostopEnabled ? 'false' : 'true' }})" class="btn btn-{{ $autostopEnabled ? 'danger' : 'success' }}">
            <i class="glyphicon {{ $autostopEnabled ? 'glyphicon-ban-circle' : 'glyphicon-stop' }}"></i> 
            {{ $autostopEnabled ? __('SETTINGS/TORRENT/autostop-disabled/btn') : __('SETTINGS/TORRENT/autostop-enabled/btn') }}
        </a>

        <div style="{{ $autostopEnabled ? '' : 'display:none' }}">
            <hr class="setting-divider">
            <h2>
                <span title="{{ $autostopAllEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                    <i class="glyphicon {{ $autostopAllEnabled ? 'glyphicon-ok alert-success' : 'glyphicon-remove alert-danger' }}"></i>
                </span>
                {{ __('SETTINGS/TORRENT/autostopall/hdr') }}
            </h2>
            <p>{{ $autostopAllEnabled ? __('SETTINGS/TORRENT/autostopall-disabled/desc') : __('SETTINGS/TORRENT/autostopall-enabled/desc') }}</p>
            <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
                {{ $autostopAllEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
            </p>
            <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.autostop_all', {{ $autostopAllEnabled ? 'false' : 'true' }})" class="btn btn-{{ $autostopAllEnabled ? 'danger' : 'success' }}">
                <i class="glyphicon {{ $autostopAllEnabled ? 'glyphicon-ban-circle' : 'glyphicon-stop' }}"></i> 
                {{ $autostopAllEnabled ? __('SETTINGS/TORRENT/autostopall-disabled/btn') : __('SETTINGS/TORRENT/autostopall-enabled/btn') }}
            </a>
        </div>

        <hr class="setting-divider">
        
        <h2>
            <span title="{{ $labelEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ $labelEnabled ? 'glyphicon-tag alert-success' : 'glyphicon-remove alert-info' }}"></i>
            </span>
            {{ __('SETTINGS/TORRENT/label/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/TORRENT/label/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            {{ $labelEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
        </p>
        <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.label', @json(!$labelEnabled))" class="btn btn-{{ $labelEnabled ? 'info' : 'success' }}">
            <i class="glyphicon {{ $labelEnabled ? 'glyphicon-remove' : 'glyphicon-tag' }}"></i>&nbsp;
            {{ $labelEnabled ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

        <hr class="setting-divider">

        <h2>
            <span title="{{ $chromiumEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ $chromiumEnabled ? 'glyphicon-ok alert-success' : 'glyphicon-remove alert-danger' }}"></i>
            </span>
            {{ __('SETTINGS/TORRENT/chromium/hdr') }}
        </h2>
        <p>{{ $chromiumEnabled ? __('SETTINGS/TORRENT/chromium-disabled/desc') : __('SETTINGS/TORRENT/chromium-enabled/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            {{ $chromiumEnabled ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
        </p>
        <a href="javascript:void(0)" onclick="toggleTorrentSetting('torrenting.launch_via_chromium', @json(!$chromiumEnabled))" class="btn btn-{{ $chromiumEnabled ? 'danger' : 'success' }}">
            <i class="glyphicon {{ $chromiumEnabled ? 'glyphicon-remove' : 'glyphicon-ok' }}"></i> 
            {{ $chromiumEnabled ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

    </div>
</div>
