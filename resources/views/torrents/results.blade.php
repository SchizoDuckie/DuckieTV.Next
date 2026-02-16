@foreach($results as $index => $result)
<tr>
    <td style="width:80px; padding:5px; vertical-align: top; white-space: nowrap;">
        {{-- 1. Magnet (Add to client) --}}
        @if(!empty($result['noMagnet']))
            <a href="javascript:void(0)" class="disabled"><i class="glyphicon glyphicon-magnet" style="color:gray"></i></a>
        @elseif(!empty($result['magnetUrl']))
            <a href="javascript:void(0)" class="torrent-add-client" 
               data-magnet="{{ $result['magnetUrl'] }}" 
               data-release-name="{{ $result['releasename'] ?? '' }}" 
               title="{{ __('Add to torrent client') }}">
                <i class="glyphicon glyphicon-magnet"></i>
            </a>
        @else
            <a href="javascript:void(0)" class="torrent-fetch-magnet" 
               data-index="{{ $index }}" 
               title="{{ __('Fetch magnet link') }}">
                <i class="glyphicon glyphicon-magnet" style="color: #aaa;"></i>
            </a>
        @endif

        {{-- 2. Download (Torrent File - Add to client) --}}
        @if(!empty($result['noTorrent']))
            <a href="javascript:void(0)" class="disabled"><i class="glyphicon glyphicon-download" style="color:gray"></i></a>
        @elseif(!empty($result['torrentUrl']))
            <a href="javascript:void(0)" class="torrent-add-client" 
               data-url="{{ $result['torrentUrl'] }}" 
               data-info-hash="{{ $result['infoHash'] ?? '' }}" 
               data-release-name="{{ $result['releasename'] ?? '' }}" 
               title="{{ __('Add to torrent client') }}">
                <i class="glyphicon glyphicon-download"></i>
            </a>
        @else
            <a href="javascript:void(0)" class="torrent-fetch-torrent" 
               data-index="{{ $index }}" 
               title="{{ __('Fetch torrent file') }}">
               <i class="glyphicon glyphicon-download" style="color: #aaa;"></i>
            </a>
        @endif

        {{-- 3. Link (Open Magnet/Torrent URL directly) --}}
        @if(!empty($result['magnetUrl']))
            <a href="{{ $result['magnetUrl'] }}" class="torrent-external-link" title="{{ __('Magnet Link') }}">
                <i class="glyphicon glyphicon-link"></i>
            </a>
        @else
            <a href="javascript:void(0)" class="disabled"><i class="glyphicon glyphicon-link" style="color:gray"></i></a>
        @endif

        {{-- 4. Info (Details) --}}
        @if(!empty($result['detailUrl']))
            <a href="{{ $result['detailUrl'] }}" target="_blank" title="{{ __('Torrent Details') }}">
                <i class="glyphicon glyphicon-info-sign"></i>
            </a>
        @else
            <a href="javascript:void(0)" class="disabled"><i class="glyphicon glyphicon-info-sign" style="color:gray"></i></a>
        @endif
    </td>
    <td>{{ $result['engine'] ?? '' }}</td>
    <td class="releasename" title="{{ $result['releasename'] ?? '' }}">{{ $result['releasename'] ?? '' }}</td>
    <td style="text-align: right; white-space: nowrap;">{{ $result['size'] }}</td>
    <td style="text-align: right; color: #5cb85c; width:50px;">{{ $result['seeders'] }}</td>
    <td style="text-align: right; color: #d9534f; width:50px;">{{ $result['leechers'] }}</td>
</tr>
@endforeach
