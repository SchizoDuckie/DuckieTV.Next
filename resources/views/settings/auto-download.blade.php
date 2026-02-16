<div class="buttons">
    <h2>
        <span title="{{ settings('autodownload.enabled') ? 'Enabled' : 'Disabled' }}">
            <i class="glyphicon {{ settings('autodownload.enabled') ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
        </span>
        Auto-Download
    </h2>

    <p>{{ settings('autodownload.enabled') ? 'Auto-Download is active. DuckieTV will search for episodes regularly.' : 'Auto-Download is disabled. You must manually search for episodes.' }}</p>
    <p><strong>Current Setting:</strong> {{ settings('autodownload.enabled') ? 'Enabled' : 'Disabled' }}</p>
    
    <a href="javascript:void(0)" onclick="alert('Toggle auto-download not implemented')" class="btn btn-{{ settings('autodownload.enabled') ? 'danger' : 'success' }}">
        <i class="glyphicon {{ settings('autodownload.enabled') ? 'glyphicon-remove-sign' : 'glyphicon-cloud-download' }}"></i> 
        {{ settings('autodownload.enabled') ? 'Disable Auto-Download' : 'Enable Auto-Download' }}
    </a>

    <hr class="setting-divider">

    <div class="autodownload">
        <h2>Check Frequency</h2>
        <p>DuckieTV checks for new episodes every <strong>{{ settings('autodownload.period', 6) }}</strong> hours.<br>Default: 6 hours.</p>

        <form>
            Update Frequency (Hours): <input type="number" name="period" value="{{ settings('autodownload.period', 6) }}" min="1" max="21" required />
            <a class="btn btn-success" href="javascript:void(0)" onclick="alert('Save period not implemented')" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; <span>Save</span>
            </a>
        </form>

        <hr class="setting-divider">

        <h2>Auto-Download Delay</h2>
        <p>Wait before downloading to allow for better quality releases (e.g. 2 hours).</p>

        <form>
            Delay (Days Hours:Minutes): <input type="text" name="delay" value="{{ settings('autodownload.delay', '0 02:00') }}" pattern="([0-9]){1,2}(\s){1}([0-2][0-9]){1}([:]){1}([0-5][0-9]){1}" style="width: 100px" />
            <a class="btn btn-success" href="javascript:void(0)" onclick="alert('Save delay not implemented')" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; <span>Save</span>
            </a>
        </form>
    </div>
</div>
