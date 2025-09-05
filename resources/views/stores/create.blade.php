@extends('layouts.tabler')

@section('title', 'Create Store')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Create Store</h1>
    <form action="{{ route('stores.store') }}" method="POST">
        @csrf
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
            <input type="text" class="form-control" id="phone" name="phone" required>
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
            <input type="text" class="form-control" id="state" name="state" required>
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
            <label for="medicare_tax_rate" class="form-label">Medicare Tax Rate</label>
            <input type="number" step="0.01" class="form-control" id="medicare_tax_rate" name="medicare_tax_rate" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Store</button>
    </form>
</div>
@endsection
