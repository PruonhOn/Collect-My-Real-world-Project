<x-app-layout :title="__('messages.edit') . ' ' . __('messages.payment')" :active="'admin.payments'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ __('messages.payment') }}</h1>
        <a href="{{ route('admin.payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.payments.update', $payment) }}">
        @csrf
        @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select name="student_id" :label="__('messages.student')" :required="true">
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id', $payment->student_id) == $student->id)>{{ $student->full_name }}</option>
                    @endforeach
                </x-select>
                <x-select name="course_id" :label="__('messages.course')" :required="true">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id', $payment->course_id) == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </x-select>
                <x-input name="amount" :label="__('messages.amount')" type="number" step="0.01" min="0" :value="old('amount', $payment->amount)" required />
                <x-input name="method" :label="__('messages.method')" :value="old('method', $payment->method)" />
                <x-input name="transaction_id" :label="__('messages.transaction_id')" :value="old('transaction_id', $payment->transaction_id)" />
                <x-select name="status" :label="__('messages.status')" :required="true">
                    <option value="pending" @selected(old('status', $payment->status) === 'pending')>{{ __('messages.pending') }}</option>
                    <option value="paid" @selected(old('status', $payment->status) === 'paid')>{{ __('messages.paid') }}</option>
                    <option value="failed" @selected(old('status', $payment->status) === 'failed')>{{ __('messages.failed') }}</option>
                    <option value="refunded" @selected(old('status', $payment->status) === 'refunded')>{{ __('messages.refunded') }}</option>
                </x-select>
                <x-input name="paid_date" :label="__('messages.paid_date')" type="date" :value="old('paid_date', $payment->paid_date?->format('Y-m-d'))" />
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes') }}</label>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('notes', $payment->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.payments.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>