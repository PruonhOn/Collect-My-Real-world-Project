<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with('business');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($provider = $request->query('provider')) {
            $query->where('provider', $provider);
        }

        if ($businessId = $request->query('business_id')) {
            $query->where('business_id', $businessId);
        }

        return response()->json($query->orderBy('created_at', 'desc')
            ->paginate(min(100, (int) $request->query('per_page', 20))));
    }
}
