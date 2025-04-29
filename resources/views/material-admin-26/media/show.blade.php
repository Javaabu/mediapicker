@extends('mediapicker::material-admin-26.media.media')

@section('page-title', __('View Media'))

@section('content')
    @include('mediapicker::material-admin-26.media.details._preview')
    @include('mediapicker::material-admin-26.media.details._general')
@endsection
