@props([
    'class' => '',
    'style' => '',
])

<tr style="border-bottom: 1px solid var(--google-grey-100, #f1f3f4); {{ $style }}" class="{{ $class }}">
    {{ $slot }}
</tr>

