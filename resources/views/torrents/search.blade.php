<div id="torrent-dialog-root"
     data-search-route="{{ route('torrents.search') }}"
     data-details-route="{{ route('torrents.details') }}"
     data-add-route="{{ route('torrents.add') }}"
     data-episode-id="{{ $episodeId ?? '' }}"
     data-title-template="{{ __('TORRENTDIALOG2/hdr') }}">

    <div class="modal-body">
        <!-- dialogs/torrent.html -->
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h1 style="text-transform: uppercase; margin-top: 0;">{{ __('TORRENTDIALOG/hdr') }}</h1>

        <div style="float:right; margin-top:-25px;" id="torrentDialog_topNav">
            <a href="#" id="torrent-advanced-toggle"> <i class="glyphicon glyphicon-cog"></i> <span>{{ __('TORRENTDIALOG/advanced-show/btn') }}</span></a>
            <a href="{{ route('settings.show', ['section' => 'torrent-search']) }}" data-sidepanel-show onclick="window.SidePanel.close()"> <i class="glyphicon glyphicon-cog"></i> <span>{{ __('COMMON/torrent-search-settings/glyph') }}</span></a>
        </div>

        <div class="input-group" style="width: 52%; display: inline-table;">
            <input type="text" id="torrent-search-input" class="form-control" value="{{ $query }}" 
                   placeholder="{{ __('COMMON/type-your-search/lbl') }}"
                   id="torrentDialog_searchBar">
            <span class="input-group-btn">
                <button class="btn btn-default" type="button" id="torrent-search-btn" onclick="TorrentSearch.doSearch()">
                    <i class="glyphicon glyphicon-search"></i>
                </button>
            </span>
        </div>
        
        <!-- quality list -->
        <div class="torrentBtns" style="display:inline-block;" id="torrentDialog_qualityBtns">
            <div class="btn-group">
                <button type="button" class="btn btn-default quality-btn {{ $quality == '' ? 'active' : '' }}" data-quality="">{{ __('COMMON/all/btn') }}</button>
                @foreach(($qualityList ?? ['HDTV', 'WEB', '720p', '1080p', '2160p', 'x265']) as $q)
                    <button type="button" class="btn btn-default quality-btn {{ $quality == $q ? 'active' : '' }}" data-quality="{{ $q }}">{{ $q }}</button>
                @endforeach
            </div>
        </div>

        <!-- search Providers list -->
        <div class="torrentBtns btn-group" id="torrent-selection-bar" style="margin-top:10px; margin-bottom:10px;">
            @foreach($engines as $engine)
                <button type="button" class="btn btn-default torrent-engine-btn {{ $engine === $defaultEngine ? 'active' : '' }}" 
                        data-engine="{{ $engine }}">
                    {{ $engine }}&nbsp;
                </button>
            @endforeach
        </div>

        <!-- Require/Ignore Keywords size min/max and minSeeders check boxes -->
        <div id="torrent-advanced-options" style="padding-bottom: 3px; display: none;" class="collapse">
            <div class="row" style="display: flex">
                <div class="checkbox">
                    <input type="checkbox" id="tc_gms">
                    <label for="tc_gms"><strong>{{ __('COMMON/min-seeders/hdr') }}</strong><br> (50)</label>
                </div>

                <div class="checkbox" id="require-keywords-container" style="display: none;">
                    <input type="checkbox" id="tc_gie">
                    <label for="tc_gie"><strong>{{ __('TORRENTDIALOG/search-require-keywords/lbl') }}</strong><br>(<span id="require-keywords-display"></span>)</label>
                </div>

                <div class="checkbox" id="ignore-keywords-container" style="display: none;">
                    <input type="checkbox" id="tc_gee">
                    <label for="tc_gee"><strong>{{ __('TORRENTDIALOG/search-ignore-keywords/lbl') }}</strong><br>(<span id="ignore-keywords-display"></span>)</label>
                </div>
            </div>
        </div>

        <!-- search results -->
        <div style="max-height: 620px;overflow-x: auto">
            <table class="torrents table table-condensed white">
                <thead>
                    <tr id="torrent-searching-row" style="display: none;">
                        <td>
                            <div class="loading-spinner"> <div></div> <div></div> </div>
                            <p style="text-align:center; padding:10px;"><span>{{ __('COMMON/searching/lbl') }}</span> <span id="active-engine-name"></span><span>{{ __(', please wait.') }}</span>.</p>
                        </td>
                    </tr>
                </thead>
                <tbody id="torrent-no-results-row" style="display: none;">
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
                            <h2 style="text-align:center">{{ __('COMMON/type-your-search/lbl') }}</h2>
                        </th>
                    </tr>
                </tbody>
                <tbody id="torrent-error-row" style="display: none;">
                    <tr>
                        <th>
                            <h1 style="text-align:center"> :( </h1>
                            <h2 style="text-align:center" id="torrent-error-msg"></h2>
                        </th>
                    </tr>
                </tbody>
                <tbody id="torrent-results-header" style="display: none;">
                    <tr>
                        <th>&nbsp;</th>
                        <th style="cursor: pointer;text-align:left" class="torrent-sort" data-sort="releasename">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-name/lbl') }}</strong></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="size">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-size/lbl') }}</strong></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="seeders">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-seed/lbl') }}</strong></u>
                        </th>
                        <th style="cursor: pointer;text-align:right" class="torrent-sort" data-sort="leechers">
                            <u title="{{ __('COMMON/sort-column/tooltip') }}"><strong>{{ __('COMMON/torrent-leech/lbl') }}</strong></u>
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
