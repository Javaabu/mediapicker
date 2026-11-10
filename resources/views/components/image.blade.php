@php($sources = $sources())
@if($sources)
<picture>
    @foreach($sources as $source)
        <source type="{{ $source['type'] }}" srcset="{{ $source['srcset'] }}" @if($sizes) sizes="{{ $sizes }}" @endif>
    @endforeach
@endif
    <img {{ $attributes->merge([
        'src' => $src(),
        'srcset' => $srcset(),
        'sizes' => $srcset() ? $sizes : null,
        'width' => $width(),
        'height' => $height(),
        'alt' => $altText(),
        'loading' => $loading,
        'decoding' => 'async',
    ]) }}>
@if($sources)
</picture>
@endif
