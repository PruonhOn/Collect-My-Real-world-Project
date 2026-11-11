<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $totalBooks = Book::count();
        $freeBooks = Book::where('is_free', true)->count();
        $paidBooks = Book::where('is_free', false)->count();
        $publishedBooks = Book::where('is_published', true)->count();
        $totalUsers = User::count();
        $totalPurchases = Purchase::count();
        $totalRevenue = (float) Purchase::where('status', 'paid')->sum('amount');
        $totalReviews = Review::count();
        $totalCategories = Category::count();
        $pendingContacts = ContactMessage::whereNull('replied_at')->count();

        $monthlyRevenue = Purchase::where('status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => (float) $row->total,
            ]);

        $recentBooks = Book::with(['category', 'author'])->latest()->limit(6)->get()->map(function ($book) {
            return [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $book->title,
                'price' => (float) $book->price,
                'is_free' => $book->is_free,
                'is_published' => $book->is_published,
                'views_count' => $book->views_count,
            ];
        });

        $recentUsers = User::with('roles')->latest()->limit(6)->get()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ? url('/storage/'.$user->avatar) : null,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name'),
            'created_at' => $user->created_at->toDateTimeString(),
        ]);

        $recentPayments = Payment::with(['purchase.book'])->latest()->limit(6)->get()->map(function ($payment) {
            return [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'book_title' => $payment->purchase->book->title ?? null,
                'created_at' => $payment->created_at->toDateTimeString(),
            ];
        });

        return $this->success([
            'stats' => [
                'total_books' => $totalBooks,
                'free_books' => $freeBooks,
                'paid_books' => $paidBooks,
                'published_books' => $publishedBooks,
                'total_users' => $totalUsers,
                'total_purchases' => $totalPurchases,
                'total_revenue' => $totalRevenue,
                'total_reviews' => $totalReviews,
                'total_categories' => $totalCategories,
                'pending_contacts' => $pendingContacts,
            ],
            'monthly_revenue' => $monthlyRevenue,
            'recent_books' => $recentBooks,
            'recent_users' => $recentUsers,
            'recent_payments' => $recentPayments,
        ]);
    }
}