<div class="media-details card bg-gray text-white">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>{{ __('File Name:') }}</dt>
                    <dd>{{ $media->file_name }}</dd>
                </dl>

                <dl>
                    <dt>{{ __('File Type:') }}</dt>
                    <dd>{{ $media->mime_type }}</dd>
                </dl>

                <dl>
                    <dt>{{ __('File Size:') }}</dt>
                    <dd>{{ $media->human_readable_size }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                @if($media->file_type == 'image')
                    @php
                        $width = $media->width;
                        $height = $media->height;
                    @endphp
                    <dl>
                        <dt>{{ __('Dimensions:') }}</dt>
                        <dd>{{ __(':width x :height', compact('width', 'height')) }}</dd>
                    </dl>
                @endif

                <dl>
                    <dt>{{ __('Uploaded On:') }}</dt>
                    <dd>{{ $media->created_at ? $media->created_at->format('F j, Y \a\t H:i') : '-' }}</dd>
                </dl>

                <dl>
                    <dt>{{ $media->model instanceof \Javaabu\Mediapicker\Contracts\MediaOwner ? __('Uploaded By') : __('Uploaded To') }}</dt>
                    <dd>{!! $media->model ? $media->model->admin_link : '-' !!}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
