@php($thumb = \Javaabu\Mediapicker\Http\Resources\MediaResource::conversionUrl($media, 'mediapicker-thumb'))
@if($thumb)
    <img src="{{ $thumb }}" alt="" class="mp-thumb" loading="lazy">
@else
    <span class="mp-thumb mp-thumb--icon">@include('mediapicker::partials.icon', ['name' => $media->getIcon()])</span>
@endif
