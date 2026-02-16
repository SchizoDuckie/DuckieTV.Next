<div ng-controller="BackupCtrl">
    <h2>Backup</h2>
    <div class="buttons">
        <a class="btn btn-success" href="javascript:void(0)" onclick="alert('Backup functionality not yet implemented')">
            <i class="glyphicon glyphicon-floppy-save"></i> <span>Create Backup</span>
        </a>
    </div>

    <hr class="setting-divider">

    <h2>Auto-Backup</h2>
    <p>DuckieTV can automatically backup your database every X days.</p>
    <form name="autoBackupForm">
        <label for="autoBackup">Auto-backup:</label>
        <select name="autoBackup" id="autoBackup" onchange="alert('Auto-backup setting not yet implemented')">
            @foreach([
                ['value' => 0, 'name' => 'Off'],
                ['value' => 1, 'name' => 'Every day'],
                ['value' => 2, 'name' => 'Every 2 days'],
                ['value' => 3, 'name' => 'Every 3 days'],
                ['value' => 4, 'name' => 'Every 4 days'],
                ['value' => 5, 'name' => 'Every 5 days'],
                ['value' => 6, 'name' => 'Every 6 days'],
                ['value' => 7, 'name' => 'Every week']
            ] as $option)
                <option value="{{ $option['value'] }}" {{ settings('backup.auto') == $option['value'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
            @endforeach
        </select>
    </form>
    &nbsp;
    <p>Next auto-backup scheduled for: {{ settings('backup.next_schedule') ?? 'Not scheduled' }}</p>

    <hr class="setting-divider">

    <h2>Import Backup</h2>
    
    <div id="restore-status-message"></div>

    <form id="restoreForm">
        @csrf
        <div class="checkbox">
            <input type="checkbox" id="wipebeforeImport" name="wipe" value="1">
            <label for="wipebeforeImport">Wipe database before import</label>
        </div>

        <div style="position:relative">
            <div class="buttons">
                <a class="btn btn-success">
                    <i class="glyphicon glyphicon-floppy-open"></i> <span>Choose Backup to load</span>
                </a>
            </div>
            <input type="file" name="backup_file" id="backupInput"
                   onchange="BackupRestore.upload(this)"
                   style="position:absolute;opacity:0;width:100%;top:0;padding:0;height:100%;cursor:pointer" />
        </div>
    </form>

    <hr class="setting-divider">

    <h2>Wipe Database</h2>
    <p>This will delete all series, episodes, and settings! Use with caution.</p>
    <div class="buttons">
        <a class="btn btn-danger" href="javascript:void(0)" onclick="confirm('Are you sure you want to wipe the database? This cannot be undone!') && alert('Wipe functionality not yet implemented')">
            <i class="glyphicon glyphicon-trash"></i> <span>Wipe Database</span>
        </a>
    </div>

    <hr class="setting-divider">

    <h2>Refresh Database</h2>
    <p>This will re-fetch data for all series from Trakt.tv.</p>
    <div class="buttons">
        <a class="btn btn-danger" href="javascript:void(0)" onclick="confirm('Are you sure you want to refresh the entire database? This may take a while.') && alert('Refresh functionality not yet implemented')">
            <i class="glyphicon glyphicon-refresh"></i> <span>Refresh Database</span>
        </a>
    </div>
</div>

<!-- Restore Progress Modal -->
<div class="modal fade" id="restore-progress-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header dialog-header-wait">
                <h4 class="modal-title">Restoring Backup...</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <p><strong>Overall Progress</strong></p>
                        <div class="progress">
                          <div id="total-progress-bar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" style="width: 0%; min-width: 2em;">
                            0%
                          </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-xs-12">
                        <p><strong>Processing:</strong> <span id="current-show-name" style="color: #ccc;">Initializing...</span></p>
                        <div class="progress">
                          <div id="show-progress-bar" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" style="width: 0%; min-width: 2em;">
                            0%
                          </div>
                        </div>
                    </div>
                </div>

                <hr style="border-top-color: #444;">
                <p><strong>Activity Log</strong></p>
                <div id="restore-log" style="height: 300px; overflow-y: auto; background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 12px; border: 1px solid #444; white-space: pre-wrap;">Initializing...</div>
            </div>
            <!-- No footer/close button to prevent closing during restore -->
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="restore-confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header dialog-header-confirm">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="BackupRestore.cancelRestore()"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Confirm Restore</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore this backup?</p>
                <p class="text-warning"><i class="glyphicon glyphicon-warning-sign"></i> Current settings and favorites might be overwritten.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="BackupRestore.cancelRestore()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="BackupRestore.proceedWithRestore()">Restore</button>
            </div>
        </div>
    </div>
</div>
