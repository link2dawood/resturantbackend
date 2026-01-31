@extends('layouts.tabler')

@section('title', 'Edit Revenue Income Type')

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('revenue-income-types.index') }}">Revenue Income Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit {{ $revenueIncomeType->name }}</li>
                    </ol>
                </nav>
                <h2 class="page-title">Edit Revenue Income Type</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form action="{{ route('revenue-income-types.update', $revenueIncomeType) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Revenue Income Type Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Name</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $revenueIncomeType->name) }}" placeholder="e.g., Bitcoin, Apple Pay">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <!-- <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Category</label>
                                    <select name="category" class="form-control @error('category') is-invalid @enderror">
                                        <option value="">Select Category</option>
                                        <option value="cash" {{ old('category', $revenueIncomeType->category) == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ old('category', $revenueIncomeType->category) == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="check" {{ old('category', $revenueIncomeType->category) == 'check' ? 'selected' : '' }}>Check</option>
                                        <option value="online" {{ old('category', $revenueIncomeType->category) == 'online' ? 'selected' : '' }}>Online</option>
                                        <option value="crypto" {{ old('category', $revenueIncomeType->category) == 'crypto' ? 'selected' : '' }}>Crypto</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div> -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">COA</label>
                                    <select name="default_coa_id" class="form-control @error('default_coa_id') is-invalid @enderror">
                                        <option value="">Select One</option>
                                        @foreach ($chartOfAccounts as $coa)
                                            <option value="{{ $coa->id }}" {{ $revenueIncomeType->default_coa_id == $coa->id ? 'selected' : '' }}>{{ $coa->account_code }} - {{ $coa->account_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('default_coa_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" placeholder="Optional description for this revenue type">{{ old('description', $revenueIncomeType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" 
                                           value="{{ old('sort_order', $revenueIncomeType->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-hint">Lower numbers appear first in lists</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                               {{ old('is_active', $revenueIncomeType->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                    <small class="form-hint">Only active types will be available for selection</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('revenue-income-types.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">Update Revenue Income Type</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Categories Explained</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <strong>Cash</strong> - Physical cash payments
                        </div>
                        <div class="list-group-item">
                            <strong>Card</strong> - Credit/debit card payments
                        </div>
                        <div class="list-group-item">
                            <strong>Check</strong> - Check payments
                        </div>
                        <div class="list-group-item">
                            <strong>Online</strong> - Third-party platforms (UberEats, DoorDash, etc.)
                        </div>
                        <div class="list-group-item">
                            <strong>Crypto</strong> - Cryptocurrency payments
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">COA Explained</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach ($chartOfAccounts as $coa)
                        <div class="list-group-item">
                            <strong>{{@$coa->account_code}}</strong> - {{@$coa->account_name}}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection