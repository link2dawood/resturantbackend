@extends('layouts.tabler')

@section('title', 'Edit Chart of Account')

@section('styles')
<style>
    /* Material UI Typography */
    .material-headline {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        line-height: 2.5rem;
        letter-spacing: 0;
        color: #202124;
        margin: 0;
    }
    
    .material-subtitle {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.25rem;
        color: #5f6368;
        margin: 0.5rem 0 0 0;
    }
    
    /* Material UI Buttons */
    .btn-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.0892857143em;
        text-transform: uppercase;
        padding: 0.625rem 1.5rem;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 64px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-material-primary {
        background-color: #1976d2;
        color: #fff;
    }
    
    .btn-material-primary:hover {
        background-color: #1565c0;
        color: #fff;
    }
    
    .btn-material-outlined {
        background-color: transparent;
        border: 1px solid rgba(0, 0, 0, 0.12);
        color: #1976d2;
        box-shadow: none;
    }
    
    .btn-material-outlined:hover {
        background-color: rgba(25, 118, 210, 0.04);
        border-color: #1976d2;
        box-shadow: none;
    }
    
    /* Material UI Cards */
    .card-material {
        background: #ffffff;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    
    /* Material UI Form Controls */
    .form-control-material,
    .form-select-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        line-height: 1.5rem;
        color: #202124;
        background-color: #f5f5f5;
        border: none;
        border-bottom: 2px solid rgba(0, 0, 0, 0.42);
        border-radius: 4px 4px 0 0;
        padding: 0.75rem 1rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-control-material:focus,
    .form-select-material:focus {
        background-color: #fff;
        border-bottom-color: #1976d2;
        outline: none;
        box-shadow: 0 1px 0 0 #1976d2;
    }
    
    .form-control-material.is-invalid,
    .form-select-material.is-invalid {
        border-bottom-color: #d32f2f;
        background-color: #ffebee;
    }
    
    .form-control-material.is-invalid:focus,
    .form-select-material.is-invalid:focus {
        border-bottom-color: #d32f2f;
        box-shadow: 0 1px 0 0 #d32f2f;
    }
    
    .form-control-material:disabled {
        background-color: #f5f5f5;
        color: rgba(0, 0, 0, 0.38);
        cursor: not-allowed;
    }
    
    .form-label-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .form-label-material .required {
        color: #d32f2f;
    }
    
    .invalid-feedback-material {
        display: block;
        font-size: 0.75rem;
        color: #d32f2f;
        margin-top: 0.25rem;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    .form-text-material {
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.6);
        margin-top: 0.25rem;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* Material UI Switch */
    .form-switch-material .form-check-input {
        width: 3rem;
        height: 1.5rem;
        border-radius: 1.5rem;
        background-color: rgba(0, 0, 0, 0.38);
        border: none;
        transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-switch-material .form-check-input:checked {
        background-color: #1976d2;
    }
    
    .form-switch-material .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
    }
    
    .form-switch-material .form-check-input:disabled {
        background-color: rgba(0, 0, 0, 0.12);
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .form-check-label-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.87);
        margin-left: 0.5rem;
    }
    
    /* Store Selection Box */
    .store-selection-box {
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px;
        padding: 1rem;
        background-color: #fafafa;
        margin-top: 0.5rem;
    }
    
    .store-selection-box .form-check {
        padding: 0.5rem 0;
    }
    
    .store-selection-box .form-check-label {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.87);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .material-headline {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .btn-material {
            font-size: 0.8125rem;
            padding: 0.5rem 1rem;
            min-width: 56px;
            height: 32px;
        }
    }
    
    @media (max-width: 576px) {
        .material-headline {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <!-- Header Section -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="material-headline">Edit Chart of Account</h1>
                    <p class="material-subtitle">Update the details for {{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}.</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-material btn-material-outlined d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to List</span>
                </a>
            </div>

            <!-- Form Card -->
            <div class="card-material">
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('coa.update', $chartOfAccount) }}" method="POST" id="coaForm" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3 g-md-4">
                            <div class="col-12 col-md-6">
                                <label for="account_code" class="form-label-material">
                                    Account Code <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="account_code" 
                                    name="account_code" 
                                    class="form-control form-control-material @error('account_code') is-invalid @enderror" 
                                    value="{{ old('account_code', $chartOfAccount->account_code) }}" 
                                    maxlength="10" 
                                    required
                                    pattern="[0-9]+"
                                    title="Account code must be numeric"
                                >
                                @error('account_code')
                                    <div class="invalid-feedback-material">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="account_type" class="form-label-material">
                                    Account Type <span class="required">*</span>
                                </label>
                                <select 
                                    id="account_type" 
                                    name="account_type" 
                                    class="form-select form-select-material @error('account_type') is-invalid @enderror" 
                                    required
                                >
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type }}" @selected(old('account_type', $chartOfAccount->account_type) === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback-material">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 mt-md-4">
                            <label for="account_name" class="form-label-material">
                                Account Name <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="account_name" 
                                name="account_name" 
                                class="form-control form-control-material @error('account_name') is-invalid @enderror" 
                                value="{{ old('account_name', $chartOfAccount->account_name) }}" 
                                maxlength="100" 
                                required
                            >
                            @error('account_name')
                                <div class="invalid-feedback-material">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-3 mt-md-4">
                            <label for="parent_account_id" class="form-label-material">
                                Parent Account <span class="text-muted">(optional)</span>
                            </label>
                            <select 
                                id="parent_account_id" 
                                name="parent_account_id" 
                                class="form-select form-select-material @error('parent_account_id') is-invalid @enderror"
                            >
                                <option value="">None (top level)</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->id }}" @selected((string) old('parent_account_id', $chartOfAccount->parent_account_id) === (string) $parent->id)>
                                        {{ $parent->account_code }} - {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_account_id')
                                <div class="invalid-feedback-material">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-3 mt-md-4">
                            <label class="form-label-material">Store Assignment</label>
                            <div class="form-check form-switch form-switch-material mb-3">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="is_global" 
                                    name="is_global" 
                                    value="1" 
                                    @checked(old('is_global', $chartOfAccount->stores->isEmpty()))
                                >
                                <label class="form-check-label form-check-label-material" for="is_global">
                                    Available to all stores
                                </label>
                            </div>
                            <div id="store_selection" class="store-selection-box @if(old('is_global', $chartOfAccount->stores->isEmpty())) d-none @endif">
                                @if($stores->count() > 0)
                                    @foreach($stores as $store)
                                        <div class="form-check">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox" 
                                                name="store_ids[]" 
                                                value="{{ $store->id }}" 
                                                id="store{{ $store->id }}" 
                                                @checked(in_array($store->id, old('store_ids', $assignedStoreIds)))
                                            >
                                            <label class="form-check-label" for="store{{ $store->id }}">
                                                {{ $store->store_info }}
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0">No stores available.</p>
                                @endif
                            </div>
                            @error('store_ids')
                                <div class="invalid-feedback-material">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch form-switch-material mt-3 mt-md-4">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="is_active" 
                                name="is_active" 
                                value="1" 
                                @checked(old('is_active', $chartOfAccount->is_active)) 
                                @disabled($chartOfAccount->is_system_account)
                            >
                            <label class="form-check-label form-check-label-material" for="is_active">
                                Active
                            </label>
                            @if($chartOfAccount->is_system_account)
                                <div class="form-text-material mt-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    System accounts are always active and cannot be deactivated.
                                </div>
                            @endif
                            @error('is_active')
                                <div class="invalid-feedback-material">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('coa.index') }}" class="btn btn-material btn-material-outlined">
                                <span>Cancel</span>
                            </a>
                            <button type="submit" class="btn btn-material btn-material-primary">
                                <i class="bi bi-save me-2"></i>
                                <span>Update Account</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('coaForm');
        const globalToggle = document.getElementById('is_global');
        const storeSelection = document.getElementById('store_selection');
        const accountCodeInput = document.getElementById('account_code');
        const parentAccountSelect = document.getElementById('parent_account_id');
        const currentAccountId = {{ $chartOfAccount->id }};

        // Global toggle functionality
        globalToggle.addEventListener('change', function () {
            if (this.checked) {
                storeSelection.classList.add('d-none');
                storeSelection.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            } else {
                storeSelection.classList.remove('d-none');
            }
        });

        // Account code validation - only numbers
        accountCodeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Prevent selecting current account as parent
        parentAccountSelect.addEventListener('change', function() {
            if (this.value == currentAccountId) {
                this.setCustomValidity('An account cannot be its own parent.');
            } else {
                this.setCustomValidity('');
            }
        });

        // Client-side form validation
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Custom validation for store selection
            const isGlobal = globalToggle.checked;
            const storeCheckboxes = storeSelection.querySelectorAll('input[type="checkbox"]:checked');
            
            if (!isGlobal && storeCheckboxes.length === 0) {
                e.preventDefault();
                const existingError = storeSelection.querySelector('.invalid-feedback-material');
                if (existingError) {
                    existingError.remove();
                }
                const storeError = document.createElement('div');
                storeError.className = 'invalid-feedback-material';
                storeError.textContent = 'Select at least one store or mark the account as global.';
                storeSelection.appendChild(storeError);
                storeSelection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            form.classList.add('was-validated');
        });

        // Real-time validation feedback
        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    });
</script>
@endpush
