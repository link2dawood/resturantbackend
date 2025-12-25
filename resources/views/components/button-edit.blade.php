@props([
    'href' => '#',
    'text' => 'Edit',
    'size' => 'sm', // 'sm', 'default', 'lg'
    'class' => '',
    'icon' => true,
    'iconOnly' => false,
    'title' => 'Edit',
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

<a href="{{ $href }}" class="btn btn-outline-secondary {{ $sizeClass }} {{ $displayClass }} {{ $class }}" style="gap: 0.5rem; {{ $widthHeight }}" title="{{ $title }}">
    @if($icon)
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
    @endif
    @if(!$iconOnly)
        {{ $text }}
    @endif
</a>

