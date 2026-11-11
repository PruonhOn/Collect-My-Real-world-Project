<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationMember;
use App\Models\OrganizationPartner;
use App\Models\OrganizationPosition;
use App\Models\OrganizationResponsibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminOrganizationController extends Controller
{
    private const PHOTO_DIR = 'organization';

    public function structure(Request $request): JsonResponse
    {
        $departments = OrganizationDepartment::with(['members' => function ($q) {
            $q->with(['position', 'department', 'reportsTo', 'responsibilities', 'partner'])
                ->orderBy('sort_order')
                ->orderBy('id');
        }, 'positions'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $members = OrganizationMember::with(['position', 'department', 'reportsTo', 'responsibilities', 'partner'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'departments' => $departments,
            'members' => $members,
            'positions' => OrganizationPosition::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function reorderDepartments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'departments' => ['required', 'array'],
            'departments.*.id' => ['required', 'integer', 'exists:organization_departments,id'],
            'departments.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['departments'] as $item) {
            OrganizationDepartment::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Departments reordered.']);
    }

    public function reorderMembers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'members' => ['required', 'array'],
            'members.*.id' => ['required', 'integer', 'exists:organization_members,id'],
            'members.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['members'] as $item) {
            OrganizationMember::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Members reordered.']);
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);
        $department = OrganizationDepartment::create($validated);

        return response()->json(['department' => $department], 201);
    }

    public function updateDepartment(Request $request, OrganizationDepartment $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']).'-'.$department->id;
        }
        $department->update($validated);

        return response()->json(['department' => $department->fresh()]);
    }

    public function destroyDepartment(OrganizationDepartment $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }

    public function storePosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['slug'] = Str::slug($validated['title']).'-'.Str::random(6);
        $position = OrganizationPosition::create($validated);

        return response()->json(['position' => $position], 201);
    }

    public function updatePosition(Request $request, OrganizationPosition $position): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $position->update($validated);

        return response()->json(['position' => $position->fresh()]);
    }

    public function destroyPosition(OrganizationPosition $position): JsonResponse
    {
        $position->delete();

        return response()->json(['message' => 'Position deleted.']);
    }

    public function storeMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:organization_positions,id'],
            'reports_to_id' => ['nullable', 'integer', 'exists:organization_members,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'in:staff,partner'],
            'bio' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_department_head' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'responsibilities' => ['nullable', 'array'],
            'responsibilities.*' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store(self::PHOTO_DIR, 'public');
        }
        unset($validated['responsibilities']);

        $member = OrganizationMember::create($validated);

        if ($request->has('responsibilities') && is_array($request->input('responsibilities'))) {
            foreach (array_values(array_filter($request->input('responsibilities'), fn ($r) => is_string($r) && trim($r) !== '')) as $idx => $resp) {
                OrganizationResponsibility::create([
                    'member_id' => $member->id,
                    'responsibility' => trim($resp),
                    'sort_order' => $idx,
                ]);
            }
        }

        return response()->json(['member' => $member->load(['position', 'department', 'reportsTo', 'responsibilities', 'partner'])], 201);
    }

    public function updateMember(Request $request, OrganizationMember $member): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:organization_positions,id'],
            'reports_to_id' => ['nullable', 'integer', 'exists:organization_members,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'in:staff,partner'],
            'bio' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_department_head' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_photo' => ['sometimes', 'boolean'],
            'responsibilities' => ['nullable', 'array'],
            'responsibilities.*' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo && ! str_starts_with($member->photo, 'http')) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = $request->file('photo')->store(self::PHOTO_DIR, 'public');
        } elseif ($request->boolean('remove_photo')) {
            if ($member->photo && ! str_starts_with($member->photo, 'http')) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = null;
        }
        unset($validated['responsibilities'], $validated['remove_photo']);

        $member->update($validated);

        if ($request->has('responsibilities') && is_array($request->input('responsibilities'))) {
            $member->responsibilities()->delete();
            foreach (array_values(array_filter($request->input('responsibilities'), fn ($r) => is_string($r) && trim($r) !== '')) as $idx => $resp) {
                OrganizationResponsibility::create([
                    'member_id' => $member->id,
                    'responsibility' => trim($resp),
                    'sort_order' => $idx,
                ]);
            }
        }

        return response()->json(['member' => $member->fresh()->load(['position', 'department', 'reportsTo', 'responsibilities', 'partner'])]);
    }

    public function destroyMember(OrganizationMember $member): JsonResponse
    {
        if ($member->photo && ! str_starts_with($member->photo, 'http')) {
            Storage::disk('public')->delete($member->photo);
        }
        $member->delete();

        return response()->json(['message' => 'Member deleted.']);
    }

    public function storePartner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:organization_members,id'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $partner = OrganizationPartner::create($validated);

        return response()->json(['partner' => $partner], 201);
    }

    public function updatePartner(Request $request, OrganizationPartner $partner): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => ['sometimes', 'required', 'integer', 'exists:organization_members,id'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $partner->update($validated);

        return response()->json(['partner' => $partner->fresh()]);
    }

    public function destroyPartner(OrganizationPartner $partner): JsonResponse
    {
        $partner->delete();

        return response()->json(['message' => 'Partner deleted.']);
    }

    public function seedInitial(): JsonResponse
    {
        if (OrganizationDepartment::count() > 0 || OrganizationMember::count() > 0) {
            return response()->json(['message' => 'Organization structure already exists.']);
        }

        $exec = OrganizationDepartment::create(['name' => 'Executive Leadership', 'slug' => 'executive-leadership', 'sort_order' => 1]);
        $academic = OrganizationDepartment::create(['name' => 'Academic Department', 'slug' => 'academic-department', 'sort_order' => 2]);
        $tech = OrganizationDepartment::create(['name' => 'Technology Department', 'slug' => 'technology-department', 'sort_order' => 3]);
        $marketing = OrganizationDepartment::create(['name' => 'Digital Marketing & Communications Department', 'slug' => 'digital-marketing-communications', 'sort_order' => 4]);
        $adminOps = OrganizationDepartment::create(['name' => 'Administration & Operations Department', 'slug' => 'administration-operations', 'sort_order' => 5]);
        $m_e = OrganizationDepartment::create(['name' => 'Monitoring, Evaluation & Student Support', 'slug' => 'monitoring-evaluation-student-support', 'sort_order' => 6]);

        $posExecDir = OrganizationPosition::create(['department_id' => $exec->id, 'title' => 'Executive Director & Higher Education Programme Coordinator', 'slug' => 'executive-director-higher-ed', 'sort_order' => 1]);
        $posDepExec = OrganizationPosition::create(['department_id' => $exec->id, 'title' => 'Deputy Executive Director – Internal Management', 'slug' => 'deputy-executive-director-internal', 'sort_order' => 2]);
        $posHeadAcad = OrganizationPosition::create(['department_id' => $academic->id, 'title' => 'Head of Academic Department & High School Programme Coordinator', 'slug' => 'head-academic-high-school', 'sort_order' => 1]);
        $posMid = OrganizationPosition::create(['department_id' => $academic->id, 'title' => 'Middle School Programme Coordinator', 'slug' => 'middle-school-coordinator', 'sort_order' => 2]);
        $posKinder = OrganizationPosition::create(['department_id' => $academic->id, 'title' => 'Kindergarten & Primary School Programme Coordinator', 'slug' => 'kindergarten-primary-coordinator', 'sort_order' => 3]);
        $posHeadTech = OrganizationPosition::create(['department_id' => $tech->id, 'title' => 'Head of Technology Department', 'slug' => 'head-technology', 'sort_order' => 1]);
        $posHeadMkt = OrganizationPosition::create(['department_id' => $marketing->id, 'title' => 'Head of Digital Marketing & Communications Department', 'slug' => 'head-marketing-communications', 'sort_order' => 1]);
        $posDepHeadMkt = OrganizationPosition::create(['department_id' => $marketing->id, 'title' => 'Deputy Head of Digital Marketing & Communications', 'slug' => 'deputy-head-marketing-communications', 'sort_order' => 2]);
        $posHeadAdmin = OrganizationPosition::create(['department_id' => $adminOps->id, 'title' => 'Head of Administration & Operations Department', 'slug' => 'head-admin-operations', 'sort_order' => 1]);
        $posSec = OrganizationPosition::create(['department_id' => $adminOps->id, 'title' => 'Administrative Secretary', 'slug' => 'administrative-secretary', 'sort_order' => 2]);
        $posFinance = OrganizationPosition::create(['department_id' => $adminOps->id, 'title' => 'Finance & Operations Support Officer', 'slug' => 'finance-operations-support-officer', 'sort_order' => 3]);
        $posMe = OrganizationPosition::create(['department_id' => $m_e->id, 'title' => 'Monitoring, Evaluation & Student Support Officer', 'slug' => 'monitoring-evaluation-student-support-officer', 'sort_order' => 1]);

        $m1 = OrganizationMember::create(['department_id' => $exec->id, 'position_id' => $posExecDir->id, 'name' => 'Dr. Seam Ngonn', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true]);
        $m2 = OrganizationMember::create(['department_id' => $exec->id, 'position_id' => $posDepExec->id, 'name' => 'Mr. Uong Tol', 'type' => 'staff', 'sort_order' => 2, 'is_active' => true, 'reports_to_id' => $m1->id]);
        $m3 = OrganizationMember::create(['department_id' => $academic->id, 'position_id' => $posHeadAcad->id, 'name' => 'Mr. Kuong Revey', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true, 'reports_to_id' => $m1->id]);
        $m4 = OrganizationMember::create(['department_id' => $academic->id, 'position_id' => $posMid->id, 'name' => 'Ms. An Sambath', 'type' => 'staff', 'sort_order' => 2, 'is_active' => true, 'reports_to_id' => $m3->id]);
        $m5 = OrganizationMember::create(['department_id' => $academic->id, 'position_id' => $posKinder->id, 'name' => 'Miss Min Sreyny', 'type' => 'staff', 'sort_order' => 3, 'is_active' => true, 'reports_to_id' => $m3->id]);
        $m6 = OrganizationMember::create(['department_id' => $tech->id, 'position_id' => $posHeadTech->id, 'name' => 'Mr. On Prouhn', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true, 'reports_to_id' => $m1->id]);
        $m7 = OrganizationMember::create(['department_id' => $marketing->id, 'position_id' => $posHeadMkt->id, 'name' => 'Mr. Nin Vannsan', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true, 'reports_to_id' => $m1->id]);
        $m8 = OrganizationMember::create(['department_id' => $marketing->id, 'position_id' => $posDepHeadMkt->id, 'name' => 'Mean Sokunthea', 'type' => 'staff', 'sort_order' => 2, 'is_active' => true, 'reports_to_id' => $m7->id]);
        $m9 = OrganizationMember::create(['department_id' => $adminOps->id, 'position_id' => $posHeadAdmin->id, 'name' => 'Ms. Sak Engsrun', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true, 'reports_to_id' => $m1->id]);
        $m10 = OrganizationMember::create(['department_id' => $adminOps->id, 'position_id' => $posSec->id, 'name' => 'Theara', 'type' => 'staff', 'sort_order' => 2, 'is_active' => true, 'reports_to_id' => $m9->id]);
        $m11 = OrganizationMember::create(['department_id' => $adminOps->id, 'position_id' => $posFinance->id, 'name' => 'Mr. Chheng Phana', 'type' => 'staff', 'sort_order' => 3, 'is_active' => true, 'reports_to_id' => $m9->id]);
        $m12 = OrganizationMember::create(['department_id' => $m_e->id, 'position_id' => $posMe->id, 'name' => 'To be confirmed', 'type' => 'staff', 'sort_order' => 1, 'is_active' => true, 'reports_to_id' => $m1->id]);

        $this->setResp($m1, ['Executive Director & Higher Education Programme Coordinator']);
        $this->setResp($m2, ['Deputy Executive Director – Internal Management']);
        $this->setResp($m3, ['Head of Academic Department & High School Programme Coordinator']);
        $this->setResp($m4, ['Middle School Programme Coordinator']);
        $this->setResp($m5, ['Kindergarten & Primary School Programme Coordinator']);
        $this->setResp($m6, ['Head of Technology Department']);
        $this->setResp($m7, ['Head of Digital Marketing & Communications Department']);
        $this->setResp($m8, ['Deputy Head of Digital Marketing & Communications']);
        $this->setResp($m9, ['Head of Administration & Operations Department']);
        $this->setResp($m10, ['Administrative Secretary']);
        $this->setResp($m11, ['Finance & Operations Support Officer']);
        $this->setResp($m12, ['Monitoring, Evaluation & Student Support Officer']);

        return response()->json(['message' => 'Seeded.']);
    }

    private function setResp(OrganizationMember $member, array $resps): void
    {
        foreach ($resps as $idx => $resp) {
            OrganizationResponsibility::create(['member_id' => $member->id, 'responsibility' => $resp, 'sort_order' => $idx]);
        }
    }
}
