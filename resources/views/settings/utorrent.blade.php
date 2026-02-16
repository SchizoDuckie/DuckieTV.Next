<div class="header">
    <h2><i class="glyphicon glyphicon-magnet"></i> {{ $supportedClients[$section]['name'] ?? ucfirst($section) }} {{ __('COMMON/integration/hdr') }}</h2>
</div>
<div class="body" id="utorrent-settings-body">
    <div id="connection-status">
        <p>
            <strong>{{ __('COMMON/status/hdr') }}:</strong>
            <span id="utorrent-auth-status">{{ settings('utorrent.token') ? __('SETTINGS/UTORRENT/is-authenticated/desc') : __('SETTINGS/UTORRENT/is-not-authenticated/desc') }}</span>
        </p>
    </div>
    
    <div class="alert alert-info" style="margin:10px">
        <strong style="color:red">
            For this API to work you need to be running &micro;Torrent (Windows) versions 3.3.x or newer.<br />
            From &micro;Torrent 3.5.3 onwards, you will need to set port 10000 on its WEBUI preferences.<br />
            <p><a href='https://github.com/SchizoDuckie/DuckieTV/wiki/Setting-up-uTorrent-API-with-DuckieTV#configure-%C2%B5torrent-353-and-newer' target='_blank' style="color:red">Click here to read the &micro;Torrent 3.5.3 (or newer) API Setup Wiki</a></p>
            Or you can connect via the &micro;Torrent WEBUI instead. 
            <p><a href='https://github.com/SchizoDuckie/DuckieTV/wiki/Setting-up-uTorrent-Web-UI-with-DuckieTV' target='_blank' style="color:red">Click here to read the &micro;Torrent WEBUI Setup Wiki</a></p>
        </strong>
    </div>

    <hr class="setting-divider">
  
    <p id="utorrent-token-info" style="display: {{ settings('utorrent.token') ? 'block' : 'none' }}">
        <span>{{ __('SETTINGS/UTORRENT/is-authenticated/desc') }}</span> <em id="utorrent-token-display">{{ settings('utorrent.token') }}</em>.<br />
        <span>{{ __('SETTINGS/UTORRENT/is-authenticated/desc2') }}</span>
    </p>

    <a class='btn btn-small btn-warning' id="btn-utorrent-clear" style="display: {{ settings('utorrent.token') ? 'inline-block' : 'none' }}" onclick="uTorrentSettings.removeToken()"><i class="glyphicon glyphicon-ban-circle"></i> <span>{{ __('SETTINGS/UTORRENT/clear-authority/btn') }}</span></a>
    <a class='btn btn-small btn-success' id="btn-utorrent-connect" style="display: {{ settings('utorrent.token') ? 'none' : 'inline-block' }}" onclick="uTorrentSettings.connect()"><i class="glyphicon glyphicon-refresh"></i> <span>{{ __('SETTINGS/UTORRENT/connect-to-utorrent/btn') }}</span></a>

    <hr class="setting-divider">

    <form data-section="torrent">
        <h2>
            <span id="streaming-icon-container">
                <i class="glyphicon {{ settings('torrenting.streaming') ? 'glyphicon-ok alert-success' : 'glyphicon-remove alert-danger' }}"></i>
            </span>
            <li>{{ __('SETTINGS/UTORRENT/streaming/hdr') }}</li>
        </h2>

        <p id="streaming-desc">{{ settings('torrenting.streaming') ? __('SETTINGS/UTORRENT/streaming-disabled/desc') : __('SETTINGS/UTORRENT/streaming-enabled/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            <span id="streaming-status">{{ settings('torrenting.streaming') ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</span>
        </p>

        <input type="checkbox" name="torrenting.streaming" id="input-streaming" {{ settings('torrenting.streaming') ? 'checked' : '' }} style="display:none" onchange="uTorrentSettings.toggle('streaming')">
        <a onclick="document.getElementById('input-streaming').click()" class="btn {{ settings('torrenting.streaming') ? 'btn-danger' : 'btn-success' }}" id="btn-streaming">
            <i class="glyphicon {{ settings('torrenting.streaming') ? 'glyphicon-ban-circle' : 'glyphicon-film' }}"></i> 
            <span id="btn-streaming-text">{{ settings('torrenting.streaming') ? __('SETTINGS/UTORRENT/streaming-disabled/btn') : __('SETTINGS/UTORRENT/streaming-enabled/btn') }}</span>
        </a>

        <hr class="setting-divider">

        <h2>
            <span id="directory-icon-container">
                <i class="glyphicon {{ settings('torrenting.directory') ? 'glyphicon-ok alert-success' : 'glyphicon-remove alert-danger' }}"></i>
            </span>
            <li>{{ __('SETTINGS/UTORRENT/directory/hdr') }}</li>
        </h2>

        <p id="directory-desc">{{ settings('torrenting.directory') ? __('SETTINGS/UTORRENT/directory-folder-hide/desc') : __('SETTINGS/UTORRENT/directory-folder-show/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
            <span id="directory-status">{{ settings('torrenting.directory') ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</span>
        </p>

        <input type="checkbox" name="torrenting.directory" id="input-directory" {{ settings('torrenting.directory') ? 'checked' : '' }} style="display:none" onchange="uTorrentSettings.toggle('directory')">
        <a onclick="document.getElementById('input-directory').click()" class="btn {{ settings('torrenting.directory') ? 'btn-danger' : 'btn-success' }}" id="btn-directory">
            <i class="glyphicon {{ settings('torrenting.directory') ? 'glyphicon-ban-circle' : 'glyphicon-folder-open' }}"></i> 
            <span id="btn-directory-text">{{ settings('torrenting.directory') ? __('SETTINGS/UTORRENT/directory-folder-hide/btn') : __('SETTINGS/UTORRENT/directory-folder-show/btn') }}</span>
        </a>
    </form>

    <hr class="settings-divider">
    <h2>{{ __('COMMON/remove-ads/hdr') }}</h2>
    <img src="{{ asset('img/xtodaz.png') }}" style='width:365px'>
    <p>{{ __('COMMON/remove-ads/desc') }}</p>
    <a target="_blank" href="http://schizoduckie.github.io/PimpMyuTorrent/?from=DuckieTV#" class="btn btn-success">{{ __('COMMON/remove-ads/btn') }}</a>
</div>

<script>
    window.uTorrentSettings = {
        removeToken() {
            localStorage.removeItem('utorrent.token');
            document.getElementById('utorrent-token-info').style.display = 'none';
            document.getElementById('btn-utorrent-clear').style.display = 'none';
            document.getElementById('btn-utorrent-connect').style.display = 'inline-block';
            document.getElementById('utorrent-auth-status').innerText = "{{ __('SETTINGS/UTORRENT/is-not-authenticated/desc') }}";
        },
        connect() {
            localStorage.removeItem('utorrent.preventconnecting');
            window.location.reload(); 
        },
        toggle(type) {
            Settings.save('torrent').then(() => {
                if (window.SidePanel) {
                    window.SidePanel.update('/settings/utorrent');
                } else {
                    window.location.reload();
                }
            });
        }
    };
</script>
