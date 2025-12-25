@props([
    'href' => '#',
    'text' => 'Add',
    'size' => 'default', // 'sm', 'default', 'lg'
    'class' => '',
    'icon' => true,
])

@php
    $sizeClasses = [
        'sm' => 'btn-sm',
        'default' => '',
        'lg' => 'btn-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
@endphp

<a href="{{ $href }}" class="btn btn-primary d-flex align-items-center {{ $sizeClass }} {{ $class }}" style="gap: 0.5rem;">
    @if($icon)
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"/>
        </svg>
    @endif
    {{ $text }}
</a>

