@props([
    'align' => 'left', // 'left', 'center', 'right'
    'class' => '',
    'style' => '',
    'colspan' => null,
])

@php
    $alignStyle = match($align) {
        'center' => 'text-align: center;',
        'right' => 'text-align: right;',
        default => '',
    };
@endphp

<td style="padding: 1rem; vertical-align: middle; color: var(--google-grey-900, #202124); {{ $alignStyle }} {{ $style }}" 
    class="{{ $class }}"
    @if($colspan) colspan="{{ $colspan }}" @endif>
    {{ $slot }}
</td>

