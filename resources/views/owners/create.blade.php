@extends('layouts.tabler')

@section('title', 'Create Owner')

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('owners.index') }}">Owners</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </nav>
                <h2 class="page-title">Create Owner</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('owners.create') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8 col-md-12">
                <!-- Basic Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Basic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label required">Name</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label required">Email</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label required">Password</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Picture</label>
                                    <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                                    @error('avatar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label required">State</label>
                                    <select name="state" class="form-control @error('state') is-invalid @enderror" required>
                                        @foreach(\App\Helpers\USStates::getStatesFromDatabaseForSelect() as $abbr => $name)
                                            <option value="{{ $abbr }}" {{ old('state') == $abbr ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                      rows="3" placeholder="Full home address">{{ old('home_address') }}</textarea>
                            @error('home_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Personal Phone</label>
                                    <input type="tel" name="personal_phone" class="form-control phone-input @error('personal_phone') is-invalid @enderror" 
                                           value="{{ old('personal_phone') }}" placeholder="(555) 123-4567" maxlength="14">
                                    @error('personal_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Personal Email</label>
                                    <input type="email" name="personal_email" class="form-control @error('personal_email') is-invalid @enderror" 
                                           value="{{ old('personal_email') }}" placeholder="Personal email address">
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
                            <label class="form-label required">Corporate Address</label>
                            <textarea name="corporate_address" class="form-control @error('corporate_address') is-invalid @enderror" 
                                      rows="3" placeholder="Full corporate address" required>{{ old('corporate_address') }}</textarea>
                            @error('corporate_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Corporate Phone</label>
                                    <input type="tel" name="corporate_phone" class="form-control phone-input @error('corporate_phone') is-invalid @enderror" 
                                           value="{{ old('corporate_phone') }}" placeholder="(555) 123-4567" maxlength="14">
                                    @error('corporate_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Corporate Email</label>
                                    <input type="email" name="corporate_email" class="form-control @error('corporate_email') is-invalid @enderror" 
                                           value="{{ old('corporate_email') }}" placeholder="Corporate email address">
                                    @error('corporate_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fann's Philly Email</label>
                            <input type="email" name="fanns_philly_email" class="form-control @error('fanns_philly_email') is-invalid @enderror" 
                                   value="{{ old('fanns_philly_email') }}" placeholder="Fann's Philly specific email">
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
                                    <label class="form-label required">Corporate EIN Number</label>
                                    <input type="text" name="corporate_ein" class="form-control @error('corporate_ein') is-invalid @enderror" 
                                           value="{{ old('corporate_ein') }}" placeholder="XX-XXXXXXX" required>
                                    @error('corporate_ein')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Corporate Creation Date</label>
                                    <input type="text" name="corporate_creation_date" required class="form-control date-input @error('corporate_creation_date') is-invalid @enderror" 
                                           value="{{ old('corporate_creation_date') }}" placeholder="MM-DD-YYYY" maxlength="10">
                                    @error('corporate_creation_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
                                Create Owner
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
                        <p class="text-muted">Fill in all the relevant owner information. Fields marked with <span class="text-danger">*</span> are required.</p>
                        <p class="text-muted">Personal and corporate information can be updated later if needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
