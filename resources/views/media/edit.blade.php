@extends(config('mediapicker.layout', 'layouts.app'))

@section(config('mediapicker.layout_section', 'content'))
    <div class="mp-library flex flex-col gap-6" data-mediapicker>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold"><bdi>{{ $title }}</bdi></h1>

            <div class="flex flex-wrap gap-2">
                <x-forms::link-button :url="$urls['index']" color="secondary" size="sm" start-icon="chevron-left">
                    {{ __('Back to the library') }}
                </x-forms::link-button>
                <x-forms::link-button :url="$urls['show']" color="secondary" size="sm">{{ __('View') }}</x-forms::link-button>
                @if($can['delete'])
                    <form method="POST" action="{{ $urls['destroy'] }}" onsubmit="return confirm(@js(__('Delete this file? Pages that use it will lose it.')))">
                        @csrf
                        @method('DELETE')
                        <x-forms::button type="submit" color="danger" size="sm">
                            @include('mediapicker::partials.icon', ['name' => 'trash-2'])
                            {{ __('Delete') }}
                        </x-forms::button>
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
                {{ $form }}
            </x-forms::card>
        </div>

        @if($media->isImage() && $crop_presets && $can['update'])
            @manifestRequire('mediapicker::cropper')
            <x-forms::card :title="__('Crops')">
                <p class="text-sm text-muted-foreground">{{ __('Crops don\'t change the original. Images that use a crop are made again from it.') }}</p>
                <div data-mediapicker-cropper="{{ json_encode([
                    'url' => $urls['index'],
                    'media' => [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                        'width' => $media->width,
                        'height' => $media->height,
                        'crops' => (object) $media->getCustomProperty('crops', []),
                    ],
                    'presets' => $crop_presets,
                    'strings' => \Javaabu\Mediapicker\Support\Strings::all(),
                ]) }}">
                    <noscript>{{ __('Cropping needs JavaScript.') }}</noscript>
                </div>
            </x-forms::card>
        @endif
    </div>
@endsection
