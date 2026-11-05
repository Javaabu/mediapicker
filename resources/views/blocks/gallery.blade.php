<figure class="mp-block mp-block-gallery" style="--mp-gallery-columns: {{ $columns }}">
    <ul>
        @foreach($images as $image)
            <li>
                <a href="{{ $image['src'] }}">
                    <img{!! $renderer->attributes([
                        'src' => $image['thumb'],
                        'alt' => $image['alt'],
                        'width' => $image['width'],
                        'height' => $image['height'],
                        'loading' => 'lazy',
                        'decoding' => 'async',
                    ]) !!}>
                </a>
            </li>
        @endforeach
    </ul>
    @if($attrs['caption'] ?? null)
        <figcaption dir="auto">{{ $attrs['caption'] }}</figcaption>
    @endif
</figure>
