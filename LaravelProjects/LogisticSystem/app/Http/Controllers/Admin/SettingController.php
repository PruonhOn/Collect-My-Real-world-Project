<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        $settings = $this->settingService->getAll();
        $groups = $settings->groupBy('group');
        return view('admin.settings.index', compact('settings', 'groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        try {
            $this->settingService->update($validated['settings']);

            $this->auditLogService->log('updated', 'settings', 'System settings updated', null, null, $validated['settings'], auth()->id());

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
}
