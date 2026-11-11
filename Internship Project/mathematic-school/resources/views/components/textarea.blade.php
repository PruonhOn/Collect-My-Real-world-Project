@props(['name', 'label' => null, 'rows' => 4, 'required' => false, 'placeholder' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }} @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }}
        placeholder="{{ $placeholder }}"
        @class([
            'w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm',
            'border-red-300' => $errors->has($name),
        ])>{{ old($name) }}</textarea>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
