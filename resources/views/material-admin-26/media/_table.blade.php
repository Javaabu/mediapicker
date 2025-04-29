<x-forms::table
    model="media"
    :no-bulk="! empty($no_bulk)"
    :no-checkbox="! empty($no_checkbox)"
    :no-pagination="! empty($no_pagination)"
>

    @if(empty($no_bulk))
        <x-slot:bulk-form :action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('bulk')">
            @include('mediapicker::material-admin-26.media._bulk')
        </x-slot:bulk-form>
    @endif

        <x-slot:headers>
            <x-forms::table.heading :label="__('Name')" sortable="name" @if(! empty($compact)) colspan="2"@endif />
            @if(empty($compact))
            <x-forms::table.heading :label="__('Uploaded By')" />
            <x-forms::table.heading :label="__('Uploaded')" sortable="created_at" />
            @endif
        </x-slot:headers>

        <x-slot:rows>
            @if(!isset($media_items) || $media_items->isEmpty())
                <x-forms::table.empty-row :columns="empty($compact) ? 3 : 2" :no-checkbox="! empty($no_checkbox)">
                    {{ __('No matching media found.') }}
                </x-forms::table.empty-row>
            @else
                @include('mediapicker::material-admin-26.media._list')
            @endif
        </x-slot:rows>

        @if(empty($no_pagination))
            <x-slot:pagination>
                {{ $media_items->links('forms::material-admin-26.pagination') }}
            </x-slot:pagination>
        @endif

</x-forms::table>
