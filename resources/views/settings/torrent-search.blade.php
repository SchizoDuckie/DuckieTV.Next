    <div class="buttons" data-section="torrent-search">
        <!-- Default Provider -->
        <h2 translate-once>{{ __('SETTINGS/TORRENT-SEARCH/default-provider/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>{{ __('SETTINGS/TORRENT-SEARCH/default-provider/desc') }}</p>

        @inject('torrentSearchService', 'App\Services\TorrentSearchService')
        @php
            $providers = array_keys($torrentSearchService->getSearchEngines());
            $currentProvider = settings('torrenting.searchprovider', 'ThePirateBay');
        @endphp

        @foreach($providers as $provider)
            <a href="javascript:void(0)" onclick="setSearchProvider('{{ $provider }}')" class="btn {{ $currentProvider == $provider ? 'btn-success' : '' }}" style='padding:10px; height:45px; margin: 2px;'>
                @if($currentProvider == $provider)
                    <i class="glyphicon glyphicon-ok"></i>
                @endif
                <strong style='position: {{ $currentProvider == $provider ? "absolute; left: 60px" : "" }}'>{{ $provider }}</strong>
            </a>
        @endforeach

        <hr class="setting-divider">

        <!-- Quality Settings -->
        <h2 translate-once>{{ __('SETTINGS/TORRENT-SEARCH/quality/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>
            {{ __('SETTINGS/TORRENT-SEARCH/quality/desc') }}<br>
            <strong>{{ __('SETTINGS/TORRENT-SEARCH/quality-select/hdr') }}</strong> {{ __('SETTINGS/TORRENT-SEARCH/quality-select/desc') }}<br>
            {{ __('SETTINGS/TORRENT-SEARCH/quality-select/desc2') }}
        </p>

        @php
            $qualities = ['UltraHD', 'FullHD', 'HD', 'SD', 'Low'];
            $currentQuality = settings('torrenting.searchquality', '');
        @endphp

        <a href="javascript:void(0)" onclick="setSearchQuality('')" style="padding:10px; height:45px; margin: 2px;" class="btn {{ $currentQuality == '' ? 'btn-success' : '' }}">
            @if($currentQuality == '')
                <i class="glyphicon glyphicon-ok"></i>
            @endif
            <strong style='padding-left: {{ $currentQuality == '' ? "30px" : "0" }}'>{{ __('SETTINGS/TORRENT-SEARCH/quality-select-all/lbl') }}</strong>
        </a>

        @foreach($qualities as $quality)
            <a href="javascript:void(0)" onclick="setSearchQuality('{{ $quality }}')" style="padding:10px; height:45px; margin: 2px;" class="btn {{ $currentQuality == $quality ? 'btn-success' : '' }}">
                @if($currentQuality == $quality)
                    <i class="glyphicon glyphicon-ok"></i>
                @endif
                <strong style='padding-left: {{ $currentQuality == $quality ? "30px" : "0" }}'>{{ $quality }}</strong>
            </a>
        @endforeach

        <hr class="setting-divider">

        <!-- Require Keywords Mode -->
        @php
            $requireKeywordsModeOR = settings('torrenting.requirekeywordsmode') == 'OR';
        @endphp
        <h2 style='white-space:nowrap'>
            {{ __('SETTINGS/TORRENT-SEARCH/require-keywords-mode/hdr') }}
            <span title="{{ $requireKeywordsModeOR ? __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-or/lbl') : __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-and/lbl') }}">
                <i style='font-style:normal'>{{ $requireKeywordsModeOR ? '||' : '&&' }}</i>
            </span>
        </h2>
        <ul class="list-unstyled">
            <li>
                <p style='text-align:left;white-space:normal'>{{ $requireKeywordsModeOR ? __('SETTINGS/TORRENT-SEARCH/keyword-match-or/desc') : __('SETTINGS/TORRENT-SEARCH/keyword-match-and/desc') }}</p>
                <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
                    {{ $requireKeywordsModeOR ? __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-or/lbl') : __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-and/lbl') }}
                </p>
                <a href="javascript:void(0)" onclick="toggleSetting('torrenting.requirekeywordsmode', '{{ $requireKeywordsModeOR ? 'AND' : 'OR' }}')" class="btn btn-{{ $requireKeywordsModeOR ? 'info' : 'success' }}">
                    <i style='font-style:normal; font-weight:bold; float:left'>{{ !$requireKeywordsModeOR ? '||' : '&&' }}</i>&nbsp; 
                    {{ !$requireKeywordsModeOR ? __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-or/lbl') : __('SETTINGS/TORRENT-SEARCH/keyword-match-mode-and/lbl') }}
                </a>
            </li>
        </ul>

        <hr class="setting-divider">

        <!-- Require Keywords -->
        <h2>{{ __('COMMON/require-keywords/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>{{ $requireKeywordsModeOR ? __('SETTINGS/TORRENT-SEARCH/require-keywords-mode-or/lbl') : __('SETTINGS/TORRENT-SEARCH/require-keywords-mode-and/lbl') }}</p>
        <ul class="list-unstyled">
            <li>
                <span>{{ __('COMMON/require-keywords/hdr') }}</span><br>
                <input type='text' style='width:350px' id="requireKeywords" value="{{ settings('torrenting.requirekeywords') }}" placeholder="{{ __('COMMON/global-search/placeholder') }}">
                <a class="btn btn-success" onclick="saveSetting('torrenting.requirekeywords', document.getElementById('requireKeywords').value)">
                    <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; {{ __('COMMON/save/btn') }}
                </a>
            </li>
        </ul>

        <hr class="setting-divider">

        <!-- Ignore Keywords -->
        <h2>{{ __('COMMON/ignore-keywords/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>{{ __('SETTINGS/TORRENT-SEARCH/ignore-keywords/desc') }}</p>
        <ul class="list-unstyled">
            <li>
                <span>{{ __('COMMON/ignore-keywords/hdr') }}</span><br>
                <input type='text' style='width:350px' id="ignoreKeywords" value="{{ settings('torrenting.ignorekeywords') }}" placeholder="{{ __('COMMON/global-search/placeholder') }}">
                <a class="btn btn-success" onclick="saveSetting('torrenting.ignorekeywords', document.getElementById('ignoreKeywords').value)">
                    <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; {{ __('COMMON/save/btn') }}
                </a>
            </li>
        </ul>

         <hr class="setting-divider">
         
         <!-- Seeders -->
        <div class="autodownload">
            <h2>{{ __('SETTINGS/TORRENT-SEARCH/seeders/hdr') }}</h2>
            <p>{{ __('SETTINGS/TORRENT-SEARCH/seeders/desc') }}<br>{{ __('SETTINGS/TORRENT-SEARCH/seeders-default/lbl') }}</p>
            
            <span>{{ __('SETTINGS/TORRENT-SEARCH/seeders/form') }}</span> 
            <input type="number" id="minSeeders" value="{{ settings('torrenting.min_seeders') }}" min="0" max="3000">
            <a class="btn btn-success" onclick="saveSetting('torrenting.min_seeders', document.getElementById('minSeeders').value)" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; {{ __('COMMON/save/btn') }}
            </a>
        </div>

        <hr class="setting-divider">
        
        <!-- Global Size Min -->
        <h2>{{ __('COMMON/global-size-min/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>{{ __('SETTINGS/TORRENT-SEARCH/global-size-min/desc') }}</p>
        <form>
            <span>{{ __('COMMON/global-size/form') }}</span> 
            <input type="number" min="0" id="globalSizeMin" value="{{ settings('torrenting.global_size_min') }}" placeholder="{{ __('COMMON/search-size/placeholder') }}">
            <a class="btn btn-success" onclick="saveSetting('torrenting.global_size_min', document.getElementById('globalSizeMin').value)" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; {{ __('COMMON/save/btn') }}
            </a>
        </form>

        <hr class="setting-divider">

        <!-- Global Size Max -->
        <h2>{{ __('COMMON/global-size-max/hdr') }}</h2>
        <p style='text-align:left;white-space:normal'>{{ __('SETTINGS/TORRENT-SEARCH/global-size-max/desc') }}</p>
         <form>
            <span>{{ __('COMMON/global-size/form') }}</span> 
            <input type="number" min="0" id="globalSizeMax" value="{{ settings('torrenting.global_size_max') }}" placeholder="{{ __('COMMON/search-size/placeholder') }}">
            <a class="btn btn-success" onclick="saveSetting('torrenting.global_size_max', document.getElementById('globalSizeMax').value)" style="float:right; margin-top:-10px;">
                <i class="glyphicon glyphicon-floppy-save"></i>&nbsp; {{ __('COMMON/save/btn') }}
            </a>
        </form>

        <hr class="setting-divider">

        <!-- Unsafe Proxies -->
        @php
            $allowUnsafe = settings('torrenting.unsafe_proxies') == 1;
        @endphp
        <h2 style='white-space:nowrap'>
            {{ __('SETTINGS/TORRENT-SEARCH/unsafe-proxies/hdr') }}
            <span title="{{ $allowUnsafe ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon glyphicon-{{ $allowUnsafe ? 'ok' : 'remove' }}" ></i>
            </span>
        </h2>
        <ul class="list-unstyled">
            <li>
                <p>{{ $allowUnsafe ? __('SETTINGS/TORRENT-SEARCH/unsafe-proxies-enabled/desc') : __('SETTINGS/TORRENT-SEARCH/unsafe-proxies-disabled/desc') }}</p>
                 <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong>
                    {{ $allowUnsafe ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}
                </p>
                <a href="javascript:void(0)" onclick="toggleSetting('torrenting.unsafe_proxies', '{{ $allowUnsafe ? 0 : 1 }}')" class="btn btn-{{ $allowUnsafe ? 'danger' : 'success' }}">
                    <i class="glyphicon glyphicon-{{ $allowUnsafe ? 'remove' : 'ok' }}" ></i>&nbsp; {{ $allowUnsafe ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
                </a>
            </li>
        </ul>

    </div>

    <!-- Scripts moved to public/js/Settings.js -->
