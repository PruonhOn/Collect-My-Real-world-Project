@props(['name', 'label' => null, 'value' => '', 'required' => false, 'placeholder' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }} @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }}
        @class([
            'w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white',
            'border-red-300' => $errors->has($name),
        ])>
        {{ $slot }}
    </select>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
