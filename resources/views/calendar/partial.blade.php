{{-- The date-picker attribute activates all [date-picker] CSS from main.css.
     The inner div uses ng-switch-when to match the original Angular CSS selectors:
       our "year" mode  = original ng-switch-when="month"  (month selector grid)
       our "month" mode = original ng-switch-when="date"   (day/date calendar grid)
       our "week" mode  = original ng-switch-when="week"   (single week strip)
--}}
<calendar>
    <div date-picker>

        @if($mode === 'decade')
        <div ng-switch-when="year">
        @elseif($mode === 'year')
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
                        @if($mode === 'decade')
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="Calendar.navigate('decade', '{{ $currentDate->copy()->subYears(10)->toDateString() }}')"></i></th>
                        @elseif($mode === 'year')
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="Calendar.navigate('year', '{{ $currentDate->copy()->subYear()->toDateString() }}')"></i></th>
                        @elseif($mode === 'week')
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="Calendar.navigate('week', '{{ $currentDate->copy()->subWeek()->toDateString() }}')"></i></th>
                        @else
                            <th><i class="glyphicon glyphicon-chevron-left" onclick="Calendar.navigate('month', '{{ $currentDate->copy()->subMonth()->toDateString() }}')"></i></th>
                        @endif

                        {{-- Title (click to go up in hierarchy) --}}
                        <th colspan="5" class="switch">
                            @if($mode === 'week')
                                <h2 onclick="Calendar.navigate('month', '{{ $currentDate->toDateString() }}')">{{ $currentDate->format('F Y') }}
                                    <i class="glyphicon glyphicon-chevron-down"></i>
                                </h2>
                            @elseif($mode === 'month')
                                <h2 onclick="Calendar.navigate('year', '{{ $currentDate->toDateString() }}')">{{ $currentDate->format('F Y') }}
                                    <i class="glyphicon glyphicon-chevron-up" onclick="event.stopPropagation(); Calendar.navigate('week', '{{ $currentDate->toDateString() }}')"></i>
                                </h2>
                            @elseif($mode === 'year')
                                <h2 onclick="Calendar.navigate('decade', '{{ $currentDate->toDateString() }}')">{{ $currentDate->format('Y') }}</h2>
                            @elseif($mode === 'decade')
                                <h2>{{ $startYear }}-{{ $endYear }}</h2>
                            @else
                                <h2 onclick="Calendar.navigate('year', '{{ $currentDate->toDateString() }}')" style="cursor: pointer;">{{ $currentDate->format('Y') }}</h2>
                            @endif
                        </th>

                        {{-- Next arrow --}}
                        @if($mode === 'decade')
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="Calendar.navigate('decade', '{{ $currentDate->copy()->addYears(10)->toDateString() }}')"></i></th>
                        @elseif($mode === 'year')
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="Calendar.navigate('year', '{{ $currentDate->copy()->addYear()->toDateString() }}')"></i></th>
                        @elseif($mode === 'week')
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="Calendar.navigate('week', '{{ $currentDate->copy()->addWeek()->toDateString() }}')"></i></th>
                        @else
                            <th><i class="glyphicon glyphicon-chevron-right" onclick="Calendar.navigate('month', '{{ $currentDate->copy()->addMonth()->toDateString() }}')"></i></th>
                        @endif
                    </tr>

                    {{-- Weekday headers (month and week views only) --}}
                    @if($mode === 'month' || $mode === 'week')
                        <tr>
                            <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                        </tr>
                    @endif
                </thead>

                {{-- DECADE VIEW --}}
                @if($mode === 'decade')
                    <tbody>
                        <tr>
                            <td colspan="7" style="padding: 0;">
                                @foreach($years as $year => $count)
                                    @php
                                        $yearDate = \Carbon\Carbon::create($year, 1, 1);
                                        $isCurrentYear = now()->year === $year;
                                    @endphp
                                    <span class="year {{ $isCurrentYear ? 'now' : '' }}"
                                          onclick="Calendar.navigate('year', '{{ $yearDate->toDateString() }}')">{{ $year }}</span>
                                @endforeach
                            </td>
                        </tr>
                    </tbody>

                {{-- YEAR VIEW --}}
                @elseif($mode === 'year')
                    <tbody>
                        <tr>
                            <td colspan="7" style="padding: 0;">
                                @foreach($months as $month => $count)
                                    @php
                                        $monthDate = \Carbon\Carbon::create($currentDate->year, $month, 1);
                                        $isCurrentMonth = now()->year === $currentDate->year && now()->month === $month;
                                        // Fix: $count is an integer from the query, not an object. 
                                        // The query in CalendarService returns array of counts keyed by month index.
                                        $hasEpisodes = $count > 0;
                                    @endphp
                                    <span class="month {{ $isCurrentMonth ? 'active' : '' }}"
                                          onclick="Calendar.navigate('month', '{{ $monthDate->toDateString() }}')">
                                        {{ $monthDate->format('M') }}
                                        @if($hasEpisodes) <i>{{ $count }}</i> @endif
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    </tbody>

                {{-- WEEK VIEW --}}
                @elseif($mode === 'week')
                    <tbody>
                        <tr>
                           @php
                               $current = $start->copy();
                           @endphp
                           @for($i = 0; $i < 7; $i++)
                                @php
                                    $dateStr = $current->toDateString();
                                    $dayEvents = $events[$dateStr] ?? [];
                                    $isToday = $current->isToday();
                                @endphp
                                <td class="day" style="width: 14.28%; vertical-align: top; height: 100%;">
                                    <span class="{{ $isToday ? 'now' : '' }}" style="display:block; text-align:center; font-weight:bold; margin-bottom: 5px;">
                                        {{ $current->format('D j') }}
                                    </span>

                                    @foreach($dayEvents as $event)
                                        @php $ep = $event['episode']; $serie = $event['serie']; @endphp
                                        <div class="event">
                                            <a class="{{ $ep->watched ? 'watched' : '' }}" href="javascript:void(0)" 
                                               data-sidepanel-show="{{ route('episodes.show', $ep->id) }}" 
                                               @if($ep->magnetHash) data-magnet-hash="{{ $ep->magnetHash }}" @endif
                                               style="display: block; width: 100%; cursor: pointer; text-decoration: none; position: relative; overflow: hidden;">
                                                <div class="watchedwidth" style="position: relative; z-index: 2;">
                                                    <span class="eventName">
                                                        <span class="eventNameInner">{{ $serie->name }} - {{ $ep->formatted_episode }}</span>
                                                    </span>
                                                </div>
                                                @if($ep->watched)
                                                    <span class="glyphicon glyphicon-ok watchedpos" style="z-index: 2;"></span>
                                                @endif

                                                @if(settings('torrenting.enabled') && settings('torrenting.progress') && $ep->magnetHash)
                                                    <div class="torrent-mini-remote-control-progress progress-striped progress" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; margin: 0; opacity: 0.3; z-index: 1;">
                                                        <span class="progress-bar {{ $ep->downloaded ? 'progress-bar-success' : 'progress-bar-info' }}" style="width: {{ $ep->downloaded ? '100%' : '0%' }}"></span>
                                                    </div>
                                                @endif
                                            </a>
                                        </div>
                                    @endforeach
                                    
                                    @if(count($dayEvents) > 0)
                                         <div style="text-align: center; margin-top: 5px;">
                                            <a class="markday-button markdaydownloaded" onclick="document.getElementById('mark-dl-{{ $dateStr }}').submit()" title="Mark day as downloaded"><i class="glyphicon glyphicon-floppy-disk"></i></a>
                                            <a class="markday-button markdaywatched" onclick="document.getElementById('mark-w-{{ $dateStr }}').submit()" title="Mark day as watched"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        </div>
                                        <form id="mark-dl-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-downloaded') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                        <form id="mark-w-{{ $dateStr }}" method="POST" action="{{ route('calendar.mark-watched') }}" style="display:none;">@csrf<input type="hidden" name="date" value="{{ $dateStr }}"></form>
                                    @endif
                                </td>
                                @php $current->addDay(); @endphp
                           @endfor
                        </tr>
                    </tbody>

                {{-- MONTH VIEW --}}
                @else
                    <tbody>
                        <tr>
                            @php
                                $today = now()->startOfDay();
                                $current = $start->copy();
                            @endphp

                            @while($current->lte($end))
                                @if($current->dayOfWeek === Carbon\Carbon::MONDAY && $current->ne($start))
                                    </tr><tr>
                                @endif

                                @php
                                    $dateStr = $current->toDateString();
                                    $dayEvents = $events[$dateStr] ?? [];
                                    $isActive = $current->month === $currentDate->month;
                                    $isDifferentMonth = !$isActive;
                                    $isToday = $current->isToday();
                                    $isAfter = $current->gt($today);
                                    $isBefore = $current->lt($today);
                                @endphp

                                <td class="day">
                                    @foreach($dayEvents as $event)
                                        @php $ep = $event['episode']; $serie = $event['serie']; @endphp
                                        <div class="event">
                                            <a class="{{ $ep->watched ? 'watched' : '' }}" href="javascript:void(0)" 
                                               data-sidepanel-show="{{ route('episodes.show', $ep->id) }}"
                                               @if($ep->magnetHash) data-magnet-hash="{{ $ep->magnetHash }}" @endif 
                                               style="display: block; width: 100%; cursor: pointer; text-decoration: none; position: relative; overflow: hidden;">
                                                <div class="watchedwidth" style="position: relative; z-index: 2;">
                                                    <span class="eventName">
                                                        <span class="eventNameInner">{{ $serie->name }} - {{ $ep->formatted_episode }}</span>
                                                    </span>
                                                </div>
                                                @if($ep->watched)
                                                    <span class="glyphicon glyphicon-ok watchedpos" style="z-index: 2;"></span>
                                                @endif
                                                
                                                @if(settings('torrenting.enabled') && settings('torrenting.progress') && $ep->magnetHash)
                                                    <div class="torrent-mini-remote-control-progress progress-striped progress" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; margin: 0; opacity: 0.3; z-index: 1;">
                                                        <span class="progress-bar {{ $ep->downloaded ? 'progress-bar-success' : 'progress-bar-info' }}" style="width: {{ $ep->downloaded ? '100%' : '0%' }}"></span>
                                                    </div>
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
