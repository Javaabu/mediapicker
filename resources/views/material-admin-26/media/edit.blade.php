@extends('mediapicker::material-admin-26.media.media')

@section('page-title', __('Edit Media'))

@section('content')
    <div class="row">
        <div class="col-md-8">
            @include('mediapicker::material-admin-26.media.details._preview')
            @include('mediapicker::material-admin-26.media.details._general')
        </div>
        <div class="col-md-4">
            <x-forms::form method="PATCH" :model="$media" :action="$media->url('update')">
                @include('mediapicker::material-admin-26.media.form._general')

                @include('mediapicker::material-admin-26.media.form._submit')
            </x-forms::form>
        </div>
    </div>
@endsection
