@extends('layouts.tabler')

@section('title', 'View Chart of Account')

@section('content')
@php
    // A "header" code (trailing-zero block, e.g. 6400/6450) can hold sub-accounts;
    // a detail/leaf code (e.g. 6451) cannot.
    $canHaveChildren = \App\Models\ChartOfAccount::childCodeRangeForParent((string) $chartOfAccount->account_code) !== null;
@endphp
<div class="container-xl mt-4">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-0">{{ $chartOfAccount->account_name }}</h1>
                    <p class="text-muted mb-0">Account Code: {{ $chartOfAccount->account_code }}</p>
                </div>
                <div class="d-flex gap-2">
                    @if($canHaveChildren)
                        <a href="{{ route('coa.create', ['parent' => $chartOfAccount->id]) }}" class="btn btn-success">
                            <i class="bi bi-plus-lg me-2"></i>Add Sub-Account
                        </a>
                    @endif
                    @if($chartOfAccount->canBeManagedBy(auth()->user()))
                        <a href="{{ route('coa.edit', $chartOfAccount) }}" class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>Edit
                        </a>
                    @endif
                    <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Account Details</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Account Code</dt>
                                <dd class="col-sm-7">
                                    <strong>{{ $chartOfAccount->account_code }}</strong>
                                </dd>

                                <dt class="col-sm-5">Account Type</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-secondary">{{ $chartOfAccount->account_type }}</span>
                                </dd>

                                <dt class="col-sm-5">Parent Account</dt>
                                <dd class="col-sm-7">
                                    @if($chartOfAccount->parent)
                                        <a href="{{ route('coa.show', $chartOfAccount->parent) }}">
                                            {{ $chartOfAccount->parent->account_code }} - {{ $chartOfAccount->parent->account_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">None (top level)</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Status</dt>
                                <dd class="col-sm-7">
                                    @if($chartOfAccount->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Created By</dt>
                                <dd class="col-sm-7">
                                    {{ $chartOfAccount->creator?->name ?? 'System' }}
                                </dd>

                                <dt class="col-sm-5">Created At</dt>
                                <dd class="col-sm-7">
                                    {{ $chartOfAccount->created_at->format(config('dates.display_datetime')) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Store Availability</h5>
                        </div>
                        <div class="card-body">
                            @if($chartOfAccount->stores->isEmpty())
                                <p class="text-muted mb-0">
                                    <i class="bi bi-globe-americas me-2"></i>Available to all stores.
                                </p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach($chartOfAccount->stores as $store)
                                        <li class="list-group-item">
                                            <i class="bi bi-shop me-2"></i>{{ $store->store_info }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($chartOfAccount->children->isNotEmpty() || $canHaveChildren)
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sub-Accounts ({{ $chartOfAccount->children->count() }})</h5>
                        @if($canHaveChildren)
                            <a href="{{ route('coa.create', ['parent' => $chartOfAccount->id]) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-plus-lg me-1"></i>Add Sub-Account
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if($chartOfAccount->children->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($chartOfAccount->children as $child)
                                            <tr>
                                                <td><strong>{{ $child->account_code }}</strong></td>
                                                <td>{{ $child->account_name }}</td>
                                                <td><span class="badge bg-secondary">{{ $child->account_type }}</span></td>
                                                <td>
                                                    @if($child->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <x-button-view href="{{ route('coa.show', $child) }}" iconOnly="true" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0 p-3">
                                No sub-accounts yet.
                                <a href="{{ route('coa.create', ['parent' => $chartOfAccount->id]) }}">Add the first one</a>.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


