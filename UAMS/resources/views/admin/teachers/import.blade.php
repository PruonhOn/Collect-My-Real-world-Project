<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">Import Teachers</h2>
            <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Teachers</a>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.teachers.do-import') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="file" :value="__('CSV / Excel file')" />
                    <input id="file" name="file" type="file" accept=".csv,.xlsx,.xls" required
                        class="mt-2 block w-full text-sm text-gray-700 border border-gray-200 rounded-xl cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-semibold hover:file:bg-indigo-100" />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Required columns (first row)</p>
                    <code class="text-xs text-indigo-700">name, email, employee_code, department_id</code>
                    <p class="text-xs text-gray-500 mt-2">Only <strong>name</strong>, <strong>email</strong> and <strong>employee_code</strong> are required. Imported users get the default password <code class="text-indigo-700">password</code>.</p>
                    <p class="text-xs text-gray-500 mt-1">Example row: <code class="text-indigo-700">Jane Smith, jane.smith@uams.edu, TCH-2026-010, 1</code></p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 hover:from-indigo-700 hover:to-violet-700 transition">
                        Upload &amp; Import
                    </button>
                    <a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
