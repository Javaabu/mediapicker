@extends(config('mediapicker.default_layout'))

@section('title', 'Media Library')
@section('page-title', __('Media Library'))

@section('top-search')
    <x-forms::search-form :action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')" :placeholder="__('Search media library...')" />
@endsection

@section('model-actions')
    @include('mediapicker::material-admin-26.media._actions')
@endsection
