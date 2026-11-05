<x-forms::table no-checkbox no-bulk no-pagination>
    <x-slot:headers>
        <x-forms::table.heading>{{ __('Select') }}</x-forms::table.heading>
        <x-forms::table.heading>{{ __('Name') }}</x-forms::table.heading>
        <x-forms::table.heading>{{ __('File type') }}</x-forms::table.heading>
        <x-forms::table.heading>{{ __('File size') }}</x-forms::table.heading>
        <x-forms::table.heading>{{ __('Uploaded') }}</x-forms::table.heading>
    </x-slot:headers>

    <x-slot:rows>
    @foreach($media_items as $media)
        @php($can_update = auth()->user()?->can('update', $media))
        <x-forms::table.row data-id="{{ $media->id }}">
            <x-forms::table.cell>
                @can('delete', $media)
                    <input type="checkbox" name="media[]" value="{{ $media->id }}" aria-label="{{ __('Select') }}: {{ $media->name }}">
                @endcan
            </x-forms::table.cell>
            <x-forms::table.cell>
                <a href="{{ $media->libraryUrl($can_update ? 'edit' : 'show') }}" class="flex items-center gap-3">
                    <span class="inline-block w-10">@include('mediapicker::media.partials.thumb')</span>
                    <bdi>{{ $media->name }}</bdi>
                </a>
            </x-forms::table.cell>
            <x-forms::table.cell><span dir="ltr">{{ $media->mime_type }}</span></x-forms::table.cell>
            <x-forms::table.cell><span dir="ltr">{{ $media->human_readable_size }}</span></x-forms::table.cell>
            <x-forms::table.cell>{{ $media->created_at?->isoFormat('LLL') }}</x-forms::table.cell>
        </x-forms::table.row>
    @endforeach
    </x-slot:rows>
</x-forms::table>
