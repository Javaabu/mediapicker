@php
    $media_model = \Javaabu\Mediapicker\Mediapicker::mediaModel();
@endphp

<x-forms::filter>
    <div class="row">
        <div class="col-md-3">
            <x-forms::text name="search" :label="__('Search')" :placeholder="__('Search..')" :show-errors="false" :inline="false" />
        </div>
        <div class="col-md-3">
            <x-forms::select2
                name="date_field"
                :label="__('Date to Filter')"
                :options="$media_model::getDateFieldsList()"
                allow-clear
                :show-errors="false"
            />
        </div>
        <div class="col-md-3">
            <x-forms::datetime name="date_from" :show-errors="false" />
        </div>
        <div class="col-md-3">
            <x-forms::datetime name="date_to" :show-errors="false" />
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <x-forms::select2
                name="type"
                :label="__('Type')"
                :placeholder="__('Nothing Selected')"
                :options="\Javaabu\Helpers\Media\AllowedMimeTypes::getTypeLabels()"
                allow-clear
                :show-errors="false"
            />
        </div>
        <div class="col-md-3">
            <x-forms::per-page />
        </div>
        <div class="col-md-3">
            <x-forms::filter-submit :cancel-url="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index')" />
        </div>
    </div>
</x-forms::filter>
