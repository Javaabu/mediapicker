@php
    $row_id = 'media-'.$media->id.'-thumb';
    $is_selected = isset($selected) && in_array($media->id, $selected);
@endphp


{{--@can('update', $media)
<a href="{{ $media->admin_url }}">
@endcan--}}
<div id="{{ $row_id }}" title="{{ $media->name }}"
     class="card media-thumb square img-header mb-1 {{ $is_selected ? 'selected' : '' }}"
     style="background-image: url({{  $media->type_slug == 'image' ? $media->getUrl('preview') : $media->getUrl() }})">
    <div class="square-content card-body">
        @if(empty($no_checkbox))
            <div class="meta-action">
                <div class="checkbox checkbox--inverse">
                    @php $checkbox_id = $row_id.'-check'; @endphp
                    <input id="{{ $checkbox_id }}" data-check="media" name="media[]" value="{{ $media->id }}"
                           type="checkbox"/>
                    <label for="{{ $checkbox_id }}" class="checkbox__label"></label>
                </div>
            </div>
        @endif

        @if(empty($hide_actions))
            <div class="actions actions--inverse">
                @can('update', $media)
                    <a class="actions__item zmdi zmdi-edit" href="{{ $media->url('edit') }}"
                       title="Edit">
                    </a>
                @endcan

                @can('delete', $media)
                    <a class="actions__item delete-link zmdi zmdi-delete" href="#"
                       data-request-url="{{ $media->url('destroy') }}"
                       data-redirect-url="{{ Request::fullUrl() }}" title="Delete">
                    </a>
                @endcan
            </div>
        @endif

        @if($media->file_type != 'image')
            <i class="{{ $media->getIcon('material') }} media-icon"></i>
        @endif

        @if(! empty($selectable))
            <a href="{{ $media->getUrl() }}"
               data-large="{{ $media->file_type == 'image' ? $media->getUrl('mediapicker-large') : $media->getUrl() }}"
               data-thumb="{{ $media->file_type == 'image' ? $media->getUrl('mediapicker-thumb') : $media->getUrl() }}"
               data-select-media="{{ $media->id }}"
               data-media-type="{{ $media->file_type }}"
               data-file-name="{{ $media->name }}"
               data-media-icon="{{ $media->getIcon('material') }}"
               class="view-overlay"></a>
        @else
            @can('view', $media)
                <a href="{{ $media->admin_url }}" class="view-overlay"></a>
            @else
                <span class="view-overlay"></span>
            @endcan
        @endif
    </div>
</div>
<p class="mb-4 text-center text-muted">{{ $media->name }}</p>
{{--
@can('update', $media)
</a>
@endcan--}}
