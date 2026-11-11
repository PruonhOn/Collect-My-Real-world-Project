<?php
// ============================================================
// app/Http/Controllers/WorkOrderController.php
// ============================================================
namespace App\Http\Controllers;

use App\Models\{WorkOrder, Asset, User};
use App\Http\Requests\StoreWorkOrderRequest;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', WorkOrder::class);
        return view('work-orders.index');   // Livewire table rendered here
    }

    public function create()
    {
        $this->authorize('create', WorkOrder::class);
        $assets      = Asset::active()->orderBy('name')->get();
        $technicians = User::active()->role('technician')->orderBy('name')->get();
        return view('work-orders.create', compact('assets', 'technicians'));
    }

    public function store(StoreWorkOrderRequest $request)
    {
        $wo = WorkOrder::create([...$request->validated(), 'created_by' => auth()->id()]);

        if ($request->assigned_to) {
            $wo->assign(User::findOrFail($request->assigned_to));
        }

        return redirect()->route('work-orders.show', $wo)->with('success', 'Work order created.');
    }

    public function show(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);
        $workOrder->load(['asset', 'technician', 'creator', 'logs.user', 'alerts']);
        return view('work-orders.show', compact('workOrder'));
    }

    public function edit(WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $assets      = Asset::active()->orderBy('name')->get();
        $technicians = User::active()->role('technician')->orderBy('name')->get();
        return view('work-orders.edit', compact('workOrder', 'assets', 'technicians'));
    }

    public function update(StoreWorkOrderRequest $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $workOrder->update($request->validated());
        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Updated.');
    }

    public function destroy(WorkOrder $workOrder)
    {
        $this->authorize('delete', $workOrder);
        $workOrder->delete();
        return redirect()->route('work-orders.index')->with('success', 'Work order deleted.');
    }

    public function assign(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('assign', $workOrder);
        $request->validate(['user_id' => 'required|exists:users,id']);
        $workOrder->assign(User::findOrFail($request->user_id));
        return back()->with('success', 'Assigned successfully.');
    }

    public function start(WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $workOrder->start();
        return back()->with('success', 'Work started.');
    }

    public function complete(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $request->validate(['notes' => 'nullable|string|max:2000']);
        $workOrder->complete($request->notes ?? '');
        return back()->with('success', 'Work order completed!');
    }
}


// ============================================================
// app/Http/Controllers/AssetController.php
// ============================================================
namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::withCount(['workOrders'])->paginate(20);
        return view('assets.index', compact('assets'));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'required|string|unique:assets,code',
            'category'            => 'required|string',
            'location'            => 'required|string',
            'status'              => 'required|in:active,inactive,retired',
            'installed_at'        => 'nullable|date',
            'warranty_expires_at' => 'nullable|date',
        ]);
        Asset::create($data);
        return redirect()->route('assets.index')->with('success', 'Asset created.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['workOrders.technician']);
        $stats = [
            'total_wo'     => $asset->workOrders()->count(),
            'open_wo'      => $asset->workOrders()->whereNotIn('status',['completed','cancelled'])->count(),
            'completed_wo' => $asset->workOrders()->where('status','completed')->count(),
        ];
        return view('assets.show', compact('asset', 'stats'));
    }

    public function edit(Asset $asset)
    {
        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'category'            => 'required|string',
            'location'            => 'required|string',
            'status'              => 'required|in:active,inactive,retired',
            'installed_at'        => 'nullable|date',
            'warranty_expires_at' => 'nullable|date',
        ]);
        $asset->update($data);
        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete-assets');
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Asset removed.');
    }

    public function history(Asset $asset)
    {
        $logs = $asset->workOrders()->with(['logs.user'])->latest()->get()->pluck('logs')->flatten()->sortByDesc('created_at');
        return view('assets.history', compact('asset', 'logs'));
    }
}


// ============================================================
// app/Http/Controllers/ReportController.php
// ============================================================
namespace App\Http\Controllers;

use App\Models\{WorkOrder, Asset};
use App\Exports\WorkOrdersExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function workOrders(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $orders = WorkOrder::with(['asset', 'technician'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $summary = [
            'total'       => $orders->count(),
            'completed'   => $orders->where('status', 'completed')->count(),
            'overdue'     => $orders->filter->is_overdue->count(),
            'avg_resolve' => round($orders->whereNotNull('completed_at')
                ->avg(fn($o) => $o->started_at?->diffInHours($o->completed_at) ?? 0), 1),
        ];

        return view('reports.work-orders', compact('orders', 'summary', 'dateFrom', 'dateTo'));
    }

    public function export(Request $request)
    {
        $request->validate(['type' => 'required|in:excel,pdf', 'report' => 'required|string']);

        if ($request->type === 'excel') {
            return Excel::download(new WorkOrdersExport($request->all()), 'work-orders-report.xlsx');
        }

        $orders  = WorkOrder::with(['asset','technician'])->get();
        $pdf     = Pdf::loadView('reports.pdf.work-orders', compact('orders'))->setPaper('a4', 'landscape');
        return $pdf->download('work-orders-report.pdf');
    }
}


// ============================================================
// app/Http/Controllers/UserController.php
// ============================================================
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ])->assignRole($data['role']);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function show(User $user)
    {
        $user->load(['workOrders.asset', 'roles']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role'  => 'required|exists:roles,name',
        ]);
        $user->update(['name' => $data['name'], 'email' => $data['email']]);
        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself.');
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User removed.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', $user->is_active ? 'User activated.' : 'User deactivated.');
    }
}
