<p class="mp-block mp-block-file">
    @include('mediapicker::partials.icon', ['name' => $icon])
    <a href="{{ $href }}" download dir="auto">{{ $label }}</a>
    @if($size)
        <small dir="ltr">{{ $size }}</small>
    @endif
</p>
