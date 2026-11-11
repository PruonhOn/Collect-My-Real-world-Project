<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\AcademicYear\StoreAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends BaseApiController
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = AcademicYear::query()->orderByDesc('start_date');

        return response()->json([
            'data' => AcademicYearResource::collection($query->get()),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        if ($academicYear->is_current) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
        }

        $this->audit->record('academic_year.created', $academicYear, after: $academicYear->toArray());

        return response()->json([
            'data' => new AcademicYearResource($academicYear),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(AcademicYear $academicYear): JsonResponse
    {
        return response()->json([
            'data' => new AcademicYearResource($academicYear),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'unique:academic_years,name,'.$academicYear->id],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        $academicYear->fill($validated)->save();

        if ($academicYear->is_current) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
        }

        $this->audit->record('academic_year.updated', $academicYear, after: $academicYear->toArray());

        return response()->json([
            'data' => new AcademicYearResource($academicYear),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->courses()->exists()) {
            return response()->json([
                'data' => ['message' => 'Academic year has courses and cannot be deleted. Archive it instead.'],
                'meta' => null,
                'errors' => null,
            ], 409);
        }

        $this->audit->record('academic_year.deleted', $academicYear, before: $academicYear->toArray());
        $academicYear->delete();

        return response()->json([
            'data' => ['message' => 'Academic year deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
