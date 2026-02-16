<h2>Trakt.TV
  <span title="{{ settings('trakttv.sync') ? 'Enabled' : 'Disabled' }}">
  	<i class="glyphicon {{ settings('trakttv.sync') ? 'glyphicon-ok' : 'glyphicon-remove' }}" style="font-size:22px"></i>
  </span>
</h2>

{{-- Validated Credentials View --}}
@if(settings('trakttv.token'))
    <div class="buttons">
        <div style="text-align:center">
            <p class="alert alert-success">DuckieTV is authorized to read/write to your Trakt.TV account.</p>
            <p><strong>Username:</strong> {{ settings('trakttv.username') }}</p>
        </div>

        <hr class="setting-divider">

        <h2>
            <span title="{{ settings('trakttv.sync_downloaded') ? 'Enabled' : 'Disabled' }}">
                <i class="glyphicon {{ settings('trakttv.sync_downloaded') ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            Sync Downloaded Status
        </h2>

        <p>When enabled, episodes marked as 'Downloaded' in DuckieTV will be marked as 'Collected' on Trakt.TV.</p>

        <p><strong>Current Setting:</strong> {{ settings('trakttv.sync_downloaded') ? 'Enabled' : 'Disabled' }}</p>
        <a href="javascript:void(0)" onclick="alert('Toggle sync downloaded not implemented')" class="btn btn-{{ settings('trakttv.sync_downloaded') ? 'danger' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('trakttv.sync_downloaded') ? 'remove' : 'ok' }}" ></i> {{ settings('trakttv.sync_downloaded') ? 'Disable' : 'Enable' }}
        </a>

        <hr class="setting-divider">

        <h2>Update Period</h2>
        <p>DuckieTV checks for updates every <strong>{{ settings('trakttv.update_period', 24) }}</strong> hours.<br>Default: 24 hours.</p>

        <form>
            Update Frequency (Hours): <input type='number' name="period" value="{{ settings('trakttv.update_period', 24) }}" min="1" max="24" />
            <a class="btn btn-success" href="javascript:void(0)" onclick="alert('Save period not implemented')" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; <span>Save</span>
            </a>
        </form>
    </div>
@else
    {{-- Not Authenticated View --}}
    <div style="text-align:center">
        <p>Connect your Trakt.TV account to sync your collection across devices and backup your watched history.</p>
        <a href="javascript:void(0)" onclick="alert('Trakt OAuth flow not implemented')" class="btn btn-success btn-lg">
            <i class="glyphicon glyphicon-user"></i> Authenticate with Trakt.TV
        </a>
    </div>
@endif
