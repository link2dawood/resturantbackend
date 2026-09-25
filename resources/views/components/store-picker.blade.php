@props([
    'stores',            // collection of stores the viewer may see
    'selected' => null,  // the current store, model or id
    'name' => 'store_id',
    'autoSubmit' => false,
    'includeAll' => false,
    'allLabel' => 'All stores',
])

@php
    // The same select appeared on ten screens with small differences in
    // whitespace and auto-submit. One component, so adding (say) a store code
    // to the label is a one-line change instead of a ten-file trawl.
    $selectedId = is_object($selected) ? $selected->id : $selected;
@endphp

@if($stores->isNotEmpty())
    <select name="{{ $name }}"
            {{ $attributes->merge(['class' => 'form-select']) }}
            @if($autoSubmit) onchange="this.form.submit()" @endif>
        @if($includeAll)
            <option value="">{{ $allLabel }}</option>
        @endif
        @foreach($stores as $store)
            {{-- A store with no name still has to be pickable. --}}
            <option value="{{ $store->id }}" @selected((int) $store->id === (int) $selectedId)>{{ $store->store_info ?? 'Store #'.$store->id }}</option>
        @endforeach
    </select>
@endif
