<div id="torrent-dialog-root"
     data-search-route="{{ route('torrents.search') }}"
     data-details-route="{{ route('torrents.details') }}"
     data-add-route="{{ route('torrents.add') }}"
     data-episode-id="{{ $episodeId ?? '' }}"
     data-title-template="{{ __('TORRENTDIALOG2/hdr') }}">

    {{-- Provide title for TorrentSearch.js to extract --}}
    <div class="modal-title" style="display:none">{{ __('TORRENTDIALOG2/hdr', ['itemslength' => 0]) }}</div>

    <div class="modal-body">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="float: right; font-size: 21px; font-weight: 700; line-height: 1; color: #000; text-shadow: 0 1px 0 #fff; opacity: .2;">&times;</button>
        
        <h1 style="margin-top:0" id="torrent-dialog-header">
            @if($query)
                {!! __('TORRENTDIALOG2/hdr', ['itemslength' => 0]) !!} <small>({{ $query }})</small>
            @else
                {{ __('TORRENTDIALOG/hdr') }}
            @endif
        </h1>

        <div style="float:right; margin-top:-25px;">
            <a href="#" id="torrent-advanced-toggle" style="cursor:pointer">
                <i class="glyphicon glyphicon-cog"></i> <span>{{ __('TORRENTDIALOG/advanced-show/btn') }}</span>
            </a>
            <a href="#" data-sidepanel-show="{{ route('settings.show', ['section' => 'torrent-search']) }}" onclick="TorrentSearch.close()" style="cursor:pointer; margin-left: 10px;">
                <i class="glyphicon glyphicon-cog"></i> <span>{{ __('COMMON/torrent-search-settings/glyph') }}</span>
            </a>
        </div>

        <div class="torrentDialog_topNav">
            <div class="input-group torrentDialog_searchBar">
                <input type="text" id="torrent-search-input" class="form-control" value="{{ $query }}" placeholder="{{ __('COMMON/type-your-search/lbl') }}">
                <span class="input-group-btn">
                    <button class="btn btn-default" id="torrent-search-btn" style="height: 40px" type="button" title="{{ __('TORRENTDIALOG2/search-now/tooltip') }}">
                        <i class="glyphicon glyphicon-search"></i>
                    </button>
                </span>
            </div>

            <div class="torrentBtns torrentDialog_qualityBtns">
                <div class="btn-group">
                    <button type="button" class="btn btn-default quality-btn active" data-quality="">{{ __('COMMON/all/btn') }}</button>
                    @foreach($qualityList ?? ['1080p', '720p', 'HDTV', 'WebDL'] as $q)
                        <button type="button" class="btn btn-default quality-btn" data-quality="{{ $q }}">{{ $q }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- active search engines list -->
        <div id="torrent-advanced-options" style="padding-bottom: 3px; display:none;">
            <div class="torrentBtns btn-group">
                @foreach($engines as $engine)
                    <button type="button" class="btn btn-default torrent-engine-btn {{ $engine === $defaultEngine ? 'active' : '' }}" 
                            data-engine="{{ $engine }}"
                            style="{{ $engine !== $defaultEngine ? 'color: white' : 'color: gray' }}">
                        <i class="tb-activeIcon glyphicon glyphicon-{{ $engine === $defaultEngine ? 'ok' : 'remove' }}"></i>
                        &nbsp;{{ $engine }}&nbsp;
                    </button>
                @endforeach
            </div>

            <div class="row" style="display: flex">
                <div class="checkbox">
                    <input type="checkbox" id="tc_gms">
                    <label for="tc_gms"><strong>{{ __('COMMON/min-seeders/hdr') }}</strong><br> (50)</label>
                </div>
            </div>
        </div>

        <!-- search results -->
        <div style="max-height: 660px; overflow-x: auto">
            <table class="torrents table table-condensed white" style="max-height: 800px;overflow-x: auto">
                <thead>
                    <tr id="torrent-searching-row" style="display:none">
                        <td>
                            <p style='text-align:center; padding:10px;'>
                                <span>{{ __('COMMON/searching/lbl') }}</span><span>{{ __('COMMON/searching-please-wait/lbl') }}</span>.
                            </p>
                        </td>
                    </tr>
                </thead>
                <tbody id="torrent-no-results-row" style="display:none">
                    <tr>
                        <th>
                            <p>{{ __('COMMON/no-results/lbl') }}</p>
                            "<strong><span id="torrent-no-results-query"></span></strong>"
                        </th>
                    </tr>
                </tbody>
                <tbody id="torrent-initial-state" @if($query) style="display:none" @endif>
                    <tr>
                        <th>
                            <h2 style='text-align:center'>{{ __('COMMON/type-your-search/lbl') }}</h2>
                        </th>
                    </tr>
                </tbody>
                <tbody id="torrent-error-row" style="display:none">
                    <tr style="font-size: 12px;">
                        <td style='text-align:center'>:(</td>
                        <td id="torrent-error-msg" style='text-align:left;white-space:pre-wrap;' colspan="4"></td>
                    </tr>
                </tbody>
                <tbody id="torrent-results-header" style="display:none">
                    <tr>
                        <th style="width: 80px;">&nbsp;</th>
                        <th style="cursor: pointer;text-align:left" class="torrent-sort" data-sort="engine">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-engine/lbl') }}</strong><span class="sortorder"></span></u>
                        </th>
                        <th style="cursor: pointer;text-align:left" class="torrent-sort" data-sort="releasename">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-name/lbl') }}</strong><span class="sortorder"></span></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="size">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-size/lbl') }}</strong><span class="sortorder"></span></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="seeders">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-seed/lbl') }}</strong><span class="sortorder"></span></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="leechers">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-leech/lbl') }}</strong><span class="sortorder"></span></u>
                        </th>
                    </tr>
                </tbody>
                <tbody id="torrent-results-body">
                    <!-- Results injected by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
