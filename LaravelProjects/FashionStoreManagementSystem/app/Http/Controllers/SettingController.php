<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('settings.index');
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $settings = [
            'store_name' => $request->input('store_name'),
            'store_tagline' => $request->input('store_tagline'),
            'store_address' => $request->input('store_address'),
            'store_phone' => $request->input('store_phone'),
            'store_email' => $request->input('store_email'),
            'currency' => $request->input('currency'),
            'tax_rate' => $request->input('tax_rate'),
            'receipt_footer' => $request->input('receipt_footer'),
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $settings['store_logo'] = $path;
        }

        Setting::setMany($settings);

        return back()->with('success', 'Store settings saved successfully.');
    }
}
