<figure class="mp-block mp-block-video">
    <video{!! $renderer->attributes([
        'src' => $src,
        'controls' => ($attrs['controls'] ?? true) ? true : null,
        'autoplay' => ($attrs['autoplay'] ?? false) ? true : null,
        'loop' => ($attrs['loop'] ?? false) ? true : null,
        'muted' => ($attrs['muted'] ?? false) || ($attrs['autoplay'] ?? false) ? true : null,
        'playsinline' => true,
        'preload' => 'metadata',
    ]) !!}></video>
    @if($attrs['caption'] ?? null)
        <figcaption dir="auto">{{ $attrs['caption'] }}</figcaption>
    @endif
</figure>
