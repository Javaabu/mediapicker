@php $type = $media->file_type; @endphp
@if($type == 'image')
    <div class="card">
        <img src="{{ $media->getUrl() }}" class="img-fluid m-center" alt="">
    </div>
@elseif($type == 'video')
    <div class="card">
        <video style="height: 100%; width: 100%" {{--poster="{{ $media->getUrl('large') }}"--}}
        controls="controls" preload="none">
            <source type="video/mp4" src="{{ $media->getUrl() }}"/>
        </video>
    </div>
@else
    <div class="card square">
        <div class="square-content">
            <i class="{{ $media->getIcon('material') }} media-icon"></i>
        </div>
    </div>
@endif
