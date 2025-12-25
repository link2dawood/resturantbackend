@props([
    'viewHref' => null,
    'editHref' => null,
    'deleteAction' => null,
    'deleteConfirm' => 'Are you sure you want to delete this item? This action cannot be undone.',
    'deleteMethod' => 'DELETE',
    'size' => 'sm',
    'showView' => true,
    'showEdit' => true,
    'showDelete' => true,
])

<div class="d-flex gap-1 justify-content-center">
    @if($showView && $viewHref)
        <x-button-view href="{{ $viewHref }}" size="{{ $size }}" iconOnly="true" />
    @endif
    
    @if($showEdit && $editHref)
        <x-button-edit href="{{ $editHref }}" size="{{ $size }}" iconOnly="true" />
    @endif
    
    @if($showDelete && $deleteAction)
        <x-button-delete action="{{ $deleteAction }}" size="{{ $size }}" iconOnly="true" confirmMessage="{{ $deleteConfirm }}" method="{{ $deleteMethod }}" />
    @endif
</div>

