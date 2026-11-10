@if($media->file_type === 'image')
    <img src="{{ \Javaabu\Mediapicker\Http\Resources\MediaResource::conversionUrl($media, 'mediapicker-large') ?? $media->getUrl() }}"
         alt="{{ $media->alt ?? '' }}" @if($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         class="block h-auto max-w-full rounded-md">
@elseif($media->file_type === 'video')
    <video src="{{ $media->getUrl() }}" controls preload="metadata" class="block max-w-full rounded-md"></video>
@elseif($media->file_type === 'audio')
    <audio src="{{ $media->getUrl() }}" controls preload="metadata" class="block w-full"></audio>
@else
    <a href="{{ $media->getUrl() }}" target="_blank" rel="noreferrer" class="flex items-center gap-3 rounded-md border p-4">
        @include('mediapicker::partials.icon', ['name' => $media->getIcon(), 'class' => 'mp-icon h-8 w-8'])
        <bdi>{{ $media->file_name }}</bdi>
    </a>
@endif
