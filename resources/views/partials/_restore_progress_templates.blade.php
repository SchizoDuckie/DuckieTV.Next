{{-- resources/views/partials/_restore_progress_templates.blade.php --}}

<!-- Template for the main Modal view -->
<template id="restore-progress-modal-template">
    <div class="restore-progress-detailed">
        <!-- Scrolling Thumbnail Track -->
        <div class="restore-thumbnails-container" style="height: 120px; overflow: hidden; margin-bottom: 15px; background: #000; position: relative;">
            <div class="restore-thumbnails-track" style="display: flex; gap: 10px; position: absolute; left: 0; transition: left 0.5s ease-in-out;">
                <!-- Posters will be added here -->
            </div>
        </div>

        <p class="restore-status-text">Initializing restore process...</p>
        
        <div class="progress progress-striped active">
            <div class="progress-bar progress-bar-info main-progress-bar" style="width: 0%"></div>
        </div>

        <div class="restore-show-progress" style="margin-top: 15px; display: none;">
            <p class="restore-show-text" style="font-size: 0.9em; margin-bottom: 5px;"></p>
            <div class="progress progress-striped active" style="height: 10px;">
                <div class="progress-bar progress-bar-success show-progress-bar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Failure List (Hidden by default) -->
        <div class="restore-failures-container" style="margin-top: 15px; display: none;">
            <p style="color: #ff5252; font-size: 0.9em; font-weight: bold; margin-bottom: 5px;"><i class="glyphicon glyphicon-warning-sign"></i> Failed Imports:</p>
            <ul class="restore-failed-items" style="list-style: none; padding: 0; margin: 0; font-size: 0.8em; color: #ff8a80; max-height: 100px; overflow-y: auto;">
                <!-- Failed items will be added here -->
            </ul>
        </div>

        <div class="restore-logs" style="margin-top: 15px; height: 100px; overflow-y: auto; background: #111; padding: 10px; font-family: monospace; font-size: 0.8em; color: #aaa; border: 1px solid #333; text-align: left;">
            <!-- Logs will be appended here -->
        </div>

        <div class="restore-actions" style="margin-top: 15px; text-align: right;">
            <button type="button" class="btn btn-danger btn-sm btn-stop-restore">
                <i class="glyphicon glyphicon-stop"></i> Stop Restore
            </button>
        </div>
    </div>
</template>

<!-- Template for the minimized Mini view -->
<template id="restore-progress-mini-template">
    <div class="restore-mini-progress" style="position: fixed; bottom: 20px; right: 20px; width: 300px; background: #222; border: 1px solid #444; border-radius: 4px; padding: 10px; z-index: 9999; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
        <!-- Small thumbnail track for mini view -->
        <div class="restore-thumbnails-track-mini" style="display: flex; gap: 5px; height: 40px; overflow: hidden; margin-bottom: 8px;">
            <!-- Posters will be added here -->
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
             <strong style="color: #eee; font-size: 0.9em;"><i class="glyphicon glyphicon-time"></i> Restore in Progress</strong>
             <span class="percent-label" style="color: #00bcd4; font-weight: bold; font-size: 0.9em;">0%</span>
        </div>
        <div class="progress" style="height: 8px; margin-bottom: 0;">
            <div class="progress-bar progress-bar-info main-progress-bar" style="width: 0%"></div>
        </div>
        <p class="mini-status-text" style="font-size: 0.8em; color: #999; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></p>
    </div>
</template>

<style>
    .restore-logs::-webkit-scrollbar {
        width: 6px;
    }
    .restore-logs::-webkit-scrollbar-track {
        background: #111;
    }
    .restore-logs::-webkit-scrollbar-thumb {
        background: #333;
    }
    .restore-logs div {
        margin-bottom: 2px;
    }
    .restore-mini-progress:hover {
        background: #2a2a2a;
        border-color: #666;
    }
</style>
