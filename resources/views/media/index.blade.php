@extends(config('mediapicker.layout', 'layouts.app'))

@section(config('mediapicker.layout_section', 'content'))
    <div class="mp-library flex flex-col gap-6" data-mediapicker>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold"><bdi>{{ $title }}</bdi></h1>

            @if($can['create'])
                <x-forms::link-button :url="$urls['create']">
                    @include('mediapicker::partials.icon', ['name' => 'upload'])
                    {{ __('Upload media') }}
                </x-forms::link-button>
            @endif
        </div>

        @include('mediapicker::media.partials.alerts')

        @include('mediapicker::media.partials.filters')

        <div class="flex items-center justify-end gap-2" role="group" aria-label="{{ __('View') }}">
            @foreach(['grid' => __('Grid'), 'list' => __('List')] as $view => $label)
                <a href="{{ request()->fullUrlWithQuery(['view' => $view, 'page' => null]) }}"
                   class="mp-button" @if($filters['view'] === $view) aria-current="true" @endif>
                    @include('mediapicker::partials.icon', ['name' => $view === 'grid' ? 'grid-2x2' : 'list'])
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if($media_items->isEmpty())
            <x-forms::no-items :title="$filters['search'] || $filters['type'] || $filters['owner'] ? __('No media matches these filters.') : __('No media yet')" />
        @else
            <form method="POST" action="{{ $urls['bulk'] }}?{{ http_build_query(['view' => $filters['view']]) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="delete">

                @include('mediapicker::media.partials.' . $filters['view'])

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <x-forms::button type="submit" color="danger" onclick="return confirm(@js(__('Delete the selected files? Pages that use them will lose them.')))">
                        @include('mediapicker::partials.icon', ['name' => 'trash-2'])
                        {{ __('Delete selected') }}
                    </x-forms::button>

                    @if($media_items->hasPages())
                        <x-forms::pagination :paginator="$media_items" />
                    @endif
                </div>
            </form>
        @endif
    </div>
@endsection
