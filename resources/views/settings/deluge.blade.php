<div class="header">
    <h2><i class="glyphicon glyphicon-magnet"></i> {{ $supportedClients[$section]['name'] ?? ucfirst($section) }} {{ __('COMMON/integration/hdr') }}</h2>
</div>
<div class="body">
    <div class="alert alert-info" style="text-align: center">
        <img src="{{ asset('img/torrentclients/deluge-colored.png') }}" style='width:200px; margin: 0 auto; display: block;'>
        <p><a href='https://github.com/SchizoDuckie/DuckieTV/wiki/Setting-up-Deluge-with-DuckieTV' target='_blank'>{{ __('COMMON/set-up-instructions/lbl') }}</a></p>
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
            <input type="url" name="deluge.server" class="form-control" value="{{ settings('deluge.server') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('COMMON/port/lbl') }}</label>
            <input type="number" name="deluge.port" class="form-control" value="{{ settings('deluge.port') }}" required min="0" max="65535">
        </div>

        {{-- Deluge typically requires only password for WebUI --}}
        <div class="form-group">
            <label>{{ __('COMMON/password/lbl') }}</label>
            <input type="password" name="deluge.password" class="form-control" value="{{ settings('deluge.password') }}">
        </div>

        <button type="submit" class="btn btn-primary">{{ __('COMMON/test-save/btn') }}</button>
    </form>
</div>
