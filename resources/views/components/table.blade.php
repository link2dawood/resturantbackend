@props([
    'headers' => [],
    'emptyMessage' => 'No items found',
    'emptyDescription' => 'Get started by creating your first item.',
    'emptyActionHref' => null,
    'emptyActionText' => 'Create Item',
    'cardTitle' => null,
    'cardHeaderActions' => null,
    'class' => '',
    'responsive' => true,
])

@php
    // Ensure headers is an array
    if (is_string($headers)) {
        $headers = [];
    }
@endphp

<div class="card {{ $class }}">
    @if($cardTitle || $cardHeaderActions)
        <div class="card-header border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                @if($cardTitle)
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">{{ $cardTitle }}</h3>
                @endif
                @if($cardHeaderActions)
                    <div class="d-flex gap-2">
                        {{ $cardHeaderActions }}
                    </div>
                @endif
            </div>
        </div>
    @endif
    <div class="card-body p-0">
        @if(isset($slot) && trim($slot) !== '' && $slot !== '')
            @if($responsive)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" style="font-size: 0.875rem; font-family: 'Google Sans', sans-serif;">
                        <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                            <tr>
                                @foreach($headers as $header)
                                    @if(is_array($header))
                                        <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; {{ isset($header['align']) ? 'text-align: ' . $header['align'] . ';' : '' }} {{ isset($header['style']) ? $header['style'] : '' }}">
                                            {{ $header['label'] ?? $header['text'] ?? '' }}
                                        </th>
                                    @else
                                        <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">
                                            {{ $header }}
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{ $slot }}
                        </tbody>
                    </table>
                </div>
            @else
                <table class="table table-striped table-hover mb-0" style="font-size: 0.875rem; font-family: 'Google Sans', sans-serif;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            @foreach($headers as $header)
                                @if(is_array($header))
                                    <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; {{ isset($header['align']) ? 'text-align: ' . $header['align'] . ';' : '' }} {{ isset($header['style']) ? $header['style'] : '' }}">
                                        {{ $header['label'] ?? $header['text'] ?? '' }}
                                    </th>
                                @else
                                    <th style="font-weight: 500; color: var(--google-grey-700, #3c4043); padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px;">
                                        {{ $header }}
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        {{ $slot }}
                    </tbody>
                </table>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--google-grey-400, #9aa0a6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
                    </svg>
                </div>
                <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 400; color: var(--google-grey-700, #3c4043); margin-bottom: 0.5rem;">{{ $emptyMessage }}</h3>
                @if($emptyDescription)
                    <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem;">{{ $emptyDescription }}</p>
                @endif
                @if($emptyActionHref)
                    <x-button-add href="{{ $emptyActionHref }}" text="{{ $emptyActionText }}" />
                @endif
            </div>
        @endif
    </div>
</div>

