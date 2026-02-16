@extends('layouts.app')

@php $title = $title ?? 'Calendar'; @endphp

@section('content')
    <div id="calendar-content">
        @include('calendar.partial')
    </div>
@endsection
