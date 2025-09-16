@extends('layouts.tabler')

@section('title', 'Stores')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Stores</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage your restaurant locations</p>
        </div>
        <a href="{{ route('stores.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Create Store
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-green, #34a853);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-red, #ea4335);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-red, #ea4335)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Stores Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Stores</h3>
        </div>
        <div class="card-body p-0">
            @if($stores->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" style="font-size: 0.875rem;">
                        <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                            <tr>
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">#</th>
                                @if(Auth::user()->hasPermission('manage_owners'))
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">Owner</th>
                                @endif
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">Store Information</th>
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">Address</th>
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">Sales Tax</th>
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">Medicare Tax</th>
                                <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stores as $store)
                            @php
                            $owner = App\Models\User::find($store->created_by);
                            @endphp
                            <tr style="border-bottom: 1px solid var(--google-grey-100, #f1f3f4);">
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-700, #3c4043);">
                                    <span class="badge bg-light text-dark" style="font-size: 0.75rem;">{{ $store->id }}</span>
                                </td>
                                @if(Auth::user()->hasPermission('manage_owners'))
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-900, #202124);">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs me-2" style="background-color: var(--google-blue-100, #d2e3fc); color: var(--google-blue, #4285f4);">
                                            {{ substr($owner->name ?? 'U', 0, 1) }}
                                        </div>
                                        <span style="font-weight: 500;">{{ $owner->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                @endif
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-900, #202124);">
                                    <div style="font-weight: 500; font-size: 0.875rem;">{{ $store->store_info }}</div>
                                </td>
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-700, #3c4043);">
                                    <div style="font-size: 0.875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $store->address }}">{{ $store->address }}</div>
                                </td>
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-700, #3c4043);">
                                    <span class="badge bg-info text-white" style="font-size: 0.75rem;">{{ $store->sales_tax_rate }}%</span>
                                </td>
                                <td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-700, #3c4043);">
                                    <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">{{ $store->medicare_tax_rate }}%</span>
                                </td>
                                <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton{{ $store->id }}" data-bs-toggle="dropdown" aria-expanded="false" style="border: 1px solid var(--google-grey-300, #dadce0); font-size: 0.813rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                                <path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/>
                                            </svg>
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $store->id }}" style="border-radius: 0.75rem; box-shadow: var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15)); border: 1px solid var(--google-grey-200, #e8eaed);">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center" href="{{ route('stores.edit', $store->id) }}" style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                                    </svg>
                                                    Edit Store
                                                </a>
                                            </li>
                                            @if(!Auth::user()->hasPermission('manage_owners'))
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center" href="{{url('/stores/'.$store->id.'/daily-reports')}}" style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                                        <polyline points="14,2 14,8 20,8"/>
                                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                                    </svg>
                                                    Daily Reports
                                                </a>
                                            </li>
                                            @endif
                                            <li><hr class="dropdown-divider" style="margin: 0.5rem 0;"></li>
                                            <li>
                                                <form action="{{ route('stores.destroy', $store->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this store? This action cannot be undone.')" class="m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center" style="padding: 0.75rem 1rem; font-size: 0.875rem; border: none; background: none; width: 100%;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                                            <polyline points="3,6 5,6 21,6"/>
                                                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                                        </svg>
                                                        Delete Store
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--google-grey-400, #9aa0a6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
                        </svg>
                    </div>
                    <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 400; color: var(--google-grey-700, #3c4043); margin-bottom: 0.5rem;">No stores found</h3>
                    <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem;">Get started by creating your first restaurant location.</p>
                    <a href="{{ route('stores.create') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Create Your First Store
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
