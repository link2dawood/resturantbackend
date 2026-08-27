{{--
    Reusable dashboard widget card.

    <x-widget-card title="Pending Orders" :href="route('admin.orders.index')"
                   value="3" subtitle="awaiting delivery" tone="warning">
        ...optional body...
    </x-widget-card>
--}}
@props([
    'title',
    'href' => null,
    'value' => null,
    'subtitle' => null,
    'tone' => 'default',   // default | success | warning | danger
    'linkText' => null,
])

@php
    $toneColor = [
        'success' => '#2fb344',
        'warning' => '#f59f00',
        'danger' => '#d63939',
        'default' => 'var(--on-surface, #202124)',
    ][$tone] ?? 'var(--on-surface, #202124)';
@endphp

<div class="card h-100">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h3 class="card-title mb-0" style="font-size: 0.938rem; font-weight: 500;">{{ $title }}</h3>
            @if($href)
                <a href="{{ $href }}" class="small text-decoration-none">{{ $linkText ?? 'Open' }} &rarr;</a>
            @endif
        </div>

        @if($value !== null)
            <div style="font-size: 1.75rem; font-weight: 500; line-height: 1.1; color: {{ $toneColor }};">{{ $value }}</div>
        @endif

        @if($subtitle)
            <div class="text-muted small mt-1">{{ $subtitle }}</div>
        @endif

        @if(trim($slot) !== '')
            <div class="mt-3 flex-grow-1">{{ $slot }}</div>
        @endif
    </div>
</div>
