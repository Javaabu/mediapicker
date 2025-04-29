<x-forms::filter>
    <div class="row">
        <div class="col-md-3">
            <x-forms::text name="search" :label="__('Search')" :placeholder="__('Search..')" :show-errors="false" :inline="false" />
        </div>

        <div class="col-md-3">
            <x-forms::per-page />
        </div>

        <div class="col-md-3">
            <x-forms::filter-submit :cancel-url="add_query_arg(compact('mode', 'single', 'type'), \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index'))" />
        </div>
    </div>

    <x-forms::hidden name="mode" value="picker" />

    @if(! empty($single))
        <x-forms::hidden name="single" value="1" />
    @endif

    @if(! empty($type))
        <x-forms::hidden name="type" :value="$type" />
    @endif
</x-forms::filter>
