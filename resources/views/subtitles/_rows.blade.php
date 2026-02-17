@foreach($results as $subtitle)
    @php
        $attr = $subtitle['attributes'] ?? [];
        $details = $attr['feature_details'] ?? [];
        $downloadUrl = $attr['url'] ?? '#';
    @endphp
    <tr>
      <td>
        <a href="{{ $downloadUrl }}" target="_blank">S{{ $details['season_number'] ?? '?' }}E{{ $details['episode_number'] ?? '?' }}</a>
      </td>
      <td>
        <a href="{{ $downloadUrl }}" target="_blank">{{ $attr['language_name'] ?? ($attr['language'] ?? 'Unknown') }}</a>
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
