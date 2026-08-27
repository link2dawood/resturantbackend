@extends('layouts.tabler')

@section('title', $menuItem->name)

@section('content')
@php
    $sizes = \App\Http\Controllers\Admin\MenuItemController::SIZES;
    $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
@endphp

<div class="container-xl mt-4 mb-5">

    {{-- Header --}}
    <div class="mb-2">
        <a href="{{ route('admin.menu-items.index', ['store_id' => $menuItem->store_id]) }}" class="text-muted text-decoration-none small">
            &larr; Menu &amp; Recipes
        </a>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-2">
                {{ $menuItem->name }}
                @unless($menuItem->is_active)<span class="badge bg-secondary align-middle">Inactive</span>@endunless
            </h1>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-blue-lt">{{ $menuItem->category ?: 'Uncategorized' }}</span>
                @if($menuItem->square_name)<span class="badge bg-azure-lt">Square: {{ $menuItem->square_name }}</span>@endif
            </div>
        </div>
        <a href="{{ route('admin.menu-items.edit', $menuItem) }}" class="btn btn-outline-secondary">Edit item</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Recipe by size (native Bootstrap accordion) --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Recipe by size</h3>
        </div>
        <div class="card-body">
            <div class="accordion" id="recipeAccordion">
                @foreach($sizes as $i => $size)
                    @php $current = $recipesBySize[$size]; $history = $historyBySize[$size]; @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $size }}">
                                <span class="text-capitalize fw-semibold">{{ $size }}</span>
                                @if($current)
                                    <span class="badge bg-blue-lt ms-2">v{{ $current->version }}</span>
                                @else
                                    <span class="badge bg-secondary ms-2">empty</span>
                                @endif
                            </button>
                        </h2>
                        <div id="collapse-{{ $size }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#recipeAccordion">
                            <div class="accordion-body">
                                <p class="text-muted small">
                                    @if($current)
                                        Current version <strong>v{{ $current->version }}</strong> &middot; saved {{ $current->created_at?->format('M j, Y') }}
                                    @else
                                        No recipe saved yet — add ingredients below.
                                    @endif
                                </p>

                                <form method="POST" action="{{ route('admin.menu-items.recipe.update', [$menuItem, $size]) }}">
                                    @csrf @method('PUT')

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-2">
                                            <thead>
                                                <tr>
                                                    <th class="w-50">Ingredient</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th>Unit</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="rows-{{ $size }}"></tbody>
                                        </table>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('{{ $size }}')">+ Add ingredient</button>

                                    <hr>

                                    <div class="row g-3 align-items-end">
                                        <div class="col-md">
                                            <label class="form-label">Version notes <span class="text-muted">(optional)</span></label>
                                            <input type="text" name="notes" class="form-control" placeholder="e.g. reduced steak to 3 oz">
                                        </div>
                                        <div class="col-md-auto">
                                            <button type="submit" class="btn btn-primary">Save new version</button>
                                        </div>
                                    </div>
                                </form>

                                @if($history->count())
                                    <div class="mt-4 pt-3 border-top">
                                        <a class="text-muted text-decoration-none" data-bs-toggle="collapse" href="#history-{{ $size }}">
                                            Version history ({{ $history->count() }})
                                        </a>
                                        <div class="collapse mt-3" id="history-{{ $size }}">
                                            <div class="list-group list-group-flush">
                                                @foreach($history as $v)
                                                    <div class="list-group-item px-0">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <div>
                                                                <strong>v{{ $v->version }}</strong>
                                                                @if($v->is_current)<span class="badge bg-green-lt ms-1">current</span>@endif
                                                                @if($v->notes)<span class="text-muted ms-2">{{ $v->notes }}</span>@endif
                                                            </div>
                                                            <span class="text-muted">{{ $v->created_at?->format('M j, Y g:ia') }}</span>
                                                        </div>
                                                        <div>
                                                            @foreach($v->ingredients as $ing)
                                                                <span class="badge bg-light text-dark border fw-normal me-1 mb-1">
                                                                    {{ $ing->inventoryItem->name ?? '—' }}:
                                                                    {{ $qty($ing->quantity_base) }} {{ $ing->inventoryItem->base_unit ?? '' }}
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
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">Each save creates a new version and keeps the previous one in history. Quantities are stored in each item's base unit.</p>
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
    `<td><input type="number" step="0.0001" min="0" name="quantity[]" class="form-control text-end" value="${prefill.quantity ?? ''}"></td>` +
    `<td><select name="unit[]" class="form-select">${unitOptions(item, prefill.unit)}</select></td>` +
    `<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>`;
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
