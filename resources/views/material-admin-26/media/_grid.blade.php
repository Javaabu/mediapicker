@if($media_items->isEmpty())
    <div class="block-header">
        <p class="text-muted">
            {{ __('No matching media found.') }}
        </p>
    </div>
@else

    @if(empty($no_bulk))
        <x-slot:bulk-form :action="\Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('bulk')">
            @include('mediapicker::material-admin-26.media._bulk')
        </x-slot:bulk-form>
    @endif

    @if(empty($no_checkbox))
        <div class="mb-4">
            <div class="checkbox">
                <input id="media-select-all-resp" data-all="media" value="1" type="checkbox"/>
                <label for="media-select-all-resp"
                       class="checkbox__label font-weight-bold">{{ __('Select All') }}</label>
            </div>
        </div>
    @endif

    <div class="grid-wrapper">
        <div class="row">
            @foreach($media_items as $media)
                <div class="col-lg-2 col-md-3 col-6">
                    @include('mediapicker::material-admin-26.media._thumb')
                </div>

                @if(($loop->index + 1) % 6 == 0)
                    <div class="clearfix hidden-sm hidden-xs"></div>
                @endif

                @if(($loop->index + 1) % 4 == 0)
                    <div class="clearfix visible-sm"></div>
                @endif

                @if(($loop->index + 1) % 2 == 0)
                    <div class="clearfix visible-xs"></div>
                @endif
            @endforeach
        </div>

        @if(empty($no_pagination))
            <x-slot:pagination>
                {{ $media_items->links('forms::material-admin-26.pagination') }}
            </x-slot:pagination>
        @endif
    </div>

    @if(empty($no_bulk))
        <x-forms::form-close/>
    @endif
@endif
