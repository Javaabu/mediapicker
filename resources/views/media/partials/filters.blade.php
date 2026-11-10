{{-- The library's filters: a GET form, so it works without JavaScript --}}
<form method="GET" action="{{ $urls['index'] }}" class="flex flex-wrap items-end gap-3" role="search" data-mediapicker-filters>
    <label class="flex flex-1 flex-col gap-1 text-sm" style="min-inline-size: 14rem">
        <span>{{ __('Search') }}</span>
        <input type="search" name="search" value="{{ $filters['search'] }}" class="mp-input" dir="auto" placeholder="{{ __('Search media') }}">
    </label>

    @if(count($file_types) > 1)
        <label class="flex flex-col gap-1 text-sm">
            <span>{{ __('Type') }}</span>
            <select name="type" class="mp-input">
                <option value="">{{ __('All types') }}</option>
                @foreach($file_types as $type)
                    <option value="{{ $type['value'] }}" @selected(in_array($type['value'], $filters['type'], true))>{{ $type['label'] }}</option>
                @endforeach
            </select>
        </label>
    @endif

    @if($can['view_others'])
        <label class="flex flex-col gap-1 text-sm">
            <span>{{ __('Uploaded by') }}</span>
            <select name="owner" class="mp-input">
                <option value="">{{ __('Anyone') }}</option>
                <option value="mine" @selected($filters['owner'] === 'mine')>{{ __('Only mine') }}</option>
            </select>
        </label>
    @endif

    <label class="flex flex-col gap-1 text-sm">
        <span>{{ __('From') }}</span>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mp-input" dir="ltr">
    </label>

    <label class="flex flex-col gap-1 text-sm">
        <span>{{ __('To') }}</span>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mp-input" dir="ltr">
    </label>

    <label class="flex flex-col gap-1 text-sm">
        <span>{{ __('Sort by') }}</span>
        @php($sort = "{$filters['orderby']}:{$filters['order']}")
        <select name="sort" class="mp-input" data-mediapicker-sort>
            <option value="created_at:desc" @selected($sort === 'created_at:desc')>{{ __('Newest') }}</option>
            <option value="created_at:asc" @selected($sort === 'created_at:asc')>{{ __('Oldest') }}</option>
            <option value="name:asc" @selected($sort === 'name:asc')>{{ __('Name (A–Z)') }}</option>
            <option value="size:desc" @selected($sort === 'size:desc')>{{ __('Largest') }}</option>
        </select>
    </label>

    <input type="hidden" name="view" value="{{ $filters['view'] }}">

    <div class="flex gap-2">
        <x-forms::button type="submit" color="secondary">{{ __('Filter') }}</x-forms::button>
        <x-forms::link-button :url="$urls['index']" color="link">{{ __('Reset') }}</x-forms::link-button>
    </div>
</form>
