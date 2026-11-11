<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Models\AcademicTerm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicTermController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('permission:manage-system');

        $terms = AcademicTerm::latest('start_date')->paginate(15);

        return view('admin.academic-terms.index', compact('terms'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        return view('admin.academic-terms.create');
    }

    public function store(StoreAcademicTermRequest $request)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $data = $request->validated();

            if (!empty($data['is_active']) && $data['is_active']) {
                AcademicTerm::where('is_active', true)->update(['is_active' => false]);
            }

            AcademicTerm::create($data);

            DB::commit();

            return redirect()->route('admin.academic-terms.index')
                ->with('success', 'Academic term created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.academic-terms.create')
                ->with('error', 'Failed to create academic term: ' . $e->getMessage());
        }
    }

    public function show(AcademicTerm $academicTerm)
    {
        $this->authorize('permission:manage-system');

        $academicTerm->load(['classSections.course', 'classSections.teacher']);

        return view('admin.academic-terms.show', ['term' => $academicTerm, 'academicTerm' => $academicTerm]);
    }

    public function edit(AcademicTerm $academicTerm)
    {
        $this->authorize('permission:manage-system');

        return view('admin.academic-terms.edit', ['term' => $academicTerm, 'academicTerm' => $academicTerm]);
    }

    public function update(StoreAcademicTermRequest $request, AcademicTerm $academicTerm)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $data = $request->validated();

            if (!empty($data['is_active']) && $data['is_active'] && !$academicTerm->is_active) {
                AcademicTerm::where('is_active', true)
                    ->where('id', '!=', $academicTerm->id)
                    ->update(['is_active' => false]);
            }

            $academicTerm->update($data);

            DB::commit();

            return redirect()->route('admin.academic-terms.index')
                ->with('success', 'Academic term updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.academic-terms.edit', $academicTerm)
                ->with('error', 'Failed to update academic term: ' . $e->getMessage());
        }
    }

    public function destroy(AcademicTerm $academicTerm)
    {
        $this->authorize('permission:manage-system');

        if ($academicTerm->classSections()->count() > 0) {
            return redirect()->route('admin.academic-terms.index')
                ->with('error', 'Cannot delete term with associated class sections.');
        }

        $academicTerm->delete();

        return redirect()->route('admin.academic-terms.index')
            ->with('success', 'Academic term deleted successfully.');
    }

    public function activate(AcademicTerm $academicTerm)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            AcademicTerm::where('is_active', true)->update(['is_active' => false]);
            $academicTerm->update(['is_active' => true]);

            DB::commit();

            return redirect()->route('admin.academic-terms.index')
                ->with('success', 'Academic term activated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.academic-terms.index')
                ->with('error', 'Failed to activate academic term.');
        }
    }
}
