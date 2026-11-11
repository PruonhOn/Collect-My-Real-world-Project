<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('module') && $request->module !== null) {
            $query->where('module', $request->module);
        }

        if ($request->has('user_id') && $request->user_id !== null) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from') && $request->date_from !== null) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== null) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();
        $modules = ActivityLog::distinct()->pluck('module')->filter();

        if ($request->ajax()) {
            return response()->json(['logs' => $logs, 'modules' => $modules]);
        }

        return view('activity-logs.index', compact('logs', 'modules'));
    }
}
