@extends('layouts.tabler')

@section('title', 'Profile')

@section('content')
<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Profile
                </h2>
                <div class="text-muted mt-1">Manage your account information</div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="m7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="m20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="m16 5l3 3"/></svg>
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                </div>
                <div>{{ session('success') }}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        @endif
        
        <div class="row row-cards">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: url({{ $user->avatar_url }})"></span>
                            </div>
                            <div class="col">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <div class="text-muted">{{ $user->name }}</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <div class="text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    @if($user->google_id)
                                    <div class="col-12">
                                        <div class="mb-2">
                                            <label class="form-label">Google Account</label>
                                            <div><span class="badge bg-success">Connected</span></div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Avatar Management Card -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Avatar Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <span class="avatar avatar-xl" 
                                style="background-image: url('{{ asset('storage/avatars/' . $user->avatar) }}')">
                            </span>
                        </div>
                        <div class="col">
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <p class="text-muted mb-0">Current avatar</p>
                        </div>
                    </div>
                    
                    
                    <!-- Simple File Upload -->
                    <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                        @csrf
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Choose new avatar</label>
                            <input type="file" 
                            class="form-control" 
                            id="avatar" 
                            name="avatar" 
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            required>
                            <div class="form-text">
                                Supported formats: JPG, PNG, GIF. Maximum size: 2MB.
                            </div>
                        </div>
                        
                        <!-- Preview Area -->
                        <div id="imagePreview" class="mb-3" style="display: none;">
                            <label class="form-label">Preview</label>
                            <div class="d-flex align-items-center">
                                <img id="previewImage" src="" alt="Preview" class="avatar avatar-lg me-3">
                                <div>
                                    <div id="previewFileName" class="fw-bold"></div>
                                    <div id="previewFileSize" class="text-muted small"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="uploadButton">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                    <path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"/>
                                    <polyline points="9 15 12 12 15 15"/>
                                    <path d="m12 12l0 9"/>
                                </svg>
                                Upload Avatar
                            </button>
                            
                            @if($user->avatar)
                            <a href="{{ route('profile.avatar.remove') }}" 
                            class="btn btn-outline-danger"
                            onclick="event.preventDefault(); if(confirm('Are you sure you want to remove your avatar?')) { document.getElementById('remove-avatar-form').submit(); }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                <line x1="4" y1="7" x2="20" y2="7"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                                <path d="m5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                <path d="m9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                            </svg>
                            Remove Avatar
                        </a>
                        @endif
                    </div>
                </form>
                
                @if($user->avatar)
                <form id="remove-avatar-form" action="{{ route('profile.avatar.remove') }}" method="POST" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="neuro-card neuro-fade-in">
            <h3 class="card-title mb-4">Account Details</h3>
            <div class="neuro-info-grid">
                <div class="neuro-info-item">
                    <label class="neuro-label">Account Created</label>
                    <div class="text-muted">{{ $user->created_at->format('M d, Y') }}</div>
                </div>
                <div class="neuro-info-item">
                    <label class="neuro-label">Last Updated</label>
                    <div class="text-muted">{{ $user->updated_at->format('M d, Y') }}</div>
                </div>
                <div class="neuro-info-item">
                    <label class="neuro-label">Email Verified</label>
                    <div>
                        @if($user->email_verified_at)
                        <span class="badge bg-success">Verified</span>
                        @else
                        <span class="badge bg-warning">Not Verified</span>
                        @endif
                    </div>
                </div>
                <div class="neuro-info-item">
                    <label class="neuro-label">Account Type</label>
                    <div>
                        @if($user->google_id)
                        <span class="badge bg-info">Google OAuth</span>
                        @else
                        <span class="badge bg-primary">Standard</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection