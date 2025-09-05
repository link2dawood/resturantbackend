<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Only admins and owners can view audit logs
        if (!auth()->user()->hasPermission('view_audit_logs')) {
            abort(403);
        }

        $query = AuditLog::with(['user', 'auditable'])->orderBy('created_at', 'desc');
        
        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }
        
        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filter by specific model ID
        if ($request->filled('model_id')) {
            $query->where('auditable_id', $request->model_id);
        }

        $logs = $query->paginate(50);
        
        // Get filter options
        $modelTypes = AuditLog::select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            });
        
        $actions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
        
        $users = \App\Models\User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('audit-logs.index', compact('logs', 'modelTypes', 'actions', 'users'));
    }

    public function show(AuditLog $auditLog)
    {
        // Only admins and owners can view audit logs
        if (!auth()->user()->hasPermission('view_audit_logs')) {
            abort(403);
        }

        $auditLog->load(['user', 'auditable']);

        return view('audit-logs.show', compact('auditLog'));
    }
}