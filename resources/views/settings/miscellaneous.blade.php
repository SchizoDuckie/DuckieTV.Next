<div class="buttons">
    <h2>
        <span title="{{ settings('miscellaneous.watched_downloaded_paired', true) ? 'Enabled' : 'Disabled' }}">
            <i class="glyphicon {{ settings('miscellaneous.watched_downloaded_paired', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
        </span>
        Sync Watched & Downloaded
    </h2>

    <p>When enabled, marking an episode as watched will also mark it as downloaded, and vice versa.</p>

    <p><strong>Current Setting:</strong> {{ settings('miscellaneous.watched_downloaded_paired', true) ? 'Enabled' : 'Disabled' }}</p>
    
    <a href="javascript:void(0)" onclick="alert('Toggle setting not implemented')" class="btn btn-{{ settings('miscellaneous.watched_downloaded_paired', true) ? 'danger' : 'success' }}">
        <i class="glyphicon {{ settings('miscellaneous.watched_downloaded_paired', true) ? 'glyphicon-remove' : 'glyphicon-ok' }}"></i> 
        {{ settings('miscellaneous.watched_downloaded_paired', true) ? 'Disable' : 'Enable' }}
    </a>
</div>
