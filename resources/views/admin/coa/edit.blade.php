@extends('layouts.tabler')

@section('title', 'Edit Chart of Account')

@section('content')
<div class="container-xl mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-0">Edit Chart of Account</h1>
                    <p class="text-muted mb-0">Update the details for {{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}.</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.update', $chartOfAccount) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" id="account_code" name="account_code" class="form-control @error('account_code') is-invalid @enderror" value="{{ old('account_code', $chartOfAccount->account_code) }}" maxlength="10" required>
                                @error('account_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select id="account_type" name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type }}" @selected(old('account_type', $chartOfAccount->account_type) === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" id="account_name" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name', $chartOfAccount->account_name) }}" maxlength="100" required>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parent_account_id" class="form-label">Parent Account (optional)</label>
                            <select id="parent_account_id" name="parent_account_id" class="form-select @error('parent_account_id') is-invalid @enderror">
                                <option value="">None (top level)</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->id }}" @selected((string) old('parent_account_id', $chartOfAccount->parent_account_id) === (string) $parent->id)>
                                        {{ $parent->account_code }} - {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1" @checked(old('is_global', $chartOfAccount->stores->isEmpty()))>
                                <label class="form-check-label" for="is_global">Available to all stores</label>
                            </div>
                            <div id="store_selection" class="border rounded p-3 @if(old('is_global', $chartOfAccount->stores->isEmpty())) d-none @endif">
                                @foreach($stores as $store)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store{{ $store->id }}" @checked(in_array($store->id, old('store_ids', $assignedStoreIds)))>
                                        <label class="form-check-label" for="store{{ $store->id }}">{{ $store->store_info }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('store_ids')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $chartOfAccount->is_active))>
                            <label class="form-check-label" for="is_active">Active</label>
                            @error('is_active')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update Account
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
        const globalToggle = document.getElementById('is_global');
        const storeSelection = document.getElementById('store_selection');

        globalToggle.addEventListener('change', function () {
            if (this.checked) {
                storeSelection.classList.add('d-none');
                storeSelection.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            } else {
                storeSelection.classList.remove('d-none');
            }
        });
    });
</script>
@endpush


