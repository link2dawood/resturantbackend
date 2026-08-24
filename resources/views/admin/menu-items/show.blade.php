@extends('layouts.tabler')

@section('title', $menuItem->name)

@section('content')
@php
    $sizes = \App\Http\Controllers\Admin\MenuItemController::SIZES;
    $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
@endphp

<div class="container-xl mt-4 mb-6" style="max-width: 960px;">

    {{-- Header --}}
    <div class="mb-2">
        <a href="{{ route('admin.menu-items.index', ['store_id' => $menuItem->store_id]) }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Menu &amp; Recipes
        </a>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-2 d-flex align-items-center gap-2" style="font-size: 1.6rem;">
                {{ $menuItem->name }}
                @unless($menuItem->is_active)<span class="badge bg-secondary">Inactive</span>@endunless
            </h1>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-blue-lt">{{ $menuItem->category ?: 'Uncategorized' }}</span>
                @if($menuItem->square_name)
                    <span class="badge bg-azure-lt"><i class="bi bi-upc-scan me-1"></i>Square: {{ $menuItem->square_name }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.menu-items.edit', $menuItem) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit item
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>@endif

    {{-- Recipe editor with size tabs --}}
    <div class="card shadow-sm">
        <div class="card-header pb-0 border-bottom-0">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-list-check text-primary"></i>
                <h3 class="card-title mb-0">Recipe by size</h3>
            </div>
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                @foreach($sizes as $i => $size)
                    @php $current = $recipesBySize[$size]; @endphp
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $size }}" role="tab">
                            <span class="text-capitalize fw-medium">{{ $size }}</span>
                            @if($current)
                                <span class="badge bg-blue-lt ms-1">v{{ $current->version }}</span>
                            @else
                                <span class="badge bg-secondary-lt ms-1">empty</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                @foreach($sizes as $i => $size)
                    @php $current = $recipesBySize[$size]; $history = $historyBySize[$size]; @endphp
                    <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="tab-{{ $size }}" role="tabpanel">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-0 text-capitalize">{{ $size }} portions</h4>
                                <div class="text-muted small">
                                    @if($current)
                                        Current version <strong>v{{ $current->version }}</strong> · saved {{ $current->created_at?->format('M j, Y') }}
                                    @else
                                        No recipe saved yet — add ingredients below.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.menu-items.recipe.update', [$menuItem, $size]) }}">
                            @csrf @method('PUT')

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-2">
                                    <thead>
                                        <tr class="text-muted small text-uppercase" style="letter-spacing:.03em;">
                                            <th style="width:46%;">Ingredient</th>
                                            <th style="width:22%;" class="text-end">Quantity</th>
                                            <th style="width:22%;">Unit</th>
                                            <th style="width:10%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="rows-{{ $size }}"></tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('{{ $size }}')">
                                <i class="bi bi-plus-lg me-1"></i>Add ingredient
                            </button>

                            <hr class="my-4">

                            <div class="row g-3 align-items-end">
                                <div class="col-md">
                                    <label class="form-label small text-muted mb-1">Version notes <span class="text-muted">(optional)</span></label>
                                    <input type="text" name="notes" class="form-control" placeholder="e.g. reduced steak to 3 oz">
                                </div>
                                <div class="col-md-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-check-lg me-1"></i>Save new version
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Version history --}}
                        @if($history->count())
                            <div class="mt-4 pt-3 border-top">
                                <a class="text-decoration-none d-inline-flex align-items-center text-muted" data-bs-toggle="collapse" href="#history-{{ $size }}">
                                    <i class="bi bi-clock-history me-1"></i>Version history ({{ $history->count() }})
                                    <i class="bi bi-chevron-down ms-1 small"></i>
                                </a>
                                <div class="collapse mt-3" id="history-{{ $size }}">
                                    <div class="list-group list-group-flush">
                                        @foreach($history as $v)
                                            <div class="list-group-item px-0 py-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div>
                                                        <strong>v{{ $v->version }}</strong>
                                                        @if($v->is_current)<span class="badge bg-green-lt ms-1">current</span>@endif
                                                        @if($v->notes)<span class="text-muted small ms-2">{{ $v->notes }}</span>@endif
                                                    </div>
                                                    <span class="text-muted small">{{ $v->created_at?->format('M j, Y g:ia') }}</span>
                                                </div>
                                                <div>
                                                    @foreach($v->ingredients as $ing)
                                                        <span class="badge bg-light text-dark border fw-normal me-1 mb-1" style="font-size:.8rem;">
                                                            {{ $ing->inventoryItem->name ?? '—' }}
                                                            <span class="text-primary">{{ $qty($ing->quantity_base) }} {{ $ing->inventoryItem->base_unit ?? '' }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>Each save creates a new version and keeps the previous one in history. Quantities are stored in each item's base unit.
    </p>
</div>

@php
    $itemsData = $inventoryItems->map(fn ($i) => [
        'id' => $i->id, 'name' => $i->name, 'base_unit' => $i->base_unit, 'purchase_unit' => $i->purchase_unit,
    ])->values();
    $existingData = [];
    foreach ($sizes as $s) {
        $rec = $recipesBySize[$s];
        $existingData[$s] = $rec ? $rec->ingredients->map(fn ($ing) => [
            'item_id' => $ing->inventory_item_id,
            'quantity' => (float) ($ing->entered_quantity ?? $ing->quantity_base),
            'unit' => $ing->entered_unit ?: ($ing->inventoryItem->base_unit ?? ''),
        ])->values() : [];
    }
@endphp
<script>
const INVENTORY_ITEMS = @json($itemsData);
const EXISTING = @json($existingData);

function unitOptions(item, selected) {
  if (!item) return '';
  const units = [item.base_unit];
  if (item.purchase_unit && item.purchase_unit !== item.base_unit) units.push(item.purchase_unit);
  return units.map(u => `<option value="${u}" ${u === selected ? 'selected' : ''}>${u}</option>`).join('');
}

function buildRow(prefill) {
  prefill = prefill || {};
  const tr = document.createElement('tr');
  const opts = INVENTORY_ITEMS.map(i => `<option value="${i.id}" ${i.id === prefill.item_id ? 'selected' : ''}>${i.name}</option>`).join('');
  const item = INVENTORY_ITEMS.find(i => i.id === prefill.item_id);
  tr.innerHTML =
    `<td><select name="ingredient_item_id[]" class="form-select" onchange="refreshUnits(this)"><option value="">Select an ingredient…</option>${opts}</select></td>` +
    `<td><input type="number" step="0.0001" min="0" name="quantity[]" class="form-control text-end" value="${prefill.quantity ?? ''}" placeholder="0"></td>` +
    `<td><select name="unit[]" class="form-select">${unitOptions(item, prefill.unit)}</select></td>` +
    `<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" title="Remove" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
  return tr;
}

function refreshUnits(select) {
  const item = INVENTORY_ITEMS.find(i => i.id == select.value);
  const unitSel = select.closest('tr').querySelector('select[name="unit[]"]');
  unitSel.innerHTML = unitOptions(item, item ? item.base_unit : '');
}

function addRow(size, prefill) {
  document.getElementById('rows-' + size).appendChild(buildRow(prefill));
}

document.addEventListener('DOMContentLoaded', function () {
  Object.keys(EXISTING).forEach(function (size) {
    const rows = EXISTING[size] || [];
    if (rows.length) { rows.forEach(r => addRow(size, r)); }
    else { addRow(size); }
  });
});
</script>
@endsection
