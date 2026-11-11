<x-app-layout :title="__('messages.edit') . ' ' . __('messages.roles')" :active="'admin.roles'">
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 capitalize">{{ $role->name }}</h1>
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
        @csrf
        @method('PUT')
        <div class="space-y-6">
            @foreach ($permissions as $group => $groupPermissions)
                <x-card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 capitalize">{{ $group }}</h3>
                        <button type="button" onclick="selectGroup(this)" class="text-xs text-primary-600 font-medium">{{ __('messages.select_all') }}</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach ($groupPermissions as $permission)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->hasPermissionTo($permission->name)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" data-group-toggle>
                                <span class="text-xs text-gray-700 font-mono">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6">
            <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
        </div>
    </form>

    <script>
        function selectGroup(button) {
            const card = button.closest('.bg-white');
            const checkboxes = card.querySelectorAll('[data-group-toggle]');
            const allChecked = [...checkboxes].every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
    </script>
</x-app-layout>