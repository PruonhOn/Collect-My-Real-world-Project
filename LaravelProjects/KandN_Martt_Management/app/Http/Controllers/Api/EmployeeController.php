<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Employee::with(['user:id,name,email', 'creator:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        $employees = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($employees);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,terminated',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->all();
        $data['created_by'] = auth()->id();

        $employee = Employee::create($data);

        return $this->success($employee->load(['user:id,name,email', 'creator:id,name']), 'Employee created successfully', 201);
    }

    public function show(int $id)
    {
        $employee = Employee::with(['user', 'creator:id,name'])->find($id);

        if (!$employee) {
            return $this->error('Employee not found', 404);
        }

        return $this->success($employee);
    }

    public function update(Request $request, int $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->error('Employee not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'employee_code' => 'sometimes|string|max:50|unique:employees,employee_code,' . $id,
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'position' => 'sometimes|string|max:100',
            'department' => 'sometimes|string|max:100',
            'hire_date' => 'sometimes|date',
            'salary' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,terminated',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $employee->update($request->all());

        return $this->success($employee->fresh()->load(['user:id,name,email', 'creator:id,name']), 'Employee updated successfully');
    }

    public function destroy(int $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->error('Employee not found', 404);
        }

        $employee->delete();

        return $this->success(null, 'Employee deleted successfully');
    }
}
