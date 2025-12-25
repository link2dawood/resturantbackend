@props([
    'href' => '#',
    'text' => 'View',
    'size' => 'sm', // 'sm', 'default', 'lg'
    'class' => '',
    'icon' => true,
    'iconOnly' => false,
    'title' => 'View Details',
])

@php
    $sizeClasses = [
        'sm' => 'btn-sm',
        'default' => '',
        'lg' => 'btn-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
    $displayClass = $iconOnly ? 'd-flex align-items-center justify-content-center' : 'd-flex align-items-center';
    $widthHeight = $iconOnly ? 'width: 32px; height: 32px;' : '';
@endphp

<a href="{{ $href }}" class="btn btn-outline-primary {{ $sizeClass }} {{ $displayClass }} {{ $class }}" style="gap: 0.5rem; {{ $widthHeight }}" title="{{ $title }}">
    @if($icon)
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    @endif
    @if(!$iconOnly)
        {{ $text }}
    @endif
</a>

