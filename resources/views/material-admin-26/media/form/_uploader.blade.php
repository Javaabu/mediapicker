<x-forms::card :title="__('Upload Media')">
    @if(! empty($type))
        <div id="append-data">
            <x-forms::hidden name="type" :value="$type" />
        </div>
    @endif

    <h6 class="card-subtitle">
        {{ __('Drop files here to upload.') }}

        @if($max_size = \Javaabu\Helpers\Media\AllowedMimeTypes::getMaxFileSize($type ?? ''))
            {{ __('You can upload up to maximum :size per file.', ['size' => format_file_size($max_size)]) }}
        @endif
    </h6>

    <div id="files-drop" class="dropzone">
    </div>
</x-forms::card>

<div class="{{ $view == 'grid' ? '' : 'card' }} files-card" style="display: none">
    @if($view == 'grid')
        <div class="uploaded-files">
            <div class="row"></div>
        </div>
    @else
        @component('mediapicker::material-admin-26.media._table', [
            'no_bulk' => true,
            'no_checkbox' => true,
            'no_pagination' => true,
            'table_class' => 'uploaded-files mb-0',
            'compact' => true
        ])
        @endcomponent
    @endif
</div>


@push(config('mediapicker.scripts_stack'))
    @include('mediapicker::material-admin-26.media.form._upload-script')
@endpush
