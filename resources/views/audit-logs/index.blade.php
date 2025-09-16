@extends('layouts.tabler')
@section('title', 'Audit Logs')
@section('content')

<style>
    
    .audit-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: none;
        overflow: hidden;
    }
    
    .audit-table {
        margin: 0;
    }
    
    .audit-table thead th {
        background: #f8f9fa;
        border: none;
        padding: 18px 15px;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .audit-table tbody tr {
        border: none;
        transition: all 0.2s ease;
    }
    
    .audit-table tbody tr:hover {
        background-color: #f8f9ff;
        transform: translateX(2px);
    }
    
    .audit-table tbody td {
        padding: 15px;
        border: none;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .action-created { background: #e3f2fd; color: #1976d2; }
    .action-updated { background: #fff3e0; color: #f57c00; }
    .action-deleted { background: #ffebee; color: #d32f2f; }
    .action-submitted { background: #e8f5e8; color: #388e3c; }
    .action-approved { background: #e8f5e8; color: #2e7d32; }
    .action-rejected { background: #fce4ec; color: #c2185b; }
    .action-returned_to_draft { background: #f3e5f5; color: #7b1fa2; }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.8rem;
    }
    
    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .btn-filter {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-clear {
        background: #6c757d;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
    }
</style>

<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Audit Logs</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Track all system activities and changes</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="{{ route('audit-logs.index') }}">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Model Type</label>
                    <select name="model_type" class="form-select">
                        <option value="">All Models</option>
                        @foreach($modelTypes as $type)
                            <option value="{{ $type['value'] }}" {{ request('model_type') == $type['value'] ? 'selected' : '' }}>
                                {{ $type['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="text" name="date_from" class="form-control date-input" value="{{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('m-d-Y') : '' }}" placeholder="MM-DD-YYYY" maxlength="10">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="text" name="date_to" class="form-control date-input" value="{{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('m-d-Y') : '' }}" placeholder="MM-DD-YYYY" maxlength="10">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Model ID</label>
                    <input type="number" name="model_id" class="form-control" value="{{ request('model_id') }}" placeholder="ID">
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-filter d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 6L9 18L3.5 12.5"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-clear d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    </svg>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="audit-card">
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="audit-table table">
                    <thead>
                        <tr>
                            <th>📅 Date & Time</th>
                            <th>👤 User</th>
                            <th>⚡ Action</th>
                            <th>🏷️ Model</th>
                            <th>🆔 ID</th>
                            <th>📱 IP Address</th>
                            <th>👁️ Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td>
                                <div>{{ \App\Helpers\DateFormatter::toUSShort($log->created_at) }}</div>
                                <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        {{ substr($log->user_name ?? 'System', 0, 1) }}
                                    </div>
                                    <div>
                                        <div>{{ $log->user_name ?? 'System' }}</div>
                                        @if($log->user_id)
                                            <small class="text-muted">ID: {{ $log->user_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="action-badge action-{{ $log->action }}">
                                    {{ $log->action_description }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ class_basename($log->auditable_type) }}</strong>
                            </td>
                            <td>
                                <code>{{ $log->auditable_id }}</code>
                            </td>
                            <td>
                                <code>{{ $log->ip_address ?? 'N/A' }}</code>
                            </td>
                            <td>
                                <a href="{{ route('audit-logs.show', $log) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center" style="gap: 0.25rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                                    </svg>
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>📊 Results:</strong> 
                        {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} logs
                    </div>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="p-5 text-center">
                <div class="mb-3">
                    <i class="fa fa-search" style="font-size: 3rem; color: #dee2e6;"></i>
                </div>
                <h4 class="text-muted">No audit logs found</h4>
                <p class="text-muted">No logs match your current filter criteria.</p>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 4v6h6M23 20v-6h-6"/>
                        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                    </svg>
                    Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>

@endsection