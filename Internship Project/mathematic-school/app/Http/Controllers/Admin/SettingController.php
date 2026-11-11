<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [
            'school_name' => school_setting('school_name', ''),
            'school_address' => school_setting('school_address', ''),
            'school_phone' => school_setting('school_phone', ''),
            'school_email' => school_setting('school_email', ''),
            'currency' => school_setting('currency', 'USD'),
            'academic_year' => school_setting('academic_year', ''),
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:50'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'academic_year' => ['nullable', 'string', 'max:50'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value);
        }

        return redirect()->route('admin.settings.edit')
            ->with('success', __('messages.settings_updated'));
    }
}
