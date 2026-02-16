<div class="header">
    <h2><i class="glyphicon glyphicon-magnet"></i> {{ $supportedClients[$section]['name'] ?? ucfirst($section) }} {{ __('COMMON/integration/hdr') }}</h2>
</div>
<div class="body">
    <div class="alert alert-info" style="text-align: center">
        <img src="{{ asset('img/torrentclients/qbittorrent-colored.png') }}" style='width:200px; margin: 0 auto; display: block;'>
        <p><a href='https://github.com/SchizoDuckie/DuckieTV/wiki/Setting-up-qBitTorrent-with-DuckieTV' target='_blank'>{{ __('COMMON/set-up-instructions/lbl') }}</a></p>
    </div>

    <div id="connection-status">
        <p>
            <strong>{{ __('COMMON/status/hdr') }}:</strong>
            <span>{{ __('COMMON/not-connected/lbl') }}</span>
        </p>
    </div>

    <form method="POST" action="{{ route('settings.update', 'torrent') }}" data-section="torrent" onsubmit="Settings.test('torrent'); return false;">
        @csrf
        
        <div class="form-group">
            <label>{{ __('COMMON/address/lbl') }}</label>
            <input type="url" name="qbittorrent32plus.server" class="form-control" value="{{ settings('qbittorrent32plus.server') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('COMMON/port/lbl') }}</label>
            <input type="number" name="qbittorrent32plus.port" class="form-control" value="{{ settings('qbittorrent32plus.port') }}" required min="0" max="65535">
        </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="qbittorrent32plus.use_auth" id="qbittorrent_use_auth" {{ settings('qbittorrent32plus.use_auth') ? 'checked' : '' }} onchange="document.getElementById('qbittorrent_auth_fields').style.display = this.checked ? 'block' : 'none'"> {{ __('COMMON/authentication/lbl') }}
            </label>
        </div>

        <div id="qbittorrent_auth_fields" style="display: {{ settings('qbittorrent32plus.use_auth') ? 'block' : 'none' }}">
            <div class="form-group">
                <label>{{ __('COMMON/username/lbl') }}</label>
                <input type="text" name="qbittorrent32plus.username" class="form-control" value="{{ settings('qbittorrent32plus.username') }}">
            </div>

            <div class="form-group">
                <label>{{ __('COMMON/password/lbl') }}</label>
                <input type="password" name="qbittorrent32plus.password" class="form-control" value="{{ settings('qbittorrent32plus.password') }}">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('COMMON/test-save/btn') }}</button>
    </form>
</div>
