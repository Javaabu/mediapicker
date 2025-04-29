@php
    $media_model = \Javaabu\Mediapicker\Mediapicker::mediaModel();
@endphp

@if($media_items->isNotEmpty() || $media_model::exists())
    <div class="card">
        <x-forms::form
            :action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')"
            :model="request()->query()"
            id="filter"
            method="GET"
        >
            @include('mediapicker::material-admin-26.media.picker._filter')
        </x-forms::form>
    </div>

    <div class="jscroll">
        @include('mediapicker::material-admin-26.media._grid', [
            'no_bulk' => true,
            'no_checkbox' => true,
            'hide_actions' => true,
            'selectable' => true,
        ])
    </div>

    @push(config('mediapicker.scripts_stack'))
        <script type="text/javascript" src="{{ asset('build/public/vendors/jscroll/dist/jquery.jscroll.min.js') }}"></script>
    @endpush
@else
    <x-forms::no-items
        icon="zmdi zmdi-collection-folder-image"
        :show-create="false"
        :message="__('Upload some new media')"
    />
@endif
