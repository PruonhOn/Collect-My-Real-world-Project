<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success([
            'languages' => 4,
            'books' => Book::published()->count(),
            'categories' => Category::where('status', 'active')->count(),
            'authors' => Author::count(),
        ], 'Stats retrieved.');
    }
}
