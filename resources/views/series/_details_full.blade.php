<div class="serie-overview" style="color:black">
    <button type="button" class="close" data-sidepanel-show="{{ route('series.show', $serie->id) }}" title="Back to overview">&times;</button>
    <div class="serie-img poster large" style="background-image: url('{{ $serie->poster }}');">
        @if($serie->watched)
            <em class="badge" title="All watched">
                <i class="glyphicon glyphicon-eye-open" style="color:white !important"></i>
            </em>
        @else
            <em class="badge" title="{{ $serie->notWatchedCount }} to watch">
                <i class="glyphicon glyphicon-eye-close"></i> {{ $serie->notWatchedCount }}
            </em>
        @endif
        @if(!$serie->displaycalendar)
            <em class="badge leftbadge" title="Hidden from calendar">
                <i class="glyphicon glyphicon-ban-circle" style="color:white !important"></i>
            </em>
        @endif
    </div>

    <table class="metadata" width="100%" border="0" style="margin-top: 10px">
        <tbody>
            @if($serie->alias)
            <tr>
                <th>ALIAS</th>
                <td>{{ $serie->alias }}</td>
            </tr>
            @endif
            <tr>
                <th>LINKS</th>
                <td>
                    <a style="text-decoration: underline" href="https://www.google.com/search?q={{ urlencode($serie->name) }} (TV series)+Wikipedia&btnI=745" target="_blank">Wikipedia</a><strong> | </strong>
                    @if($serie->imdb_id)
                        <a style="text-decoration: underline" href="https://www.imdb.com/title/{{ $serie->imdb_id }}" target="_blank">IMDB</a><strong> | </strong>
                    @endif
                    @if($serie->tmdb_id)
                        <a style="text-decoration: underline" href="https://www.themoviedb.org/tv/{{ $serie->tmdb_id }}" target="_blank">TMDB</a><strong> | </strong>
                    @endif
                    @if($serie->tvdb_id)
                        <a style="text-decoration: underline" href="https://thetvdb.com/?tab=series&id={{ $serie->tvdb_id }}" target="_blank">TVDB</a><strong> | </strong>
                    @endif
                    @if($serie->trakt_id)
                        <a style="text-decoration: underline" href="https://www.trakt.tv/shows/{{ $serie->trakt_id }}" target="_blank">Trakt</a>
                    @endif
                </td>
            </tr>
            <tr>
                <th>AIRS ON</th>
                <td>{{ $serie->airs_dayofweek }} {{ $serie->airs_time }} {{ $serie->timezone }}</td>
            </tr>
            <tr>
                <th>FIRST AIRED</th>
                <td>{{ $serie->firstaired ? $serie->firstaired->format('M d, Y') : 'Unknown' }}</td>
            </tr>
            <tr>
                <th>GENRE</th>
                <td>
                    <ul class="list-unstyled">
                        @foreach(explode('|', $serie->genre) as $genre)
                            @if($genre) <li>{{ $genre }}</li> @endif
                        @endforeach
                    </ul>
                </td>
            </tr>
            <tr>
                <th>CONTENT RATING</th>
                <td>{{ $serie->contentrating }}</td>
            </tr>
            <tr>
                <th>COUNTRY</th>
                <td>{{ $serie->country }}</td>
            </tr>
            <tr>
                <th>NETWORK</th>
                <td>{{ $serie->network }}</td>
            </tr>
            @if($serie->rating)
            <tr>
                <th>RATING</th>
                <td>{{ $serie->rating }}% ({{ $serie->ratingcount }} votes)</td>
            </tr>
            @endif
            <tr>
                <th>RUN TIME</th>
                <td>{{ $serie->runtime }} minutes</td>
            </tr>
            <tr>
                <th>STATUS</th>
                <td>{{ strtoupper($serie->status) }}</td>
            </tr>
            @if($serie->actors)
            <tr>
                <th>ACTORS</th>
                <td>
                    <ul class="actors list-unstyled">
                        @foreach(explode('|', $serie->actors) as $actor)
                            @if($actor) <li>{{ $actor }}</li> @endif
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endif
            <tr style='border-top: 1px solid white'>
                <th colspan="2" style="text-align: center">
                    <h3 style="margin:10px 0 10px 0">IT WOULD TAKE</h3>
                    <h1 style="margin:10px 0 10px 0">{{ $serie->getFormattedTotalRunTime() }}</h1>
                    <h3 style='text-align:center;margin:10px 0 10px 0'>
                        TO BINGE WATCH {{ $serie->name }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th colspan="2" style="text-align:center">
                    <h3>YOU HAVE ALREADY SPENT</h3>
                    <h1 style="margin:10px 0 10px 0">{{ $serie->getFormattedTotalWatchedTime() }}</h1>
                    <h3>WHICH IS</h3>
                    <h1 style="margin:10px 0 10px 0">{{ $serie->getWatchedPercentage() }} %</h1>
                </th>
            </tr>
        </tbody>
    </table>
</div>
