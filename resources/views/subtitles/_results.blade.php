<table class="torrents table table-condensed white">
    <thead>
        @if($searching)
            <tr>
                <td colspan="4">
                    <div class="loading-spinner" style="margin:0 auto; display:block; width:50px; height:50px; position:relative;">
                        <div></div><div></div>
                    </div>
                    <p style="text-align:center; padding:10px;">
                        <span>{{ __('COMMON/searching/lbl') }}</span> <span>{{ __('COMMON/searching-please-wait/lbl') }}</span>.
                    </p>
                </td>
            </tr>
        @elseif(count($results) === 0 && !empty($query))
            <tr>
                <td colspan="4">
                    <p>{{ __('COMMON/no-results/lbl') }}</p>
                    <strong>{{ $query }}</strong>
                </td>
            </tr>
        @endif
    </thead>
    <tbody>
        @if(!$searching && count($results) > 0)
            <tr>
                <th><strong>S/E</strong></th>
                <th><strong>{{ __('COMMON/language/hdr') }}</strong></th>
                <th><strong>{{ __('COMMON/title/hdr') }}</strong></th>
                <th><strong>{{ __('SUBTITLEDIALOG/downloads/lbl') }}</strong></th>
            </tr>
            @foreach($results as $subtitle)
                @php
                    $attr = $subtitle['attributes'] ?? [];
                    $details = $attr['feature_details'] ?? [];
                    $downloadUrl = $attr['url'] ?? '#';
                @endphp
                <tr>
                    <td>
                        <a href="{{ $downloadUrl }}" target="_blank">
                            S{{ $details['season_number'] ?? '?' }}E{{ $details['episode_number'] ?? '?' }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ $downloadUrl }}" target="_blank" title="{{ $attr['language'] }}">
                            {{ $attr['language_name'] ?? $attr['language'] }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ $downloadUrl }}" target="_blank">{{ $attr['release'] }}</a>
                        @if(($attr['hearing_impaired'] ?? false) == '1')
                            <i class="glyphicon glyphicon-bullhorn" title="{{ __('SUBTITLEDIALOG/hearing-impaired/tooltip') }}"></i>
                        @endif
                    </td>
                    <td>
                        <a href="{{ $downloadUrl }}" target="_blank">{{ $attr['download_count'] ?? 0 }}</a>
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
