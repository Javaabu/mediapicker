<x-forms::card>

    <x-forms::text-entry :label="__('URL')">
        <a href="{{ $media->getUrl() }}" target="_blank">
            <i class="zmdi zmdi-open-in-new mr-2"></i> {{ $media->getUrl() }}
        </a>
    </x-forms::text-entry>

    <x-forms::text name="name" required />

    <x-forms::textarea name="description" rows="3" class="auto-size" />

</x-forms::card>
