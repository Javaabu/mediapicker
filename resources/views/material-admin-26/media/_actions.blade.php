<div class="actions">
    @if(isset($media))
        @can('delete', $media)
            <a class="actions__item delete-link zmdi zmdi-delete" href="#"
               data-request-url="{{ $media->url('destroy') }}"
               data-redirect-url="{{ $media->url('index') }}" title="Delete">
                <span>{{ __('Delete') }}</span>
            </a>
        @endcan

        @can('viewLogs', $media)
            <a class="actions__item zmdi zmdi-assignment" href="{{ $media->log_url }}" target="_blank"
               title="View Logs">
                <span>{{ __('View Logs') }}</span>
            </a>
        @endcan

        @can('view', $media)
            <a class="actions__item zmdi zmdi-eye" href="{{ $media->url('show') }}" title="View">
                <span>{{ __('View') }}</span>
            </a>
        @endcan

        @can('update', $media)
            <a class="actions__item zmdi zmdi-edit" href="{{ $media->url('edit') }}" title="Edit">
                <span>{{ __('Edit') }}</span>
            </a>
        @endcan
    @endif

    @can('create', \Javaabu\Mediapicker\Mediapicker::mediaModel())
        <a class="actions__item zmdi zmdi-plus" href="{{ \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('create') }}" title="Add New">
            <span>{{ __('Add New') }}</span>
        </a>
    @endcan

    @can('viewAny', \Javaabu\Mediapicker\Mediapicker::mediaModel())
        <a class="actions__item zmdi zmdi-view-list-alt"
           href="{{ add_query_arg('view', 'list', \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')) }}" title="List View">
            <span>{{ __('View All') }}</span>
        </a>

        <a class="actions__item zmdi zmdi-apps"
           href="{{ add_query_arg('view', 'grid', \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')) }}" title="Grid View">
            <span>{{ __('View All') }}</span>
        </a>
    @endcan
</div>
