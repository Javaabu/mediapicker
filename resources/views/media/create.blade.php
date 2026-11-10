@extends(config('mediapicker.layout', 'layouts.app'))

@section(config('mediapicker.layout_section', 'content'))
    <div class="mp-library flex flex-col gap-6" data-mediapicker>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold"><bdi>{{ $title }}</bdi></h1>

            <x-forms::link-button :url="$urls['index']" color="secondary" size="sm" start-icon="chevron-left">
                {{ __('Back to the library') }}
            </x-forms::link-button>
        </div>

        @include('mediapicker::media.partials.alerts')

        <x-forms::card :title="__('Upload media')">
            <x-forms::form method="POST" :action="$urls['store']" files>
                <x-forms::file name="file" :label="__('File')" :type="array_column($file_types, 'value')" :mimetypes="explode(',', $upload['accept'])" :max-size="$upload['max_size']" required />
                <x-forms::text name="name" :label="__('Name')" thaana />
                <x-forms::textarea name="description" :label="__('Description')" thaana />
                <x-forms::text name="alt" :label="__('Alt text')" thaana />
                <x-forms::text name="caption" :label="__('Caption')" thaana />

                <p class="text-sm text-muted-foreground">{{ __('After uploading an image, you can crop it on the next page.') }}</p>

                <x-forms::submit>{{ __('Upload') }}</x-forms::submit>
            </x-forms::form>
        </x-forms::card>
    </div>
@endsection
