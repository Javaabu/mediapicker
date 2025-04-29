@php
    $actions = [];

    if (auth()->user()->canDeleteAnyMedia()) {
        $actions['delete'] = __('Delete');
    }
@endphp

<x-forms::bulk-actions :actions="$actions" model="media" />

<x-forms::hidden name="view" :value="$view" />
