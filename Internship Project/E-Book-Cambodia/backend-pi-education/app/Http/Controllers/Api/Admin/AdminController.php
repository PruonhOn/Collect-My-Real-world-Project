<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Course;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use App\Models\User;
use App\Services\MockPaymentService;
use App\Services\VipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private MockPaymentService $paymentService) {}

    public function dashboard(): JsonResponse
    {
        $testItemKeys = VipService::testItemKeys();

        $totalRevenue = $this->completedPayments($testItemKeys)->sum('amount');
        $monthlyRevenue = $this->completedPayments($testItemKeys)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return response()->json([
            'stats' => [
                'users' => User::count(),
                'admins' => User::where('role', User::ROLE_ADMIN)->count(),
                'books' => Book::count(),
                'categories' => Category::count(),
                'courses' => Course::count(),
                'purchases' => Purchase::where('status', Purchase::STATUS_PAID)->count(),
                'active_sharing' => SharedAccess::where('status', SharedAccess::STATUS_ACTIVE)->count(),
                'pending_reviews' => Review::where('status', Review::STATUS_PENDING)->count(),
                'pending_messages' => Message::whereNull('reply')->count(),
                'pending_collaborations' => Message::where('subject', 'Partnership / Business')
                    ->whereNull('reply')
                    ->count(),
                'total_revenue' => (float) $totalRevenue,
                'monthly_revenue' => (float) $monthlyRevenue,
            ],
            'revenue_by_month' => $this->revenueByMonth(),
            'recent_payments' => Payment::with('user')->latest()->limit(5)->get(),
            'recent_users' => User::latest()->limit(5)->get(),
            'recent_messages' => Message::with('user:id,name')->latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->withCount(['purchases', 'sharingKeys'])
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($users);
    }

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:reader,admin'],
        ]);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json(['user' => $user->fresh()]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function vipUsers(Request $request): JsonResponse
    {
        $query = User::query()->whereNotNull('vip_until');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['purchases', 'sharingKeys'])
            ->orderByDesc('vip_until')
            ->paginate(15);

        $active = User::where('vip_until', '>', now())->count();
        $total = User::whereNotNull('vip_until')->count();

        $revenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereHas('purchase', fn ($q) => $q->where('external_item_key', 'like', 'vip:%'))
            ->whereDoesntHave('purchase', fn ($q) => $q->whereIn('external_item_key', VipService::testItemKeys()))
            ->sum('amount');

        return response()->json([
            'stats' => [
                'active' => $active,
                'total' => $total,
                'expired' => max(0, $total - $active),
                'revenue' => (float) $revenue,
            ],
            'users' => $users,
        ]);
    }

    public function payments(): JsonResponse
    {
        $payments = Payment::with('user', 'purchase.book')
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * Manually approve and activate a pending payment/purchase (used for
     * ACLEDA / Wing QR payments which require admin confirmation).
     */
    public function approvePayment(Payment $payment): JsonResponse
    {
        $purchase = $payment->purchase;

        abort_if(! $purchase, 422, 'This payment has no linked purchase.');

        if ($payment->status === Payment::STATUS_COMPLETED) {
            return response()->json(['message' => 'Payment is already completed.'], 422);
        }

        $this->paymentService->approve($purchase);

        return response()->json([
            'message' => 'Payment approved and access activated.',
            'payment' => Payment::with('purchase.book')->find($payment->id),
        ]);
    }

    public function sharingKeys(): JsonResponse
    {
        $keys = SharingKey::with('owner', 'book', 'sharedAccess.user')
            ->latest()
            ->paginate(15);

        return response()->json($keys);
    }

    /**
     * Test-card oversight: every VIP test-card purchase with its user and
     * payment status. Clearly separated from production VIP revenue.
     */
    public function vipTestCards(): JsonResponse
    {
        $base = Purchase::query()->whereIn('external_item_key', VipService::testItemKeys());

        $purchases = (clone $base)
            ->with(['user', 'payment'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'stats' => [
                'total' => (clone $base)->count(),
                'paid' => (clone $base)->where('status', Purchase::STATUS_PAID)->count(),
                'active' => User::whereIn('vip_plan', collect(VipService::testItemKeys())->map(
                    fn (string $key) => substr((string) $key, strlen(VipService::ITEM_KEY_PREFIX))
                )->all())->where('vip_until', '>', now())->count(),
            ],
            'purchases' => $purchases,
        ]);
    }

    /**
     * End a VIP test-card subscription immediately. Only touches the user's
     * membership when their current plan is the test card; the paid purchase
     * record is kept for audit.
     */
    public function revokeVipTestCard(Purchase $purchase): JsonResponse
    {
        if (! in_array($purchase->external_item_key, VipService::testItemKeys(), true)) {
            abort(422, 'This purchase is not a VIP test card.');
        }

        $user = $purchase->user;
        $plan = VipService::planForItemKey($purchase->external_item_key);

        if ($user !== null && $plan !== null && $user->vip_plan === $plan->slug && $user->vip_until?->isFuture()) {
            $user->update(['vip_until' => null, 'vip_plan' => null]);
        }

        return response()->json(['message' => 'Test subscription revoked.', 'user' => $user?->fresh()]);
    }

    /**
     * Completed payments, optionally excluding test-card purchases so $0.01
     * test spend never leaks into production revenue numbers.
     */
    private function completedPayments(array $excludeItemKeys = [])
    {
        return Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereDoesntHave('purchase', fn ($q) => $q->whereIn('external_item_key', $excludeItemKeys));
    }

    private function revenueByMonth(): array
    {
        $rows = $this->completedPayments(VipService::testItemKeys())
            ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn (Payment $p) => $p->paid_at->format('Y-m'));

        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $result[] = [
                'month' => $month,
                'total' => round((float) $rows->get($month, collect())->sum('amount'), 2),
            ];
        }

        return $result;
    }
}
