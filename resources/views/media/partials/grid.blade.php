<ul class="mp-grid m-0 list-none p-0" aria-label="{{ __('Media library') }}">
    @foreach($media_items as $media)
        @php($can_update = auth()->user()?->can('update', $media))
        @php($can_delete = auth()->user()?->can('delete', $media))
        <li class="mp-item" data-id="{{ $media->id }}">
            <a href="{{ $media->url($can_update ? 'edit' : 'show') }}" class="flex flex-col gap-1">
                @include('mediapicker::media.partials.thumb')
                <span class="mp-name" dir="auto" title="{{ $media->name }}">{{ $media->name }}</span>
            </a>
            @if($can_delete)
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="media[]" value="{{ $media->id }}">
                    <span class="sr-only">{{ __('Select') }}: </span><bdi class="sr-only">{{ $media->name }}</bdi>
                    <span aria-hidden="true">{{ __('Select') }}</span>
                </label>
            @endif
        </li>
    @endforeach
</ul>
