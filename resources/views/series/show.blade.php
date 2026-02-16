@extends('layouts.app')

@php $title = $serie->name; @endphp

@section('content')
    @include('series._details')
@endsection

@push('styles')
<style>
    .episode-row:hover {
        background: rgba(255,255,255,0.05) !important;
    }
    .btn-danger {
        background-color: #d9534f;
        border-color: #d43f3a;
    }
</style>
@endpush
@endsection
