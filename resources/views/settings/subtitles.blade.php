<div class="buttons languages">

	<h2>Subtitles</h2>

	<p>Choose which subtitle languages you want to search for</p>

	<hr>
    @php
        $selectedLanguages = settings('subtitles.languages', []); // Array of codes
        $selectedString = implode(', ', $selectedLanguages);
    @endphp

	<p>
        @if(!empty($selectedLanguages))
		    <strong>Selected:</strong><br> {{ $selectedString }}<br>
        @else
		    <strong>No filter set. All languages will be shown.</strong>
        @endif
	</p>
	<p style='text-align: right'>
        @if(!empty($selectedLanguages))
            <a href="javascript:void(0)" onclick="alert('Clear selection not implemented')" class="btn btn-xs btn-warning" style="display:inline-block; padding-right:15px;">
                <i class="glyphicon glyphicon-trash" style='font-size:15px; line-height: 23px; vertical-align:middle'></i>
                <span>Clear selection</span>
            </a>
        @endif
	</p>

	<hr>

    {{-- 
        We can reuse the available locales for subtitle languages, 
        or define a specific subtitle language list if it differs from UI languages.
        For now, let's use the provided locales from the controller if available, 
        or fall back to a comprehensive list. 
        Note: The controller only passes 'locales' to the 'language' view currently.
        We should probably pass it to 'subtitles' too or share it. 
    --}}
    
    @if(isset($locales))
        @foreach($locales as $locale => $name)
            <a href="javascript:void(0)" onclick="alert('Toggle {{ $locale }} not implemented')" class="btn {{ in_array($locale, $selectedLanguages) ? 'btn-success' : '' }}" style="margin: 2px;">
                <i class="flag flag-{{ strtolower(substr($locale, 3)) }}"></i>
                <span style='line-height:25px; position:relative; top:-5px;'>{{ $locale }}</span>
            </a>
        @endforeach
    @else
        <p>No subtitle languages available.</p>
    @endif
</div>
