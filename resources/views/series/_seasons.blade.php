{{--
    Seasons Grid — Right Panel

    Shows clickable season posters in a grid layout. Each season links to
    the episodes list for that season. Displayed in the sidepanel right panel
    via data-sidepanel-expand from the series overview or episode detail views.

    Matches the original DuckieTV seasons.html template which uses the
    serieHeader directive to render each season poster with a label.

    Variables:
        $serie — The Serie model with seasons eager-loaded

    @see templates/sidepanel/seasons.html in DuckieTV-angular
--}}
<div class="seasons miniposter" style="text-align:center">
    <button type="button" class="close" onclick="SidePanel.show('{{ route('series.show', $serie->id) }}')" title="Close Seasons">&times;</button>

    <h2 style="margin-bottom:50px;">
        <span style="border-bottom: 1px solid white; padding:5px; margin-bottom:15px">SEASONS</span>
        <small style="float:right; line-height:2">Click to see episodes</small>
    </h2>

    @foreach($serie->seasons as $season)
        <div class="serieheader" style="display: inline-block; width: 165px; cursor: pointer; margin: 5px; vertical-align: top;"
             data-sidepanel-expand="{{ route('series.episodes', ['id' => $serie->id, 'season_id' => $season->id]) }}">
            <div style="width: 150px; height: 225px; background-size: cover; background-position: center; margin: 0 auto; border: 1px solid rgba(255,255,255,0.2); background-color: rgba(255,255,255,0.05); background-image: url('{{ $season->poster ?: $serie->poster }}'); position: relative;">
                @if($season->watched)
                    <em class="badge" title="All watched" style="position: absolute; top: 5px; right: 5px;">
                        <i class="glyphicon glyphicon-eye-open" style="color:white !important"></i>
                    </em>
                @else
                    <em class="badge" title="{{ $season->getNotWatchedCount() }} to watch" style="position: absolute; top: 5px; right: 5px;">
                        <i class="glyphicon glyphicon-eye-close"></i> {{ $season->getNotWatchedCount() }}
                    </em>
                @endif
            </div>
            <p style="text-align:center; margin-top: 5px;">
                <strong style="color:white">{{ $season->seasonnumber == 0 ? 'Specials' : 'Season ' . $season->seasonnumber }}</strong>
            </p>
        </div>
    @endforeach
</div>

<div>
    <table class="buttons" width="100%" border="0">
        <tr>
            <td colspan="2">
                <div id="mark-all-watched-btn-group">
                    <a href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-btn-group').style.display='none'; document.getElementById('mark-all-watched-confirm').style.display='table';">
                        <i class="glyphicon glyphicon-eye-open"></i> <strong>MARK ALL WATCHED</strong>
                    </a>
                </div>
                
                <table id="mark-all-watched-confirm" class="buttons" width="100%" border="0" style="display:none">
                    <tr>
                        <td>
                            <a class="btn btn-danger" href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-seasons').submit()">
                                <i class="glyphicon glyphicon-question-sign spin"></i> <strong>ARE YOU SURE?</strong>&nbsp;<strong>YES</strong>
                                <form id="mark-all-watched-seasons" method="POST" action="{{ route('series.update', $serie->id) }}" style="display:none;">@csrf @method('PATCH')<input type="hidden" name="action" value="mark_watched"></form>
                            </a>
                        </td>
                        <td>
                            <a class="btn btn-success" href="javascript:void(0)" onclick="document.getElementById('mark-all-watched-confirm').style.display='none'; document.getElementById('mark-all-watched-btn-group').style.display='block';">
                                <i class="glyphicon glyphicon-ban-circle"></i> <strong>CANCEL</strong>
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
