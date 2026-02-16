<div class="leftpanel">
    <button type="button" class="close" onclick="SidePanel.close()" title="Close Settings">&times;</button>
    <h2 style="margin-bottom:3px">
        <i class="glyphicon glyphicon-cog" style="top:4px;margin-right:2px"></i> <strong>SETTINGS</strong>
    </h2>

    <table class="buttons" width="100%" border="0">
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'display') }}">
                    <i class="glyphicon glyphicon-picture"></i><strong>Display</strong>
                </a>
            </td>
        </tr>
        {{-- Window settings omitted as it is for standalone only --}}
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'language') }}">
                    <i class="glyphicon glyphicon-flag"></i><strong>Language</strong>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'backup') }}">
                    <i class="glyphicon glyphicon-cloud-download"></i><strong>Backup</strong>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'calendar') }}">
                    <i class="glyphicon glyphicon-calendar"></i><strong>Calendar</strong>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'torrent') }}">
                    <i class="glyphicon glyphicon-magnet"></i><strong>Torrent</strong>
                </a>
            </td>
        </tr>

        @if(settings('torrenting.enabled'))
            {{-- Client-Specific Settings Links --}}
            @php
                $currentClientName = settings('torrenting.client');
            @endphp
            
            @foreach($supportedClients as $clientKey => $clientData)
                @php
                    // Only show the link if it matches the current client
                    // Although the controller doesn't filter the list passed to view?
                    // The view receives $supportedClients.
                    // But if we reload the view, we want to show only the active one?
                    // The view logic:
                    $isVisible = ($clientKey === $currentClientName);
                @endphp
                @if($isVisible)
                <tr id="client-link-{{ $clientData['id'] }}" class="client-setting-link">
                    <td colspan="2">
                        <a href="#" data-sidepanel-expand="{{ route('settings.show', $clientData['id']) }}">
                            <img src="{{ asset('img/torrentclients/' . $clientData['icon']) }}" class="smallsettingicon"> 
                            <strong>{{ $clientData['name'] }} {{ __('COMMON/integration/hdr') }}</strong>
                        </a>
                    </td>
                </tr>
                @endif
            @endforeach

            <script>
                // Function to update the sidebar when the client setting changes
                // REMOVED: Inline scripts do not run when loaded via SidePanel.js (innerHTML).
                // Logic moved to Settings.js, using data-client-map attribute.
            </script>

            <tr>
                <td colspan="2">
                    <a href="#" data-sidepanel-expand="{{ route('settings.show', 'auto-download') }}">
                        <i class="glyphicon glyphicon-cloud-download"></i><strong>{{ __('COMMON/autodownload/hdr') }}</strong>
                    </a>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="#" data-sidepanel-expand="{{ route('settings.show', 'torrent-search') }}">
                        <i class="glyphicon glyphicon-search"></i><strong>{{ __('SIDEPANEL/SETTINGS/search-engines/hdr') }}</strong>
                    </a>
                </td>
            </tr>
        @endif

        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'trakttv') }}">
                    <i class="glyphicon glyphicon-cloud-upload"></i><strong>Trakt.TV</strong>
                </a>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'subtitles') }}">
                    <i class="glyphicon glyphicon-text-width"></i><strong>Subtitles</strong>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <a href="#" data-sidepanel-expand="{{ route('settings.show', 'miscellaneous') }}">
                    <i class="glyphicon glyphicon-wrench"></i><strong>Miscellaneous</strong>
                </a>
            </td>
        </tr>
    </table>
</div>
