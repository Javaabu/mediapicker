<figure class="block-image mp-block mp-block-image">
    @if($attrs['link'] ?? null)<a href="{{ $attrs['link'] }}">@endif
    <img{!! $renderer->attributes([
        'src' => $src,
        'alt' => $alt,
        'width' => $width,
        'height' => $height,
        'loading' => 'lazy',
        'decoding' => 'async',
    ]) !!}>
    @if($attrs['link'] ?? null)</a>@endif
    @if($attrs['caption'] ?? null)
        <figcaption dir="auto">{{ $attrs['caption'] }}</figcaption>
    @endif
</figure>
