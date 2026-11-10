@extends(config('mediapicker.layout', 'layouts.app'))

@section(config('mediapicker.layout_section', 'content'))
    <div class="mp-library flex flex-col gap-6" data-mediapicker>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold"><bdi>{{ $title }}</bdi></h1>

            <div class="flex flex-wrap gap-2">
                <x-forms::link-button :url="$urls['index']" color="secondary" size="sm" start-icon="chevron-left">
                    {{ __('Back to the library') }}
                </x-forms::link-button>
                @if($can['update'])
                    <x-forms::link-button :url="$urls['edit']" size="sm">{{ __('Edit') }}</x-forms::link-button>
                @endif
                @if($can['delete'])
                    <form method="POST" action="{{ $urls['destroy'] }}" onsubmit="return confirm(@js(__('Delete this file? Pages that use it will lose it.')))">
                        @csrf
                        @method('DELETE')
                        <x-forms::button type="submit" color="danger" size="sm">{{ __('Delete') }}</x-forms::button>
                    </form>
                @endif
            </div>
        </div>

        @include('mediapicker::media.partials.alerts')

        <div class="grid gap-6 lg:grid-cols-2">
            <x-forms::card :title="__('Preview')">
                @include('mediapicker::media.partials.preview')
            </x-forms::card>

            <x-forms::card :title="__('Details')">
                <div class="flex flex-col gap-3">
                    <x-forms::text-entry :label="__('Name')" :value="$media->name" />
                    <x-forms::text-entry :label="__('File name')" :value="$media->file_name" />
                    <x-forms::text-entry :label="__('File type')" :value="$media->mime_type" />
                    <x-forms::text-entry :label="__('File size')" :value="$media->human_readable_size" />
                    @if($media->width && $media->height)
                        <x-forms::text-entry :label="__('Dimensions')" :value="$media->width . ' × ' . $media->height" />
                    @endif
                    <x-forms::text-entry :label="__('Uploaded')" :value="$media->created_at?->isoFormat('LLL')" />
                    @if($media->model?->name)
                        <x-forms::text-entry :label="__('Uploaded by')" :value="$media->model->name" />
                    @endif
                    @if($media->description)
                        <x-forms::text-entry :label="__('Description')" :value="$media->description" multiline />
                    @endif
                    @if($media->alt)
                        <x-forms::text-entry :label="__('Alt text')" :value="$media->alt" />
                    @endif
                    @if($media->caption)
                        <x-forms::text-entry :label="__('Caption')" :value="$media->caption" />
                    @endif

                    <label class="flex flex-col gap-1 text-sm">
                        <span>{{ __('URL') }}</span>
                        <input class="mp-input" dir="ltr" readonly value="{{ $media->getUrl() }}" onclick="this.select()">
                    </label>
                    <div>
                        <x-forms::link-button :url="$media->getUrl()" color="secondary" size="sm" target="_blank" rel="noreferrer">
                            @include('mediapicker::partials.icon', ['name' => 'download'])
                            {{ __('Download') }}
                        </x-forms::link-button>
                    </div>
                </div>
            </x-forms::card>
        </div>
    </div>
@endsection
