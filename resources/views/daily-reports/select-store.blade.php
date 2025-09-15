@extends('layouts.tabler')
@section('title', 'Select Store - Daily Report')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<style>
:root {
  --google-blue: #4285f4;
  --google-blue-50: #e8f0fe;
  --google-blue-600: #1a73e8;
  --google-blue-700: #1967d2;
  --google-green: #34a853;
  --google-green-50: #e6f4ea;
  --google-grey-50: #f8f9fa;
  --google-grey-100: #f1f3f4;
  --google-grey-200: #e8eaed;
  --google-grey-300: #dadce0;
  --google-grey-600: #5f6368;
  --google-grey-700: #3c4043;
  --google-grey-900: #202124;
}

* {
  font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

.google-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}

.google-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.08), 0 4px 8px 3px rgba(60, 64, 67, 0.04);
  border: none;
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
}

.google-card:hover {
  box-shadow: 0 2px 6px 2px rgba(60, 64, 67, 0.15), 0 8px 24px 4px rgba(60, 64, 67, 0.12);
}

.google-btn {
  font-family: 'Google Sans', sans-serif;
  font-weight: 500;
  font-size: 14px;
  border-radius: 20px;
  padding: 10px 24px;
  border: none;
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.google-btn-outlined {
  background: transparent;
  color: var(--google-blue);
  border: 1px solid var(--google-grey-300);
}

.google-btn-outlined:hover {
  background: var(--google-blue-50);
  border-color: var(--google-blue);
  color: var(--google-blue);
}

.progress-bar {
  height: 4px;
  background: var(--google-blue);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
}
</style>
@endsection

@section('content')

<div class="google-container">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 28px; font-weight: 400; color: var(--google-grey-900); margin: 0 0 8px 0;">Create Daily Report</h1>
        <p style="font-size: 16px; color: var(--google-grey-600); margin: 0 0 24px 0;">Step 1 of 3: Select a store</p>

        <!-- Progress bar -->
        <div style="background: var(--google-grey-200); height: 4px; border-radius: 2px; margin: 0 auto 32px auto; max-width: 300px;">
            <div class="progress-bar" style="width: 33%;"></div>
        </div>
    </div>

    @if(session('success'))
        <div class="google-card" style="background: var(--google-green-50); border-left: 4px solid var(--google-green); margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: var(--google-green);">check_circle</span>
                <span style="color: var(--google-green); font-weight: 500;">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="google-card" style="background: #fce8e6; border-left: 4px solid #ea4335; margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: #ea4335;">error</span>
                <span style="color: #ea4335; font-weight: 500;">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="google-card" style="background: #fef7e0; border-left: 4px solid #fbbc04; margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: #f57c00;">warning</span>
                <span style="color: #f57c00; font-weight: 500;">{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    <div class="google-card" style="padding: 24px; margin-bottom: 24px;">
        <p style="font-size: 16px; color: var(--google-grey-600); margin: 0 0 32px 0; text-align: center;">Choose the store for which you want to create a daily report</p>

        @if($stores->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                @foreach($stores as $store)
                    <div class="google-card store-card"
                         style="cursor: pointer; padding: 24px; transition: all 0.2s cubic-bezier(0.2, 0, 0, 1); border: 2px solid transparent;"
                         onclick="selectStore({{ $store->id }})"
                         onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='var(--google-blue)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='transparent'">

                        <div style="text-align: center;">
                            <div style="background: var(--google-blue-50); padding: 16px; border-radius: 50%; display: inline-flex; margin-bottom: 16px;">
                                <span class="material-symbols-outlined" style="font-size: 32px; color: var(--google-blue);">storefront</span>
                            </div>

                            <h3 style="font-size: 18px; font-weight: 500; color: var(--google-grey-900); margin: 0 0 8px 0;">{{ $store->store_info }}</h3>

                            <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 16px; color: var(--google-grey-600);">location_on</span>
                                <span style="font-size: 14px; color: var(--google-grey-600);">
                                    {{ $store->address }}, {{ $store->city }}, {{ $store->state }} {{ $store->zip }}
                                </span>
                            </div>

                            @if($store->phone)
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 20px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--google-grey-600);">phone</span>
                                    <span style="font-size: 14px; color: var(--google-grey-600);">{{ $store->phone }}</span>
                                </div>
                            @endif

                            <div style="background: var(--google-blue); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px;">
                                <span>Select This Store</span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Hidden form for submission -->
            <form id="storeSelectionForm" method="GET" action="{{ route('daily-reports.select-date') }}">
                <input type="hidden" name="store_id" id="selectedStoreId">
            </form>
        @else
            <div style="text-align: center; padding: 60px 32px;">
                <div style="display: inline-flex; padding: 20px; background: var(--google-grey-50); border-radius: 50%; margin-bottom: 24px;">
                    <span class="material-symbols-outlined" style="font-size: 48px; color: var(--google-grey-400);">store_mall_directory</span>
                </div>
                <h2 style="font-size: 22px; font-weight: 400; color: var(--google-grey-900); margin: 0 0 8px 0;">No Stores Available</h2>
                <p style="font-size: 16px; color: var(--google-grey-600); margin: 0 0 32px 0;">You don't have access to any stores. Please contact an administrator.</p>
                <a href="{{ route('daily-reports.index') }}" class="google-btn google-btn-outlined">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Reports
                </a>
            </div>
        @endif
    </div>

    <!-- Footer Navigation -->
    @if($stores->count() > 0)
        <div style="text-align: center; margin-top: 32px;">
            <a href="{{ route('daily-reports.index') }}" class="google-btn google-btn-outlined">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Reports List
            </a>
        </div>
    @endif
</div>

<script>
function selectStore(storeId) {
    document.getElementById('selectedStoreId').value = storeId;
    document.getElementById('storeSelectionForm').submit();
}
</script>

@endsection