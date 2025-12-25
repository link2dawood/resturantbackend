@props([
    'href' => '#',
    'action' => null, // For form actions
    'text' => 'Delete',
    'size' => 'sm', // 'sm', 'default', 'lg'
    'class' => '',
    'icon' => true,
    'iconOnly' => false,
    'title' => 'Delete',
    'confirmMessage' => 'Are you sure you want to delete this item? This action cannot be undone.',
    'method' => 'DELETE',
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

@if($action)
    <form action="{{ $action }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $confirmMessage }}')">
        @csrf
        @method($method)
        <button type="submit" class="btn btn-outline-danger {{ $sizeClass }} {{ $displayClass }} {{ $class }}" style="gap: 0.5rem; {{ $widthHeight }}" title="{{ $title }}">
            @if($icon)
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3,6 5,6 21,6"/>
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            @endif
            @if(!$iconOnly)
                {{ $text }}
            @endif
        </button>
    </form>
@else
    <a href="{{ $href }}" class="btn btn-outline-danger {{ $sizeClass }} {{ $displayClass }} {{ $class }}" style="gap: 0.5rem; {{ $widthHeight }}" title="{{ $title }}" onclick="return confirm('{{ $confirmMessage }}')">
        @if($icon)
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3,6 5,6 21,6"/>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                <line x1="10" y1="11" x2="10" y2="17"/>
                <line x1="14" y1="11" x2="14" y2="17"/>
            </svg>
        @endif
        @if(!$iconOnly)
            {{ $text }}
        @endif
    </a>
@endif

