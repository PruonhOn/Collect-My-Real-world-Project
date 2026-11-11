<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use App\Models\User;
use App\Services\MockPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private MockPaymentService $paymentService) {}

    public function dashboard(): JsonResponse
    {
        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount');
        $monthlyRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
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
                'total_revenue' => (float) $totalRevenue,
                'monthly_revenue' => (float) $monthlyRevenue,
            ],
            'revenue_by_month' => $this->revenueByMonth(),
            'recent_payments' => Payment::with('user')->latest()->limit(5)->get(),
            'recent_users' => User::latest()->limit(5)->get(),
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

    private function revenueByMonth(): array
    {
        $rows = Payment::where('status', Payment::STATUS_COMPLETED)
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
