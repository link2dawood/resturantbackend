@extends('layouts.tabler')
@section('title', 'Audit Log Details')
@section('content')

<style>
    .audit-detail-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .audit-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .audit-section {
        padding: 25px;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .audit-section:last-child {
        border-bottom: none;
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .info-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 1rem;
        color: #111827;
    }
    
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 0.9rem;
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
    
    .changes-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .changes-table th,
    .changes-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .changes-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
    }
    
    .changes-table tr:hover {
        background-color: #f9fafb;
    }
    
    .value-box {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 8px 12px;
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 0.9rem;
        max-width: 300px;
        overflow-wrap: break-word;
    }
    
    .value-old {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }
    
    .value-new {
        background: #f0fdf4;
        border-color: #86efac;
        color: #166534;
    }
    
    .json-container {
        background: #1f2937;
        color: #f9fafb;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 0.9rem;
        overflow-x: auto;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .back-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    
    .back-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }
</style>

<div class="container-xl">
    <div class="mb-3">
        <a href="{{ route('audit-logs.index') }}" class="back-btn">
            ← Back to Audit Logs
        </a>
    </div>
    
    <div class="audit-detail-card">
        <div class="audit-header">
            <h1>🔍 Audit Log Details</h1>
            <p>{{ $auditLog->action_description }} - {{ \App\Helpers\DateFormatter::toUSWithTime($auditLog->created_at) }}</p>
        </div>
        
        <!-- Basic Information -->
        <div class="audit-section">
            <div class="section-title">
                📋 Basic Information
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Action</div>
                    <div class="info-value">
                        <span class="action-badge action-{{ $auditLog->action }}">
                            {{ $auditLog->action_description }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date & Time</div>
                    <div class="info-value">{{ \App\Helpers\DateFormatter::toUSWithTime($auditLog->created_at) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Model Type</div>
                    <div class="info-value"><code>{{ class_basename($auditLog->auditable_type) }}</code></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Model ID</div>
                    <div class="info-value"><code>{{ $auditLog->auditable_id }}</code></div>
                </div>
            </div>
        </div>
        
        <!-- User Information -->
        <div class="audit-section">
            <div class="section-title">
                👤 User Information
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">User Name</div>
                    <div class="info-value">{{ $auditLog->user_name ?? 'System' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">User ID</div>
                    <div class="info-value">{{ $auditLog->user_id ? '#' . $auditLog->user_id : 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">IP Address</div>
                    <div class="info-value"><code>{{ $auditLog->ip_address ?? 'N/A' }}</code></div>
                </div>
                <div class="info-item">
                    <div class="info-label">User Agent</div>
                    <div class="info-value" title="{{ $auditLog->user_agent }}">
                        {{ $auditLog->user_agent ? Str::limit($auditLog->user_agent, 50) : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
        
        @if($auditLog->changes_summary)
        <!-- Changes Summary -->
        <div class="audit-section">
            <div class="section-title">
                🔄 Changes Made
            </div>
            <div class="table-responsive">
                <table class="changes-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditLog->changes_summary as $field => $change)
                        <tr>
                            <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                            <td>
                                @if($change['old'] !== null)
                                    <div class="value-box value-old">
                                        {{ is_array($change['old']) || is_object($change['old']) ? json_encode($change['old'], JSON_PRETTY_PRINT) : $change['old'] }}
                                    </div>
                                @else
                                    <em class="text-muted">null</em>
                                @endif
                            </td>
                            <td>
                                @if($change['new'] !== null)
                                    <div class="value-box value-new">
                                        {{ is_array($change['new']) || is_object($change['new']) ? json_encode($change['new'], JSON_PRETTY_PRINT) : $change['new'] }}
                                    </div>
                                @else
                                    <em class="text-muted">null</em>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        
        @if($auditLog->additional_data)
        <!-- Additional Data -->
        <div class="audit-section">
            <div class="section-title">
                📊 Additional Context
            </div>
            <div class="json-container">
                {!! json_encode($auditLog->additional_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
            </div>
        </div>
        @endif
        
        <!-- Raw Data -->
        <div class="audit-section">
            <div class="section-title">
                🗄️ Raw Data
            </div>
            <div class="row">
                @if($auditLog->old_values)
                <div class="col-md-6">
                    <h6>Old Values</h6>
                    <div class="json-container">
                        {!! json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
                    </div>
                </div>
                @endif
                
                @if($auditLog->new_values)
                <div class="col-md-6">
                    <h6>New Values</h6>
                    <div class="json-container">
                        {!! json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection