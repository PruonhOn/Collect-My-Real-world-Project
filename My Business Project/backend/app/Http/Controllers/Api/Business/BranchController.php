<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Branch;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(Request $request): JsonResponse
    {
        $query = Branch::query()->orderBy('name');

        if ($request->query('status') !== 'all') {
            $query->where('is_active', true);
        }

        return response()->json($query->paginate(min(100, (int) $request->query('per_page', 15))));
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');

        if (! $this->gate->checkLimit($business, 'max_branches')) {
            return response()->json([
                'message' => 'Branch limit reached for your current plan.',
                'limit' => $this->gate->limit($business, 'max_branches'),
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['business_id'] = $business->id;
        $branch = Branch::create($validated);
        $this->gate->incrementUsage($business, 'max_branches');

        return response()->json(['data' => $branch], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorizeSameBusiness($branch);

        return response()->json(['data' => $branch]);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeSameBusiness($branch);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch->update($validated);

        return response()->json(['data' => $branch->fresh()]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $business = app('current.business');
        $this->authorizeSameBusiness($branch);

        $branch->delete();
        $this->gate->incrementUsage($business, 'max_branches', -1);

        return response()->json(['message' => 'Branch deleted.']);
    }

    protected function authorizeSameBusiness(Branch $branch): void
    {
        $business = app('current.business');
        if ($branch->business_id !== $business->id) {
            abort(404, 'Branch not found.');
        }
    }
}
