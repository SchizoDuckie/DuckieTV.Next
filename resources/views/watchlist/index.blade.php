<div style="padding:30px">
    <div class="text-center mb-4">
        <h2>{{ $items[0]['title'] ?? 'TorrentFreak Top 10' }}</h2>
        <button type="button" class="close pull-right" data-sidepanel-close>&times;</button>
    </div>

    @if(empty($items))
        <div class="alert alert-info">No data available from TorrentFreak at the moment.</div>
    @else
        @foreach($items as $collection)
            <div class="top10-section {{ $loop->first ? '' : 'hidden' }}" id="top10-{{ $loop->index }}">
                <table class="table table-condensed table-dark">
                    <thead>
                        <tr>
                            <th style="width:100px">Rank</th>
                            <th>Title</th>
                            <th style="width:85px">Rating</th>
                            <th style="width:100px">Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collection['top10'] as $row)
                            <tr>
                                <td>
                                    <strong class="text-white">{{ $row['rank'] }} / ({{ $row['prevRank'] }})</strong>
                                </td>
                                <td>
                                    <a href="{{ $row['imdb'] }}" target="_blank" class="text-info">
                                        <strong>{{ $row['title'] }}</strong>
                                    </a>
                                </td>
                                <td class="text-white">{{ $row['rating'] }}</td>
                                <td>
                                    @if($row['trailer'])
                                        <a href="{{ $row['trailer'] }}" target="_blank" class="p-1" title="Watch Trailer">
                                            <i class="glyphicon glyphicon-film"></i>
                                        </a>
                                    @endif
                                    {{-- TODO: Torrent dialog integration --}}
                                    <a href="{{ route('search.index', ['q' => $row['searchTitle']]) }}" class="p-1" title="Search Torrent">
                                        <i class="glyphicon glyphicon-search"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @if(count($items) > 1)
            <div class="text-center mt-3">
                <button class="btn btn-sm btn-default" onclick="toggleTop10(-1)">&lt; Previous</button>
                <button class="btn btn-sm btn-default" onclick="toggleTop10(1)">Next &gt;</button>
            </div>
        @endif
    @endif
</div>
