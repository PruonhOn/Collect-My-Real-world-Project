<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get();
        $groupedSettings = $settings->groupBy('group');

        if ($request->ajax()) {
            return response()->json(['settings' => $groupedSettings]);
        }

        return view('settings.index', compact('groupedSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting && $setting->is_editable) {
                $setting->update(['value' => $value]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Settings updated successfully']);
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
