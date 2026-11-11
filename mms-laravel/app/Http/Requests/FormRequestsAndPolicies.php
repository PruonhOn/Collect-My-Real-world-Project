<?php
// ============================================================
// app/Http/Requests/StoreWorkOrderRequest.php
// ============================================================
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type'        => 'required|in:corrective,preventive,inspection,emergency',
            'priority'    => 'required|in:low,medium,high,critical',
            'asset_id'    => 'required|exists:assets,id',
            'assigned_to' => 'nullable|exists:users,id',
            'due_at'      => 'nullable|date|after:now',
        ];
    }
}


// ============================================================
// app/Policies/WorkOrderPolicy.php
// ============================================================
namespace App\Policies;

use App\Models\{User, WorkOrder};
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view work-orders');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('view work-orders') || $workOrder->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create work-orders');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        if ($user->hasRole('admin')) return true;
        return $workOrder->assigned_to === $user->id && $user->can('edit work-orders');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole('admin') && $user->can('delete work-orders');
    }

    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('assign work-orders');
    }
}


// ============================================================
// app/Http/Middleware/CheckPermission.php
// ============================================================
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$request->user()?->can($permission)) {
            abort(403, 'Insufficient permissions.');
        }
        return $next($request);
    }
}
