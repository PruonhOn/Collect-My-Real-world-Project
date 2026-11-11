<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $defaults = [
            'store_name' => 'K & N Fashion',
            'store_address' => '',
            'store_phone' => '',
            'store_email' => '',
            'currency' => 'USD',
            'tax_rate' => '0',
        ];

        $settings = array_merge($defaults, $settings);

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_address' => 'nullable|string',
            'store_phone' => 'nullable|string|max:50',
            'store_email' => 'nullable|email|max:255',
            'currency' => 'required|string|max:10',
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
