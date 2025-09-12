@extends('layouts.tabler')

@section('title', 'Create Store')

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('stores.index') }}">Stores</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </nav>
                <h2 class="page-title">Create Store</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('stores.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Store Information</h3>
                    </div>
                    <div class="card-body">
          @if(Auth::user()->hasPermission('manage_owners'))
        <div class="mb-3">
            <label for="store_info" class="form-label">Owners</label>
            <select class="form-control"  name="created_by"  required>
               <option value="">Select Owner</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                @endforeach
            </select>
            
        </div>
        @else
        <input type="hidden" class="form-control"  name="created_by" value="{{Auth::user()->id}}" >
        @endif
        <div class="mb-3">
            <label for="store_info" class="form-label">Store Info</label>
            <input type="text" class="form-control" id="store_info" name="store_info" required>
        </div>
        <div class="mb-3">
            <label for="contact_name" class="form-label">Contact Name</label>
            <input type="text" class="form-control" id="contact_name" name="contact_name" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control phone-input" id="phone" name="phone" placeholder="(555) 123-4567" maxlength="14" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City</label>
            <input type="text" class="form-control" id="city" name="city" required>
        </div>
        <div class="mb-3">
            <label for="state" class="form-label">State</label>
            <select name="state" id="state" class="form-control @error('state') is-invalid @enderror" required>
                @foreach(\App\Helpers\USStates::getStatesFromDatabaseForSelect() as $abbr => $name)
                    <option value="{{ $abbr }}" {{ old('state') == $abbr ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @error('state')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label for="zip" class="form-label">Zip</label>
            <input type="text" class="form-control" id="zip" name="zip" required>
        </div>
        <div class="mb-3">
            <label for="sales_tax_rate" class="form-label">Sales Tax Rate</label>
            <input type="number" step="0.01" class="form-control" id="sales_tax_rate" name="sales_tax_rate" required>
        </div>
        <div class="mb-3">
            <label for="medicare_tax_rate" class="form-label">SS / Medicare Tax Rate (Optional)</label>
            <input type="number" step="0.01" class="form-control" id="medicare_tax_rate" name="medicare_tax_rate" placeholder="0.00">
            <small class="form-text text-muted">Leave blank if not applicable to your business</small>
        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Create Store
                            </button>
                            <a href="{{ route('stores.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Information</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Fill in all store information. Fields marked with <span class="text-danger">*</span> are required.</p>
                        <p class="text-muted">Tax rates can be updated later if needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
