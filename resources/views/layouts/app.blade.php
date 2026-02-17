<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'DuckieTV' }}</title>
    <style>
        serieheader { display: inline-block; vertical-align: top; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Legacy Styles (same load order as original tab.html) -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/anim.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flags.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toasts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dialogs.css') }}">

    <style>
        /* Only styles NOT covered by legacy CSS */
        body {
            background-color: #000;
            overflow-x: hidden;
        }
        /* Match original layout details */
        .actionbar-bottom {
            position: absolute;
            bottom: 0px;
            width: 58px;
        }
    </style>
    @stack('styles')
</head>
<body class="{{ app(\App\Services\SettingsService::class)->get('kc.always', false) ? 'kc' : '' }} standalone">

    <div class="windowChrome">
        <button class="glyphicon glyphicon-win-minimize" id="minimize" title="Minimize"></button>
        <button class="glyphicon glyphicon-win-restore" id="unmaximize" title="Restore Down" style="display:none"></button>
        <button class="glyphicon glyphicon-win-maximize" id="maximize" title="Maximize"></button>
        <button class="glyphicon glyphicon-win-close" id="close" title="Close"></button>
    </div>

    <background-rotator channel="'background:load'">
        <div class="background-image-container">
            <div class="placeholder active"></div>
            <div class="bg1"></div>
            <div class="bg2"></div>
        </div>
        <div class="background-details"></div>
    </background-rotator>

    @yield('calendar_container')

    @yield('content')

    <series-list ui-view="favorites">
        <div class="series-list"></div>
    </series-list>

    <action-bar>
        <div id="actionbar">
            <div class="logo" onclick="window.location='{{ route('home') }}'" title="Quack!"></div>
            <ul class="list-unstyled">
                <li id="calendar">
                    <a href="{{ route('calendar.index') }}" title="{{ __('COMMON/calendar/hdr') }}" class="glyphicon glyphicon-calendar"></a>
                </li>
                <li id="favorites">
                    <a data-serieslist-show="{{ route('series.index') }}" title="{{ __('COMMON/favorites/hdr') }}" class="glyphicon glyphicon-heart"></a>
                </li>
                <li id="add_favorites">
                    <a data-seriesadding-show="{{ route('search.index') }}" title="{{ __('SERIESLIST/TOOLS/FAVORITES/addshow-show/glyph') }}" class="glyphicon glyphicon-plus"></a>
                </li>
                <li id="wl">
                    <a href="{{ route('watchlist.index') }}" title="{{ __('ACTIONBAR/watchlist/tooltip') }}" class="glyphicon glyphicon-facetime-video"></a>
                </li>
                <li id="actionbar_search">
                    <a href="{{ route('torrents.search-dialog') }}" title="{{ __('TORRENTDIALOG/search-download-any/tooltip') }}" class="glyphicon glyphicon-download" data-torrent-search-show></a>
                </li>
                <li id="actionbar_subtitles">
                    <a href="{{ route('subtitles.index') }}" title="{{ __('COMMON/find-subtitle/lbl') }}" class="glyphicon glyphicon-text-width" data-subtitles-search-show></a>
                </li>
                {{-- TorrentClientComposer injects $activeClient and $clientClass --}}
                <li id="actionbar_torrent">
                    <a href="#" title="{{ $activeClient ? $activeClient->getName() : 'DuckieTorrent' }}" class="glyphicon {{ $clientClass }}"
                       data-sidepanel-show="{{ route('torrents.index') }}"></a>
                </li>
                <div class="actionbar-bottom">
                    <li id="actionbar_switch">
                        <a href="#" onclick="event.preventDefault(); document.getElementById('toggle-viewmode-form').submit();" 
                           title="{{ __('ACTIONBAR/switch-todo-calendar/tooltip') }}" class="glyphicon glyphicon-check"></a>
                        <form id="toggle-viewmode-form" action="{{ route('settings.toggle-viewmode') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                    <li id="actionbar_autodlstatus">
                        <a href="#" title="{{ __('COMMON/auto-download-status/hdr') }}" class="glyphicon glyphicon-list"
                           data-sidepanel-show="{{ route('autodlstatus.index') }}"></a>
                    </li>
                    <li id="actionbar_settings">
                        <a href="#" title="{{ __('COMMON/settings/hdr') }}" class="glyphicon glyphicon-cog"
                           data-sidepanel-show="{{ route('settings.index') }}"></a>
                    </li>
                    <li id="actionbar_about">
                         <a href="#" title="{{ __('COMMON/about/hdr') }}" class="glyphicon glyphicon-info-sign"
                            data-sidepanel-show="{{ route('about.index') }}"></a>
                    </li>
                </div>
            </ul>
        </div>
    </action-bar>

    @include('partials._side_panel')

    <div class="query-monitor" id="query-monitor">
        <i class="glyphicon glyphicon-info-sign"></i>
        <span>{{ __('QUERYMONITOR/updating-data/lbl') }}</span>:
        <div class="progress-striped progress active">
            <div class="progress-bar progress-bar-success" id="query-monitor-bar" style="width: 0%">
                <span class="count" id="query-monitor-count">0/0</span>
            </div>
        </div>
        <small>{{ __('QUERYMONITOR/please-wait/lbl') }}</small>
    </div>

    @include('partials._restore_progress_templates')

    <!-- Standalone scripts (no build system) -->
    <script src="{{ asset('js/NativeWindow.js') }}"></script>
    <script src="{{ asset('js/ContextMenu.js') }}"></script>
    <script src="{{ asset('js/Toast.js') }}"></script>
    <script src="{{ asset('js/QueryMonitor.js') }}"></script>
    <script src="{{ asset('js/SidePanel.js') }}"></script>
    <script src="{{ asset('js/Calendar.js') }}"></script>
    <script src="{{ asset('js/BackgroundRotator.js') }}"></script>
    <script src="{{ asset('js/TorrentSearch.js') }}"></script>
    <script src="{{ asset('js/TraktTrending.js') }}"></script>
    <script src="{{ asset('js/Subtitles.js') }}"></script>

    {{-- Trakt Trending Overlay --}}
    <div id="trakt-trending-overlay" class="overlay-panel" style="display:none">
        <div class="overlay-header">
            <h2>{{ __('COMMON/addtrending/hdr') }}</h2>
            <button class="close-overlay">&times;</button>
        </div>
        <div class="content"></div>
    </div>
    <script src="{{ asset('js/Settings.js') }}"></script>
    <script src="{{ asset('js/TorrentClient.js') }}"></script>
    <script src="{{ asset('js/DialGauge.js') }}"></script>
    <script src="{{ asset('js/PollingService.js') }}"></script>
    <script src="{{ asset('js/Modal.js') }}"></script>
    <script src="{{ asset('js/BackupRestore.js') }}"></script>
    <script src="{{ asset('js/Panels.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('App: DOMContentLoaded');
            window.SidePanel = new SidePanel();
            window.Panels = new Panels({
                libraryRoute: '{{ route('series.index') }}'
            });
            TorrentSearch.init();
            Subtitles.init();
            window.Calendar = new DuckieCalendar();
            window.BackgroundRotator = new BackgroundRotator({
                route: '{{ route('background.random') }}'
            });
            window.PollingService = new PollingService(2000, {
                connected: "{{ __('COMMON/torrent/connected_to') }}",
                disconnected: "{{ __('COMMON/torrent/disconnected') }}",
                error: "{{ __('COMMON/error') }}"
            });
            window.PollingService.start();
            window.BackupRestore.init({
                'BACKUPCTRLjs/restore/intro': '{{ __("BACKUPCTRLjs/restore/intro") }}',
                'BACKUPCTRLjs/restore/wipe-warn': '{{ __("BACKUPCTRLjs/restore/wipe-warn") }}',
                'BACKUPCTRLjs/restore/merge-info': '{{ __("BACKUPCTRLjs/restore/merge-info") }}',
                'BACKUPCTRLjs/restore/confirm-hdr': '{{ __("BACKUPCTRLjs/restore/confirm-hdr") }}',
                'COMMON/error/hdr': '{{ __("COMMON/error/hdr") }}',
                'BACKUPCTRLjs/progress/restore-failed': '{{ __("BACKUPCTRLjs/progress/restore-failed") }}',
                'BACKUPCTRLjs/progress/hdr': '{{ __("BACKUPCTRLjs/progress/hdr") }}',
                'COMMON/loading-please-wait/lbl': '{{ __("COMMON/loading-please-wait/lbl") }}',
                'BACKUPCTRLjs/progress/extracting': '{{ __("BACKUPCTRLjs/progress/extracting") }}',
                'COMMON/searching/lbl': '{{ __("COMMON/searching/lbl") }}',
                'COMMON/season/lbl': '{{ __("COMMON/season/lbl") }}',
                'BACKUPCTRLjs/progress/restore-complete': '{{ __("BACKUPCTRLjs/progress/restore-complete") }}'
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
