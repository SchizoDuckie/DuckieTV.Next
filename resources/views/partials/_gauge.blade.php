{{--
    Gauge Component — matches original ng-dial-gauge rendered structure exactly.
    Uses viewBox="0 0 180 120" to match original ~180px coordinate system so
    all coordinates, stroke widths, and CSS font sizes apply identically.
--}}
@php
    $min = $min ?? 0;
    $scaleMax = $max ?? 100;
    $relative = $relative ?? false;
    
    if ($relative) {
        while ($value > $scaleMax && $scaleMax < 1000000000) {
            $scaleMax *= 10;
        }
    }
    
    $percent = ($scaleMax > $min) ? ($value - $min) / ($scaleMax - $min) : 0;
    $percent = max(0, min(1, $percent));
    // Original: center=90, radius=74, semicircle arc length = pi * 74
    $dasharray = M_PI * 74; // 232.478
    $dashoffset = $dasharray * (1 - $percent);

    // Color interpolation
    $finalColor = $color ?? '#00FF00';
    if (isset($colorEnd)) {
        $c1 = sscanf($finalColor, "#%02x%02x%02x");
        $c2 = sscanf($colorEnd, "#%02x%02x%02x");
        if ($c1 && $c2) {
            $r = round($c1[0] + ($c2[0] - $c1[0]) * $percent);
            $g = round($c1[1] + ($c2[1] - $c1[1]) * $percent);
            $b = round($c1[2] + ($c2[2] - $c1[2]) * $percent);
            $finalColor = sprintf("#%02x%02x%02x", $r, $g, $b);
        }
    }

    // Build minor tick path (36 steps, inner r=82, outer r=85)
    $minorTicks = '';
    for ($i = 0; $i <= 36; $i++) {
        $angle = M_PI + (M_PI * $i / 36);
        $minorTicks .= sprintf(' M%s %s L%s %s',
            90 + 82 * cos($angle), 90 + 82 * sin($angle),
            90 + 85 * cos($angle), 90 + 85 * sin($angle));
    }

    // Build major tick path (9 steps, inner r=82, outer r=87)
    $majorTicks = '';
    for ($i = 0; $i <= 9; $i++) {
        $angle = M_PI + (M_PI * $i / 9);
        $majorTicks .= sprintf(' M%s %s L%s %s',
            90 + 82 * cos($angle), 90 + 82 * sin($angle),
            90 + 87 * cos($angle), 90 + 87 * sin($angle));
    }
@endphp
<ng-dial-gauge id="{{ $id ?? '' }}" style="fill:white; font-weight:100;"
     data-min="{{ $min }}" data-max="{{ $scaleMax }}" data-relative="{{ $relative ? 'true' : 'false' }}"
     data-color="{{ $color ?? '#00FF00' }}" data-color-end="{{ $colorEnd ?? '' }}">
    <div style="width:100%; height:100%;">
        <svg width="100%" height="100%">
            <g>
                <path d="{{ $minorTicks }}" stroke="#c0c0c0" stroke-width="0.5"></path>
                <path d="{{ $majorTicks }}" stroke="#c0c0c0" stroke-width="1"></path>
                <path d="M16 90 A 74 74,0,0,1,164 90" stroke="white" stroke-linecap="square" stroke-width="10" fill="transparent"></path>
                <text text-anchor="middle" x="90" y="110" class="dialgauge-title">{{ $title }}</text>
                <path class="gauge-arc" d="M16 90 A 74 74,0,0,1,164 90" stroke="{{ $finalColor }}" stroke-linecap="square" stroke-width="10" fill="transparent"
                      stroke-dasharray="{{ $dasharray }}" stroke-dashoffset="{{ $dashoffset }}"
                      style="transition: stroke-dashoffset 0.5s ease-out; {{ $value <= 0 ? 'opacity: 0.1' : '' }}"></path>
                <text text-anchor="middle" x="90" y="90"><tspan class="dialgauge-value">{{ number_format($value, 1) }}</tspan><tspan dx="3" class="dialgauge-unit">{{ $units }}</tspan></text>
            </g>
        </svg>
    </div>
</ng-dial-gauge>
