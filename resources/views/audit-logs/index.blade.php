@extends('layouts.tabler')
@section('title', 'Audit Logs')
@section('content')

<style>
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
    
    .action-created { background: #d2e3fc; color: #4285f4; }
    .action-updated { background: #fff3e0; color: #f57c00; }
    .action-deleted { background: #fce8e6; color: #ea4335; }
    .action-submitted { background: #e6f4ea; color: #34a853; }
    .action-approved { background: #e6f4ea; color: #34a853; }
    .action-rejected { background: #fce8e6; color: #ea4335; }
    .action-returned_to_draft { background: #f1f3f4; color: #5f6368; }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #4285f4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 500;
        font-size: 0.8rem;
    }
    
    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                <button type="submit" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    Clear
                </a>
            </div>
        </form>
    </div>

    @php
        $headers = [
            'Date & Time',
            'User',
            'Action',
            'Model',
            'ID',
            'IP Address',
            ['label' => 'Details', 'align' => 'center']
        ];
    @endphp

    <x-table 
        :headers="$headers"
        emptyMessage="No audit logs found"
        emptyDescription="No logs match your current filter criteria."
        emptyActionHref="{{ route('audit-logs.index') }}"
        emptyActionText="Clear Filters">
        @if($logs->count() > 0)
            @foreach($logs as $log)
                <x-table-row>
                    <x-table-cell>
                        <div>{{ \App\Helpers\DateFormatter::toUSShort($log->created_at) }}</div>
                        <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell>
                        <span class="action-badge action-{{ $log->action }}">
                            {{ $log->action_description }}
                        </span>
                    </x-table-cell>
                    <x-table-cell>
                        <strong>{{ class_basename($log->auditable_type) }}</strong>
                    </x-table-cell>
                    <x-table-cell>
                        <code>{{ $log->auditable_id }}</code>
                    </x-table-cell>
                    <x-table-cell>
                        <code>{{ $log->ip_address ?? 'N/A' }}</code>
                    </x-table-cell>
                    <x-table-cell align="center">
                        <x-button-view href="{{ route('audit-logs.show', $log) }}" iconOnly="true" />
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>

    @if($logs->hasPages())
        <div class="mt-3">
            <x-pagination :paginator="$logs" />
        </div>
    @endif
</div>

@endsection