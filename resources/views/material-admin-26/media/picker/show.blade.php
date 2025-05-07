@extends(config('mediapicker.picker_layout'))

@section('content')

    @php
        $tabs = [
            [
                'name' => 'select-media',
                'title' => __('Select from Media Library'),
            ],
        ];

        if (auth()->user()->can('create', \Javaabu\Mediapicker\Mediapicker::mediaModel())) {
            $tabs[] = [
                'name' => 'new-media',
                'title' => __('Upload New')
            ];
        }
    @endphp

    <div class="container-fluid media-picker" data-single="{{ $single ? 'true' : 'false' }}">
        <x-forms::tabs :tabs="$tabs">
            <x-slot:select-media>
                @include('mediapicker::material-admin-26.media.picker._index')
            </x-slot:select-media>

            @can('create', \Javaabu\Mediapicker\Mediapicker::mediaModel())
                <x-slot:new-media>
                    @include('mediapicker::material-admin-26.media.form._uploader', ['view' => 'grid'])
                </x-slot:new-media>
            @endcan
        </x-forms::tabs>
    </div>
@endsection
