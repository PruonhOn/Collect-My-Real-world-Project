<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'group' => 'required|string',
        ]);

        $settings = $request->except(['_token', 'group']);
        $group = $request->group;

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key, 'group' => $group],
                ['value' => $value, 'created_by' => auth()->id()]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }
}
