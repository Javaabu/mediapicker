@extends('mediapicker::material-admin-26.media.media')

@section('page-title', __('Add Media'))

@section('content')
    @include('mediapicker::material-admin-26.media.form._uploader', ['view' => 'list'])
@endsection
