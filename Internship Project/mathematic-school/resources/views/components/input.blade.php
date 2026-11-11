@props(['name', 'label' => null, 'type' => 'text', 'value' => '', 'required' => false, 'placeholder' => null, 'help' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }} @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }} placeholder="{{ $placeholder }}"
        @class([
            'w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm',
            'border-red-300' => $errors->has($name),
        ])>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
    @if ($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif
</div>
