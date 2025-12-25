@props([
    'text' => 'Search',
    'size' => 'default', // 'sm', 'default', 'lg'
    'class' => '',
    'icon' => true,
    'type' => 'button', // 'button' or 'submit'
])

@php
    $sizeClasses = [
        'sm' => 'btn-sm',
        'default' => '',
        'lg' => 'btn-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
@endphp

<button type="{{ $type }}" class="btn btn-outline-primary {{ $sizeClass }} d-flex align-items-center {{ $class }}" style="gap: 0.5rem;">
    @if($icon)
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
        </svg>
    @endif
    {{ $text }}
</button>

