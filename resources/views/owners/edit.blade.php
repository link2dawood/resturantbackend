@extends('layouts.tabler')

@section('title', 'Edit Owner')

@section('content')
<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Edit Owner
                </h2>
                <div class="text-muted mt-1">Update owner information</div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('owners.index') }}" class="btn btn-outline-secondary d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>
                        Back to Owners
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">There were some errors with your submission:</h4>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <form action="{{ route('owners.update', $owner->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row row-cards">
                <!-- Basic Information -->
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Name</label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                               value="{{ old('name', $owner->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Email</label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                               value="{{ old('email', $owner->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                                               placeholder="Leave blank to keep current password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-hint">Leave blank if you don't want to change the password</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Avatar</label>
                                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                                        @error('avatar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if ($owner->avatar)
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/avatars/' . $owner->avatar) }}" alt="Current Avatar" 
                                                     class="avatar avatar-md">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Home Address</label>
                                <textarea name="home_address" class="form-control @error('home_address') is-invalid @enderror" 
                                          rows="3" placeholder="Full home address">{{ old('home_address', $owner->home_address) }}</textarea>
                                @error('home_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Personal Phone</label>
                                        <input type="text" name="personal_phone" class="form-control @error('personal_phone') is-invalid @enderror" 
                                               value="{{ old('personal_phone', $owner->personal_phone) }}" placeholder="Personal phone number">
                                        @error('personal_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Personal Email</label>
                                        <input type="email" name="personal_email" class="form-control @error('personal_email') is-invalid @enderror" 
                                               value="{{ old('personal_email', $owner->personal_email) }}" placeholder="Personal email address">
                                        @error('personal_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Corporate Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Corporate Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Corporate Address</label>
                                <textarea name="corporate_address" class="form-control @error('corporate_address') is-invalid @enderror" 
                                          rows="3" placeholder="Full corporate address">{{ old('corporate_address', $owner->corporate_address) }}</textarea>
                                @error('corporate_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Corporate Phone</label>
                                        <input type="text" name="corporate_phone" class="form-control @error('corporate_phone') is-invalid @enderror" 
                                               value="{{ old('corporate_phone', $owner->corporate_phone) }}" placeholder="Corporate phone number">
                                        @error('corporate_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Corporate Email</label>
                                        <input type="email" name="corporate_email" class="form-control @error('corporate_email') is-invalid @enderror" 
                                               value="{{ old('corporate_email', $owner->corporate_email) }}" placeholder="Corporate email address">
                                        @error('corporate_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fann's Philly Email</label>
                                <input type="email" name="fanns_philly_email" class="form-control @error('fanns_philly_email') is-invalid @enderror" 
                                       value="{{ old('fanns_philly_email', $owner->fanns_philly_email) }}" placeholder="Fann's Philly specific email">
                                @error('fanns_philly_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Business Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Corporate EIN Number</label>
                                        <input type="text" name="corporate_ein" class="form-control @error('corporate_ein') is-invalid @enderror" 
                                               value="{{ old('corporate_ein', $owner->corporate_ein) }}" placeholder="XX-XXXXXXX">
                                        @error('corporate_ein')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Corporate Creation Date</label>
                                        <input type="date" name="corporate_creation_date" class="form-control @error('corporate_creation_date') is-invalid @enderror" 
                                               value="{{ old('corporate_creation_date', $owner->corporate_creation_date?->format('Y-m-d')) }}">
                                        @error('corporate_creation_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M5 12l5 5l10 -10"/>
                                    </svg>
                                    Update Owner
                                </button>
                                <a href="{{ route('owners.index') }}" class="btn btn-secondary">
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
                            <p class="text-muted">Update the owner's comprehensive profile information. Fields marked with <span class="text-danger">*</span> are required.</p>
                            <p class="text-muted">All other fields are optional and can be filled as needed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection