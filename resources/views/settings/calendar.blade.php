<div class="buttons">
    <h2>
        <span title="{{ settings('calendar.start_sunday', false) ? 'Week start: Sunday' : 'Week start: Monday' }}">
            <i class="glyphicon glyphicon-indent-{{ settings('calendar.start_sunday', false) ? 'left' : 'right' }}"></i>
        </span>
        Week Start Day
    </h2>
    <p>Toggle between Sunday/Monday for start day of the week below</p>
    <p><strong>Current Setting:</strong> {{ settings('calendar.start_sunday', false) ? 'Week start: Sunday' : 'Week start: Monday' }}</p>
    <a href="javascript:void(0)" onclick="alert('Toggle start day not implemented')" class="btn btn-{{ settings('calendar.start_sunday', false) ? 'success' : 'info' }}">
        <i class="glyphicon glyphicon-indent-{{ settings('calendar.start_sunday', false) ? 'right' : 'left' }}"></i> 
        {{ settings('calendar.start_sunday', false) ? 'Use Monday as start day-of-week' : 'Use Sunday as start day-of-week' }}
    </a>

    <hr class="setting-divider">

    <h2>
        <span title="{{ settings('calendar.mode') == 'date' ? 'Month' : 'Week' }}">
            <i class="glyphicon {{ settings('calendar.mode') == 'date' ? 'glyphicon-calendar' : 'glyphicon-th-list' }}"></i>
        </span>
        Calendar display mode
    </h2>
    <p>If you prefer the calendar to show only the current week/month, change it here</p>
    <p><strong>Current Setting:</strong> {{ settings('calendar.mode') == 'date' ? 'Month' : 'Week' }}</p>
    <a href="javascript:void(0)" onclick="alert('Toggle display mode not implemented')" class="btn btn-{{ settings('calendar.mode') == 'date' ? 'info' : 'success' }}">
        <i class="glyphicon glyphicon-{{ settings('calendar.mode') == 'date' ? 'th-list' : 'calendar' }}"></i> 
        {{ settings('calendar.mode') == 'date' ? 'Use one-week calendar' : 'Use month calendar' }}
    </a>

    <hr class="setting-divider">

    <h2>
        <span title="{{ settings('calendar.show_specials', true) ? 'Special episodes are shown' : 'Special episodes are hidden' }}">
            <i class="glyphicon {{ settings('calendar.show_specials', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
        </span>
        specials on calendar
    </h2>
    <p>Choose to show or hide all the special episodes from the calendar</p>
    <p><strong>Current Setting:</strong> {{ settings('calendar.show_specials', true) ? 'Special episodes are shown' : 'Special episodes are hidden' }}</p>
    <a href="javascript:void(0)" onclick="alert('Toggle specials not implemented')" class="btn btn-{{ settings('calendar.show_specials', true) ? 'danger' : 'success' }}">
        <i class="glyphicon glyphicon-{{ settings('calendar.show_specials', true) ? 'remove' : 'ok' }}"></i> 
        {{ settings('calendar.show_specials', true) ? 'Hide specials episodes' : 'Show special episodes' }}
    </a>

    <hr class="setting-divider">

    <h2>
        <span title="{{ settings('calendar.show_downloaded', true) ? 'Enabled' : 'Disabled' }}">
            <i class="glyphicon {{ settings('calendar.show_downloaded', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
        </span>
        Downloaded Episodes
    </h2>
    <p>Choose whether to highlight in green the downloaded episodes within the calendar</p>
    <p><strong>Current Setting:</strong> {{ settings('calendar.show_downloaded', true) ? 'Enabled' : 'Disabled' }}</p>
    <a href="javascript:void(0)" onclick="alert('Toggle downloaded not implemented')" class="btn btn-{{ settings('calendar.show_downloaded', true) ? 'danger' : 'success' }}">
        <i class="glyphicon glyphicon-{{ settings('calendar.show_downloaded', true) ? 'remove' : 'ok' }}"></i> 
        {{ settings('calendar.show_downloaded', true) ? 'Click to disable' : 'Click to enable' }}
    </a>

    <hr class="setting-divider">

    <h2>
        <span title="{{ settings('calendar.show_episode_numbers', true) ? 'Enabled' : 'Disabled' }}">
            <i class="glyphicon {{ settings('calendar.show_episode_numbers', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
        </span>
        Episode numbers on calendar
    </h2>
    <p>Choose to show or hide the episode numbers from the episode titles on the calendar.</p>
    <p><strong>Current Setting:</strong> {{ settings('calendar.show_episode_numbers', true) ? 'Enabled' : 'Disabled' }}</p>
    <a href="javascript:void(0)" onclick="alert('Toggle episode numbers not implemented')" class="btn btn-{{ settings('calendar.show_episode_numbers', true) ? 'danger' : 'success' }}">
        <i class="glyphicon glyphicon-{{ settings('calendar.show_episode_numbers', true) ? 'remove' : 'ok' }}"></i> 
        {{ settings('calendar.show_episode_numbers', true) ? 'Click to disable' : 'Click to enable' }}
    </a>
</div>
