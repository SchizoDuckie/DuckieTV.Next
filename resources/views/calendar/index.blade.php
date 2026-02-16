@extends('layouts.app')

@php $title = $title ?? 'Calendar'; @endphp

@section('content')

{{-- The date-picker attribute activates all [date-picker] CSS from main.css.
     The inner div uses ng-switch-when to match the original Angular CSS selectors:
       our "year" mode  = original ng-switch-when="month"  (month selector grid)
       our "month" mode = original ng-switch-when="date"   (day/date calendar grid)
       our "week" mode  = original ng-switch-when="week"   (single week strip)
--}}
<calendar>
    <div date-picker>

        @if($mode === 'year')
        <div ng-switch-when="month">
        @elseif($mode === 'week')
        <div ng-switch-when="week">
        @else
        <div ng-switch-when="date">
        @endif

            <table>
                <thead>
                    <tr>
                        {{-- Prev arrow --}}
                        @if($mode === 'year')
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'year', 'date' => $currentDate->copy()->subYear()->toDateString()]) }}'"></i></th>
                        @elseif($mode === 'week')
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'week', 'date' => $currentDate->copy()->subWeek()->toDateString()]) }}'"></i></th>
                        @else
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'month', 'date' => $currentDate->copy()->subMonth()->toDateString()]) }}'"></i></th>
                        @endif

                        {{-- Title (click to go up in hierarchy) --}}
                        <th colspan="5" class="switch">
                            @if($mode === 'week')
                                <h2 onclick="window.location.href='{{ route('calendar.index', ['mode' => 'month', 'date' => $currentDate->toDateString()]) }}'">{{ $currentDate->format('F Y') }}
                                    <i class="glyphicon glyphicon-chevron-down"></i>
                                </h2>
                            @elseif($mode === 'month')
                                <h2 onclick="window.location.href='{{ route('calendar.index', ['mode' => 'year', 'date' => $currentDate->toDateString()]) }}'">{{ $currentDate->format('F Y') }}
                                    <i class="glyphicon glyphicon-chevron-up" onclick="event.stopPropagation(); window.location.href='{{ route('calendar.index', ['mode' => 'week', 'date' => $currentDate->toDateString()]) }}'"></i>
                                </h2>
                            @else
                                <h2 onclick="window.location.href='{{ route('calendar.index', ['mode' => 'year', 'date' => $currentDate->toDateString()]) }}'" style="cursor: pointer;">{{ $currentDate->format('Y') }}</h2>
                            @endif
                        </th>

                        {{-- Next arrow --}}
                        @if($mode === 'year')
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'year', 'date' => $currentDate->copy()->addYear()->toDateString()]) }}'"></i></th>
                        @elseif($mode === 'week')
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'week', 'date' => $currentDate->copy()->addWeek()->toDateString()]) }}'"></i></th>
                        @else
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="window.location.href='{{ route('calendar.index', ['mode' => 'month', 'date' => $currentDate->copy()->addMonth()->toDateString()]) }}'"></i></th>
                        @endif
                    </tr>

                    {{-- Weekday headers (month and week views only) --}}
                    @if($mode === 'month' || $mode === 'week')
                        <tr>
                            <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                        </tr>
                    @endif
                </thead>

                {{-- YEAR VIEW --}}
                @if($mode === 'year')
                    <tbody>
                        <tr>
                            <td colspan="7" style="padding: 0;">
                                @for($m = 1; $m <= 12; $m++)
                                    @php
                                        $monthDate = \Carbon\Carbon::create($currentDate->year, $m, 1);
                                        $isCurrentMonth = now()->year === $currentDate->year && now()->month === $m;
                                    @endphp
                                    <span class="month {{ $isCurrentMonth ? 'now' : '' }}"
                                          onclick="window.location.href='{{ route('calendar.index', ['mode' => 'month', 'date' => $monthDate->toDateString()]) }}'">{{ $monthDate->format('M') }}</span>
                                @endfor
                            </td>
                        </tr>
                    </tbody>

                {{-- MONTH VIEW --}}
                @elseif($mode === 'month')
                    <tbody class="date">
                        @php 
                            $current = $start->copy();
                            $today = now()->startOfDay();
                            // $currentDate is the selected/focused date passed from controller
                            $focusedDate = $currentDate->copy()->startOfDay();
                        @endphp
                        @while($current <= $end)
                            @if($current->dayOfWeekIso === 1)
                                <tr>
                            @endif

                            @php
                                $dateStr = $current->toDateString();
                                $dayEvents = $events[$dateStr] ?? [];
                                $isToday = $current->isToday();
                                $isDifferentMonth = $current->month !== $currentDate->month; // $currentDate here is the View Month, not necessarily focused date for highlighting
                                
                                // Matches original isSameDay(day) -> active
                                $isActive = $current->isSameDay($focusedDate);
                                
                                // Matches original isAfter(day)
                                $isAfter = $current->gt($today);
                                
                                // Matches original isBefore(day)
                                $isBefore = $current->lt($today);
                            @endphp

                            <td class="day">
                                @foreach($dayEvents as $event)
                                    @php $ep = $event['episode']; $serie = $event['serie']; @endphp
                                    <div class="event">
                                        <a class="{{ $ep->watched ? 'watched' : '' }}" href="javascript:void(0)" data-sidepanel-show="{{ route('episodes.show', $ep->id) }}" style="display: block; width: 100%; cursor: pointer; text-decoration: none;">
                                            <div class="watchedwidth">
                                                <span class="eventName">
                                                    <span class="eventNameInner">{{ $serie->name }} - {{ $ep->formatted_episode }}</span>
                                                </span>
                                            </div>
                                            @if($ep->watched)
                                                <span class="glyphicon glyphicon-ok watchedpos"></span>
                                            @endif
                                        </a>
                                    </div>
                                @endforeach

                                <span class="{{ $isToday ? 'now' : '' }} {{ $isActive ? 'active' : '' }} {{ $isDifferentMonth ? 'disabled' : '' }} {{ $isAfter ? 'after' : '' }} {{ $isBefore ? 'before' : '' }}">
                                    <em class="dayofweek">{{ $current->format('D') }}
                                        @if(count($dayEvents) > 0)
                                            <a class="markday-button markdaydownloaded" onclick="document.getElementById('mark-dl-{{ $dateStr }}').submit()"><i class="glyphicon glyphicon-floppy-disk"></i></a>
                                            <a class="markday-button markdaywatched" onclick="document.getElementById('mark-w-{{ $dateStr }}').submit()"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @endif
                                    </em>
                                    <a href="{{ route('calendar.index', ['mode' => 'week', 'date' => $dateStr]) }}" style="color: inherit; text-decoration: none;">{{ $current->day }}</a>
                                </span>

                                @if(count($dayEvents) > 0)
                                    <form id="mark-dl-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-downloaded') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                    <form id="mark-w-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-watched') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                @endif
                            </td>

                            @if($current->dayOfWeekIso === 7)
                                </tr>
                            @endif
                            @php $current->addDay(); @endphp
                        @endwhile
                    </tbody>

                {{-- WEEK VIEW --}}
                @elseif($mode === 'week')
                    <tbody>
                        <tr>
                            @php $current = $start->copy(); @endphp
                            @while($current <= $end)
                                @php
                                    $dateStr = $current->toDateString();
                                    $dayEvents = $events[$dateStr] ?? [];
                                    $isToday = $current->isToday();
                                @endphp

                                <td class="day">
                                    @foreach($dayEvents as $event)
                                        @php $ep = $event['episode']; $serie = $event['serie']; @endphp
                                        <div class="event">
                                            <a class="{{ $ep->watched ? 'watched' : '' }}" href="javascript:void(0)" data-sidepanel-show="{{ route('episodes.show', $ep->id) }}" style="display: block; width: 100%; cursor: pointer; text-decoration: none;">
                                                <div class="watchedwidth">
                                                    <span class="eventName">
                                                        <span class="eventNameInner">{{ $serie->name }} - {{ $ep->formatted_episode }}</span>
                                                    </span>
                                                </div>
                                                @if($ep->watched)
                                                    <span class="glyphicon glyphicon-ok watchedpos"></span>
                                                @endif
                                            </a>
                                        </div>
                                    @endforeach

                                    <span class="{{ $isToday ? 'now' : '' }}">
                                        <em class="dayofweek">{{ $current->format('D') }}
                                            @if(count($dayEvents) > 0)
                                                <a class="markday-button markdaydownloaded" onclick="document.getElementById('mark-dl-{{ $dateStr }}').submit()"><i class="glyphicon glyphicon-floppy-disk"></i></a>
                                                <a class="markday-button markdaywatched" onclick="document.getElementById('mark-w-{{ $dateStr }}').submit()"><i class="glyphicon glyphicon-eye-open"></i></a>
                                            @endif
                                        </em>
                                        {{ $current->day }}
                                    </span>

                                    @if(count($dayEvents) > 0)
                                        <form id="mark-dl-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-downloaded') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                        <form id="mark-w-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-watched') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                    @endif
                                </td>
                                @php $current->addDay(); @endphp
                            @endwhile
                        </tr>
                    </tbody>
                @endif
            </table>
        </div>
    </div>
</calendar>

@endsection
