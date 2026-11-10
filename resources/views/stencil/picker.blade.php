{{-- The stencil `mediapicker` field type (Javaabu\Mediapicker\Stencil\MediaPickerField) --}}
@php($value = $stencil->value($node))
<x-mediapicker::picker
    :name="str_replace('[]', '', $node->name)"
    :label="(string) $node->prop('label', '')"
    :help="(string) $node->prop('help', '')"
    :required="(bool) $node->prop('required')"
    :disabled="(bool) $node->prop('disabled')"
    :multiple="(bool) $node->prop('multiple')"
    :type="$node->prop('type', [])"
    :upload="(bool) $node->prop('upload', true)"
    :route="$node->prop('route')"
    :url="$node->prop('url') ?: null"
    :value="$value === null ? [] : \Illuminate\Support\Arr::wrap($value)"
/>
