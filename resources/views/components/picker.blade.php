@php($config = $config())
<x-forms::form-group :name="$dot_name" :label="$labelText()" :required="$required" :show-label="$showLabel">
    <div
        {{ $attributes->merge(['class' => 'mp-field']) }}
        data-mediapicker-field="{{ json_encode($config) }}"
        @if($errors->has($dot_name) || $errors->has("{$dot_name}.*")) aria-invalid="true" @endif
    >
        <ul class="mp-field__items">
            @foreach($items as $item)
                <li class="mp-field__item" data-id="{{ $item['id'] }}">
                    @if($item['thumb'])
                        <img src="{{ $item['thumb'] }}" alt="{{ $item['alt'] ?? '' }}" class="mp-thumb" loading="lazy">
                    @else
                        <span class="mp-thumb mp-thumb--icon">@include('mediapicker::partials.icon', ['name' => $item['icon']])</span>
                    @endif
                    <span class="mp-name" dir="auto" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                </li>
            @endforeach
        </ul>

        <p class="mp-field__empty" @if($items) hidden @endif>{{ __('Nothing chosen') }}</p>

        <div class="mp-field__inputs">
            @forelse($items as $item)
                <input type="hidden" name="{{ $input_name }}" value="{{ $item['id'] }}">
            @empty
                <input type="hidden" name="{{ $name }}" value="">
            @endforelse
        </div>

        <div class="mp-field__buttons">
            <button type="button" class="mp-button" id="{{ $id }}" data-mp-choose @disabled($disabled)
                @if($help) aria-describedby="{{ $id }}_help" @endif>
                @include('mediapicker::partials.icon', ['name' => $multiple ? 'images' : 'image'])
                {{ $items ? __('Change') : __('Choose') }}
            </button>
            <button type="button" class="mp-button mp-icon-button mp-button--danger" data-mp-clear @if(! $items || $disabled) hidden @endif>
                {{ __('Remove') }}
            </button>
        </div>
    </div>

    @if($help)
        <x-forms::input-help id="{{ $id }}_help">{{ $help }}</x-forms::input-help>
    @endif

    <x-forms::errors :name="$dot_name" />
    @if($multiple)
        <x-forms::errors :name="$dot_name . '.*'" />
    @endif
</x-forms::form-group>
