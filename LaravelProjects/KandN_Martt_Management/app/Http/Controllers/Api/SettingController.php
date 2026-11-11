<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Setting::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('key', 'like', "%{$search}%");
        }

        $settings = $query->latest()->paginate($request->per_page ?? 50);
        return $this->paginatedSuccess($settings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'required',
            'group' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->all();
        $data['created_by'] = auth()->id();

        if (is_array($data['value'])) {
            $data['value'] = json_encode($data['value']);
        }

        $setting = Setting::create($data);

        return $this->success($setting, 'Setting created successfully', 201);
    }

    public function show(int $id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return $this->error('Setting not found', 404);
        }

        return $this->success($setting);
    }

    public function update(Request $request, int $id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return $this->error('Setting not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|string|max:255|unique:settings,key,' . $id,
            'value' => 'sometimes',
            'group' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['key', 'value', 'group', 'type']);

        if (isset($data['value']) && is_array($data['value'])) {
            $data['value'] = json_encode($data['value']);
        }

        $setting->update($data);

        return $this->success($setting->fresh(), 'Setting updated successfully');
    }

    public function showByGroup(string $group)
    {
        $settings = Setting::where('group', $group)->get()->pluck('value', 'key');

        return $this->success($settings);
    }

    public function destroy(int $id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return $this->error('Setting not found', 404);
        }

        $setting->delete();

        return $this->success(null, 'Setting deleted successfully');
    }
}
