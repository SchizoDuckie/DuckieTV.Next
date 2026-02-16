<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DuckieTV.Next - {{ $title ?? 'Home' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Legacy Styles (same load order as original tab.html) -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/anim.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flags.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toasts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dialogs.css') }}">


    <style>
        /* Only styles NOT covered by main.css */
        body {
            background-color: #000;
            overflow-x: hidden;
        }

        /* Ensure custom tag is treated as block */
        sidepanel, background-rotator {
            display: block;
        }
    </style>
    @stack('styles')
</head>
<body>
    <background-rotator>
        <div class="background-image-container">
            <div class="placeholder active"></div>
            <div class="bg1"></div>
            <div class="bg2"></div>
        </div>
        <div class="background-details"></div>
    </background-rotator>

    {{-- Matches original tab.html: .container floats right of #actionbar --}}
    <div class="container">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    {{-- Matches original templates/actionBar.html --}}
    <div id="actionbar">
        <div class="logo" onclick="window.location='{{ route('home') }}'" style="cursor:pointer" title="Quack!"></div>
        <ul class="list-unstyled">
            <li id="actionbar_calendar">
                <a href="{{ route('calendar.index') }}" title="Calendar" class="glyphicon glyphicon-calendar"></a>
            </li>
            <li id="actionbar_favorites">
                <a href="{{ route('series.index') }}" title="Library" class="glyphicon glyphicon-heart"></a>
            </li>
            <li id="actionbar_add_favorites">
                <a href="{{ route('search.index') }}" title="Add Show" class="glyphicon glyphicon-plus"></a>
            </li>
            <li id="actionbar_trakt">
                <a href="#" title="Trakt.TV Trending" class="glyphicon glyphicon-film"></a>
            </li>
            <li id="actionbar_torrent">
                <a href="#" title="DuckieTorrent" class="glyphicon glyphicon-hdd"
                   data-sidepanel-show="{{ route('torrents.index') }}"></a>
            </li>
            <div style="position:absolute;bottom:0px">
                <li id="actionbar_settings">
                    <a href="#" title="Settings" class="glyphicon glyphicon-cog"
                       data-sidepanel-show="{{ route('settings.index') }}"></a>
                </li>
                <li id="actionbar_about">
                    <a href="#" title="About" class="glyphicon glyphicon-info-sign"></a>
                </li>
            </div>
        </ul>
    </div>

    <!-- NATIVE_SIDEBAR_PARTIAL_LOADED -->
    @include('partials._side_panel')

    {{-- Original DuckieTV Query Monitor --}}
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

    {{-- Template for Toast notifications (if needed later) --}}
    <template id="toast-template">
        <div class="toast">
            <span class="toast-message"></span>
        </div>
    </template>

    <!-- Standalone scripts (no build system) -->
    <script src="{{ asset('js/Toast.js') }}"></script>
    <script src="{{ asset('js/QueryMonitor.js') }}"></script>
    <script src="{{ asset('js/SidePanel.js') }}"></script>
    <script src="{{ asset('js/Calendar.js') }}"></script>
    <script src="{{ asset('js/BackgroundRotator.js') }}"></script>
    <script src="{{ asset('js/TorrentSearch.js') }}"></script>
    <script src="{{ asset('js/Settings.js') }}"></script>
    <script src="{{ asset('js/TorrentClient.js') }}"></script>
    <script src="{{ asset('js/PollingService.js') }}"></script>
    <script src="{{ asset('js/Modal.js') }}"></script>
    <script src="{{ asset('js/BackupRestore.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('App: DOMContentLoaded');
            window.SidePanel = new SidePanel();
            window.Calendar = new Calendar();
            window.BackgroundRotator = new BackgroundRotator({
                route: '{{ route('background.random') }}'
            });
            window.PollingService = new PollingService();
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
