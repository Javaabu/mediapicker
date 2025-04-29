@extends('mediapicker::material-admin-26.media.media')

@php
    $media_model = \Javaabu\Mediapicker\Mediapicker::mediaModel();
@endphp

@section('page-subheading')
    <small>{{ $title }}</small>
@endsection

@section('content')
    @if($media_items->isNotEmpty() || $media_model::exists())
        <div class="card">

            <x-forms::form
                :action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')"
                :model="request()->query()"
                id="filter"
                method="GET"
            >
                @include('mediapicker::material-admin-26.media._filter')
            </x-forms::form>

            @if($view == 'list')
                @include('mediapicker::material-admin-26.media._table')
            @else
                @include('mediapicker::material-admin-26.media._grid')
            @endif
        </div>
    @else
        <x-forms::no-items
            icon="zmdi zmdi-collection-folder-image"
            :create-action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('create')"
            :model-type="__('media')"
            :model="$media_model"
        />
    @endif
@endsection
