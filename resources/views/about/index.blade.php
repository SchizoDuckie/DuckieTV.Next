<div class="about-container" style="padding: 20px;">
    <h1>{{ __('COMMON/about/hdr') }}</h1>
    
    <div class="row">
        <div class="col-md-6">
            <h3>{{ __('ABOUT/help/hdr') }}</h3>
            <p>
                {{ __('ABOUT/help/p1') }} 
                <a href="https://reddit.com/r/duckietv" target="_blank">/r/duckietv</a>.
            </p>
            <p>
                {{ __('ABOUT/help/p2') }} 
                <a href="{{ route('about.index') }}#changelog">{{ __('ABOUT/help-changelog/link') }}</a>.
            </p>

            <h3>{{ __('ABOUT/bugs/hdr') }}</h3>
            <p>
                {{ __('ABOUT/bugs/p1') }} 
                <a href="https://github.com/SchizoDuckie/DuckieTV/issues" target="_blank">GitHub</a>
                {{ __('ABOUT/bugs/p2') }}.
            </p>

            <h3>{{ __('ABOUT/contributing/hdr') }}</h3>
            <p>
                {{ __('ABOUT/contributing/p1') }} 
                <a href="https://github.com/SchizoDuckie/DuckieTV" target="_blank">{{ __('ABOUT/contributing/link') }}</a>.
            </p>
        </div>
        
        <div class="col-md-6">
            <h3>{{ __('ABOUT/statistics/hdr') }}</h3>
            <ul>
                <li>{{ __('ABOUT/dtvlinks-chrome/lbl') }} <a href="https://chrome.google.com/webstore/detail/duckietv/hkifkplndncbhcppikbmnoeilnechfhh" target="_blank">Chrome Web Store</a></li>
                <li>{{ __('ABOUT/dtvlinks-github/lbl') }} <a href="https://schizoduckie.github.io/DuckieTV/" target="_blank">SchizoDuckie.github.io/DuckieTV</a></li>
            </ul>

            <h3>{{ __('ABOUT/privacy/hdr') }}</h3>
            <p><strong>{{ __('ABOUT/privacy/lbl') }}</strong></p>
            <ul>
                <li>{{ __('ABOUT/privacy/li1') }}</li>
                <li>{{ __('ABOUT/privacy/li2') }}</li>
                <li>{{ __('ABOUT/privacy/li3') }}</li>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h3>{{ __('ABOUT/credits/hdr') }}</h3>
            <p>{{ __('ABOUT/credits/p1') }}</p>
            <ul>
                <li><a href="https://trakt.tv" target="_blank">Trakt.TV</a> {{ __('ABOUT/credits/li1') }}</li>
                <li><a href="https://thetvdb.com" target="_blank">TheTVDB</a> {{ __('ABOUT/credits/li10') }}</li>
                <li><a href="https://fanart.tv" target="_blank">Fanart.TV</a> {{ __('ABOUT/credits/li6') }}</li>
            </ul>
        </div>
    </div>
</div>
