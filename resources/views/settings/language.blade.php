<div class="buttons languages">
    <h2>Language
        <span title="{{ settings('application.locale', 'en_US') }}">
            <i class="flag flag-{{ settings('application.locale', 'en_US') }}"></i>
        </span>
    </h2>

    <p>Select your preferred language for the DuckieTV interface.</p>
    <p>Help us translate DuckieTV on GitHub!</p>

    @php
        $currentLocale = settings('application.locale', 'en_US');
    @endphp

    @foreach($locales as $locale => $name)
        <a href="javascript:void(0)" onclick="alert('Set locale to {{ $locale }} not implemented')" class="btn {{ $currentLocale == $locale ? 'btn-success' : '' }}" style="margin: 2px;">
            <i class="flag flag-{{ strtolower(substr($locale, 3)) }}"></i>
            <span style='display-inline-block; top: -5px; position: relative;'>{{ $name }}</span>
        </a>
    @endforeach
</div>
