@props([
    // Validation errors ride along by default; a form that prints them beside
    // its fields passes :show-errors="false".
    'showErrors' => true,
    'dismissible' => false,
])

@php
    // One place for the things every screen says back to the user, so a change
    // to the wording or the markup lands on all of them at once.
    $flashes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];
@endphp

@foreach($flashes as $key => $class)
    @if(filled(session($key)))
        <div {{ $attributes->merge(['class' => 'alert '.$class.($dismissible ? ' alert-dismissible' : '')]) }} role="alert">
            {{ session($key) }}
            @if($dismissible)
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            @endif
        </div>
    @endif
@endforeach

@if($showErrors && $errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
