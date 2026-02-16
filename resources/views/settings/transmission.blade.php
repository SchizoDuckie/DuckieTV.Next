<div class="header">
    <h2><i class="glyphicon glyphicon-magnet"></i> {{ $supportedClients[$section]['name'] ?? ucfirst($section) }} {{ __('COMMON/integration/hdr') }}</h2>
</div>
<div class="body">
    <div class="alert alert-info" style="text-align: center">
        <img src="{{ asset('img/torrentclients/transmission-colored.png') }}" style='width:200px; margin: 0 auto; display: block;'>
        <p><a href='https://github.com/SchizoDuckie/DuckieTV/wiki/Setting-up-Transmission-with-DuckieTV' target='_blank'>{{ __('COMMON/set-up-instructions/lbl') }}</a></p>
    </div>

    <div id="connection-status">
        <p class="status-connected" style="display: none;">
            <strong>{{ __('COMMON/status/hdr') }}:</strong>
            <span>{{ __('COMMON/connected/lbl') }}</span> {{ __('Transmission API') }} @ <span class="server-info"></span>
        </p>
        <p class="status-not-connected">
            <strong>{{ __('COMMON/status/hdr') }}:</strong>
            <span>{{ __('COMMON/not-connected/lbl') }}</span>
        </p>
        <p class="status-error" style="display: none;">
            <strong>{{ __('COMMON/error/hdr') }}:</strong>
            <span class="error-message"></span>
        </p>
    </div>

    <form method="POST" action="{{ route('settings.update', 'torrent') }}" data-section="torrent" onsubmit="Settings.test('torrent'); return false;">
        @csrf
        
        <div class="form-group">
            <label>{{ __('COMMON/address/lbl') }}</label>
            <input type="url" name="transmission.server" class="form-control" value="{{ settings('transmission.server') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('COMMON/port/lbl') }}</label>
            <input type="number" name="transmission.port" class="form-control" value="{{ settings('transmission.port') }}" required min="0" max="65535">
        </div>

        <div class="form-group">
            <label>{{ __('COMMON/path/lbl') }}</label>
            <input type="text" name="transmission.path" class="form-control" value="{{ settings('transmission.path') }}">
        </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="transmission.use_auth" id="transmission_use_auth" {{ settings('transmission.use_auth') ? 'checked' : '' }} onchange="document.getElementById('transmission_auth_fields').style.display = this.checked ? 'block' : 'none'"> {{ __('COMMON/authentication/lbl') }}
            </label>
        </div>

        <div id="transmission_auth_fields" style="display: {{ settings('transmission.use_auth') ? 'block' : 'none' }}">
            <div class="form-group">
                <label>{{ __('COMMON/username/lbl') }}</label>
                <input type="text" name="transmission.username" class="form-control" value="{{ settings('transmission.username') }}">
            </div>

            <div class="form-group">
                <label>{{ __('COMMON/password/lbl') }}</label>
                <input type="password" name="transmission.password" class="form-control" value="{{ settings('transmission.password') }}">
            </div>
        </div>

         <div class="checkbox">
            <label>
                <input type="checkbox" name="transmission.progressX100" {{ settings('transmission.progressX100') ? 'checked' : '' }}> %x100
            </label>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('COMMON/test-save/btn') }}</button>
    </form>
</div>
