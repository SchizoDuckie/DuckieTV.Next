<div class="modal fade" id="torrent-dialog" tabindex="-1" role="dialog" aria-labelledby="torrentDialogLabel"
     data-search-route="{{ route('torrents.search') }}"
     data-details-route="{{ route('torrents.details') }}"
     data-add-route="{{ route('torrents.add') }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background-color: rgba(25, 25, 25, 0.97); color: white; border: 1px solid rgba(255,255,255,0.1);">

            {{-- Modal Header --}}
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <button type="button" class="close torrent-dialog-close" aria-label="Close" style="color: white; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="torrentDialogLabel" style="font-family: 'bebasregular'; font-size: 24px; letter-spacing: 1px;">
                    FIND TORRENT
                </h4>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body" data-episode-id="{{ $episodeId }}">

                {{-- Search Form --}}
                <div class="torrent-search-form" style="margin-bottom: 15px;">
                    <div class="input-group">
                        <input type="text"
                               id="torrent-search-input"
                               class="form-control"
                               value="{{ trim($query . ' ' . $quality) }}"
                               placeholder="Search for torrents..."
                               autocomplete="off"
                               style="background-color: #333; border: 1px solid #444; color: white;">
                        <span class="input-group-btn">
                            <button id="torrent-search-btn" class="btn btn-primary" type="button">
                                <i class="glyphicon glyphicon-search"></i> SEARCH
                            </button>
                        </span>
                    </div>
                </div>

                {{-- Engine Selector --}}
                <div class="torrentBtns" style="margin-bottom: 10px;">
                    <div class="btn-group btn-group-sm" role="group">
                        @foreach($engines as $engine)
                            <button type="button"
                                    class="btn btn-default torrent-engine-btn {{ $engine === $defaultEngine ? 'active' : '' }}"
                                    data-engine="{{ $engine }}">{{ $engine }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Quality Filter --}}
                @if(count($qualityList) > 0)
                    <div class="torrentBtns" style="margin-bottom: 10px;">
                        <div class="btn-group btn-group-xs" role="group">
                            @foreach($qualityList as $q)
                                <button type="button"
                                        class="btn btn-default quality-btn {{ $quality === $q ? 'active' : '' }}"
                                        data-quality="{{ $q }}">{{ $q }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Search Status Spinner --}}
                <div id="torrent-search-status" style="text-align: center; padding: 30px; display: none;">
                    <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i>
                    <p style="margin-top: 10px;">Searching...</p>
                </div>

                {{-- Error Display --}}
                <div id="torrent-search-error" class="alert alert-danger" style="display: none;"></div>

                {{-- No Results --}}
                <div id="torrent-no-results" style="text-align: center; padding: 30px; color: #aaa; display: none;">
                    No results found. Try a different search query or engine.
                </div>

                {{-- Results Table --}}
                <div id="torrent-results-container" style="display: none; max-height: 50vh; overflow-y: auto;">
                    <table class="torrents table table-condensed white" style="background: transparent;">
                        <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th class="torrent-sort" data-sort="releasename" style="cursor: pointer;">Name</th>
                                <th class="torrent-sort" data-sort="size" style="cursor: pointer; width: 90px; text-align: right;">Size</th>
                                <th class="torrent-sort active" data-sort="seeders" style="cursor: pointer; width: 60px; text-align: right;">
                                    <i class="glyphicon glyphicon-arrow-up" style="color: #5cb85c;"></i>
                                </th>
                                <th class="torrent-sort" data-sort="leechers" style="cursor: pointer; width: 60px; text-align: right;">
                                    <i class="glyphicon glyphicon-arrow-down" style="color: #d9534f;"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="torrent-results-body">
                            {{-- Results injected by TorrentSearch.js --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .torrentBtns .btn-group { flex-wrap: wrap; }
    .torrentBtns .btn.active { background-color: rgba(66,66,66,0.8) !important; color: white !important; border-color: #555 !important; }
    .torrent-sort:hover { background-color: rgba(255,255,255,0.05); }
    .torrent-sort.active { color: #5bc0de; }
    #torrent-results-body tr:hover { background-color: rgba(255,255,255,0.05); cursor: default; }
    #torrent-results-body td { vertical-align: middle; padding: 6px 8px; }
    #torrent-results-body .releasename { word-break: break-word; }
    #torrent-dialog .modal-content .btn-group-sm .btn { font-size: 11px; padding: 3px 8px; }
    
    /* Dark mode inputs */
    .form-control {
        background-color: #333;
        border: 1px solid #444;
        color: white;
    }
    .form-control:focus {
        background-color: #444;
        border-color: #666;
        color: white;
    }
</style>
