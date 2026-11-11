<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $logs = $this->auditLogService->getAll($request->only([
            'module', 'action', 'user_id', 'date_from', 'date_to', 'search',
        ]));
        $modules = $this->auditLogService->getModules();
        $actions = $this->auditLogService->getActions();
        return view('audit-logs.index', compact('logs', 'modules', 'actions'));
    }

    public function show(int $id): View
    {
        $log = $this->auditLogService->getById($id);
        return view('audit-logs.show', compact('log'));
    }
}
