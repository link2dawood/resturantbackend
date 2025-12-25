@extends('layouts.tabler')

@section('title', 'Edit Store')



@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Edit Store</h1>
    <form action="{{ route('stores.update', $store->id) }}" method="POST">
        @csrf
        @method('PUT')
        @if(Auth::user()->isAdmin() || Auth::user()->isFranchisor())
        <div class="mb-3">
            <label for="created_by" class="form-label">Controlling Owner <span class="text-danger">*</span></label>
            <select class="form-control" id="created_by" name="created_by" required>
               <option value="">Select Owner</option>
                @if(Auth::user()->isFranchisor())
                    <option value="{{ $franchisor->id }}" data-store-type="corporate" @if($store->created_by == $franchisor->id) selected @endif>{{ $franchisor->name }} (Franchisor - for Corporate Stores)</option>
                @endif
                @foreach ($owners as $owner)
                    @if(!$owner->isFranchisor())
                        <option value="{{ $owner->id }}" data-store-type="franchisee" @if($store->created_by == $owner->id) selected @endif>{{ $owner->name }} (Franchisee - for Franchisee Locations)</option>
                    @endif
                @endforeach
            </select>
            <small class="form-text text-muted">Corporate Stores must be assigned to Franchisor. Franchisee locations are assigned to the Owner (Franchisee).</small>
        </div>
        @else
        <input type="hidden" class="form-control" name="created_by" value="{{Auth::user()->id}}">
        @endif
        <div class="mb-3">
            <label for="store_type" class="form-label">Store Type <span class="text-danger">*</span></label>
            <select class="form-control" id="store_type" name="store_type" required>
                <option value="">Select Store Type</option>
                <option value="corporate" {{ old('store_type', $store->store_type) == 'corporate' ? 'selected' : '' }}>Corporate Store (Franchisor)</option>
                <option value="franchisee" {{ old('store_type', $store->store_type) == 'franchisee' ? 'selected' : '' }}>Franchisee Location (Owner)</option>
            </select>
            <small class="form-text text-muted">
                <strong>Corporate Store:</strong> Controlled by Franchisor, reports to Franchisor, run by Managers<br>
                <strong>Franchisee Location:</strong> Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
            </small>
        </div>
        <div class="mb-3">
            <label for="store_info" class="form-label">Store Info</label>
            <input type="text" class="form-control" id="store_info" name="store_info" value="{{ $store->store_info }}" required>
        </div>
        <div class="mb-3">
            <label for="contact_name" class="form-label">Contact Name</label>
            <input type="text" class="form-control" id="contact_name" name="contact_name" value="{{ $store->contact_name }}" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control phone-input" id="phone" name="phone" value="{{ $store->phone }}" placeholder="(555) 123-4567" maxlength="14" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ $store->address }}" required>
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City</label>
            <input type="text" class="form-control" id="city" name="city" value="{{ $store->city }}" required>
        </div>
        <div class="mb-3">
            <label for="state" class="form-label">State</label>
            <select name="state" id="state" class="form-control @error('state') is-invalid @enderror" required>
                @foreach(\App\Helpers\USStates::getStatesFromDatabaseForSelect() as $abbr => $name)
                    <option value="{{ $abbr }}" {{ old('state', $store->state) == $abbr ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @error('state')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label for="zip" class="form-label">Zip</label>
            <input type="text" class="form-control" id="zip" name="zip" value="{{ $store->zip }}" required>
        </div>
        <div class="mb-3">
            <label for="sales_tax_rate" class="form-label">Sales Tax Rate</label>
            <input type="number" step="0.01" class="form-control" id="sales_tax_rate" name="sales_tax_rate" value="{{ $store->sales_tax_rate }}" required>
        </div>
        <div class="mb-3">
            <label for="medicare_tax_rate" class="form-label">Medicare Tax Rate (Optional)</label>
            <input type="number" step="0.01" class="form-control" id="medicare_tax_rate" name="medicare_tax_rate" value="{{ $store->medicare_tax_rate }}" placeholder="0.00">
            <small class="form-text text-muted">Leave blank if not applicable to your business</small>
        </div>
        <button type="submit" class="btn btn-primary">Update Store</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const storeTypeSelect = document.getElementById('store_type');
    const ownerSelect = document.getElementById('created_by');
    
    if (storeTypeSelect && ownerSelect) {
        // Function to update store type options based on owner selection
        function updateStoreTypeFromOwner() {
            const selectedOwnerOption = ownerSelect.options[ownerSelect.selectedIndex];
            const requiredStoreType = selectedOwnerOption.getAttribute('data-store-type');
            
            if (requiredStoreType && ownerSelect.value) {
                // Restrict store type to match the selected owner
                const storeTypeOptions = storeTypeSelect.querySelectorAll('option');
                storeTypeOptions.forEach(option => {
                    if (option.value === '') {
                        // Keep the "Select Store Type" option visible
                        option.style.display = '';
                    } else if (option.value === requiredStoreType) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Auto-select the matching store type if not already selected
                if (storeTypeSelect.value !== requiredStoreType) {
                    storeTypeSelect.value = requiredStoreType;
                }
            } else {
                // If no owner selected, show all store type options
                const storeTypeOptions = storeTypeSelect.querySelectorAll('option');
                storeTypeOptions.forEach(option => {
                    option.style.display = '';
                });
            }
        }
        
        // Function to update owner options based on store type selection
        function updateOwnerFromStoreType() {
            const selectedType = storeTypeSelect.value;
            const options = ownerSelect.querySelectorAll('option[data-store-type]');
            
            // Show/hide options based on store type
            options.forEach(option => {
                if (selectedType === '') {
                    option.style.display = '';
                } else if (option.getAttribute('data-store-type') === selectedType) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset selection if current selection doesn't match store type
            if (selectedType && ownerSelect.value) {
                const selectedOption = ownerSelect.options[ownerSelect.selectedIndex];
                if (selectedOption.getAttribute('data-store-type') !== selectedType) {
                    ownerSelect.value = '';
                }
            }
        }
        
        // When owner is selected, restrict store type
        ownerSelect.addEventListener('change', function() {
            updateStoreTypeFromOwner();
        });
        
        // When store type is selected, filter owner options
        storeTypeSelect.addEventListener('change', function() {
            updateOwnerFromStoreType();
        });
        
        // Initialize on page load
        if (ownerSelect.value) {
            updateStoreTypeFromOwner();
        } else {
            updateOwnerFromStoreType();
        }
    }
});
</script>
@endsection
