@extends('layouts.tabler')

@section('title', $menuItem->name)

@section('content')
@php $sizes = \App\Http\Controllers\Admin\MenuItemController::SIZES; @endphp
<div class="container-xl mt-4" style="max-width: 900px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">{{ $menuItem->name }}</h1>
            <div class="text-muted">{{ $menuItem->category ?: 'Uncategorized' }}
                @if($menuItem->square_name) · Square: {{ $menuItem->square_name }}@endif</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.menu-items.edit', $menuItem) }}" class="btn btn-outline-secondary">Edit item</a>
            <a href="{{ route('admin.menu-items.index', ['store_id' => $menuItem->store_id]) }}" class="btn btn-link">Back</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @foreach($sizes as $size)
        @php $current = $recipesBySize[$size]; $history = $historyBySize[$size]; @endphp
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0 text-capitalize">{{ $size }} recipe
                    @if($current)<span class="badge bg-blue-lt ms-2">v{{ $current->version }}</span>@else<span class="badge bg-secondary ms-2">none</span>@endif
                </h3>
                @if($history->count())
                    <a class="small" data-bs-toggle="collapse" href="#history-{{ $size }}">History ({{ $history->count() }})</a>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.menu-items.recipe.update', [$menuItem, $size]) }}">
                    @csrf @method('PUT')
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th style="width:45%;">Ingredient</th><th style="width:20%;">Quantity</th><th style="width:20%;">Unit</th><th></th></tr></thead>
                            <tbody id="rows-{{ $size }}"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addRow('{{ $size }}')">+ Add ingredient</button>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Notes (optional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="What changed in this version">
                    </div>
                    <button type="submit" class="btn btn-primary">Save {{ $size }} recipe (new version)</button>
                </form>

                @if($history->count())
                    <div class="collapse mt-3" id="history-{{ $size }}">
                        <hr>
                        <h4 class="text-muted">Version history</h4>
                        @foreach($history as $v)
                            <div class="mb-2 small">
                                <strong>v{{ $v->version }}</strong> @if($v->is_current)<span class="badge bg-green-lt">current</span>@endif
                                <span class="text-muted">· {{ $v->created_at?->format('M j, Y g:ia') }}@if($v->notes) · {{ $v->notes }}@endif</span>
                                <ul class="mb-1">
                                    @foreach($v->ingredients as $ing)
                                        <li>{{ $ing->inventoryItem->name ?? '—' }}: {{ rtrim(rtrim(number_format((float)$ing->quantity_base,4,'.',''),'0'),'.') }} {{ $ing->inventoryItem->base_unit ?? '' }}
                                            @if($ing->entered_unit && $ing->entered_unit !== ($ing->inventoryItem->base_unit ?? ''))<span class="text-muted">(entered {{ rtrim(rtrim(number_format((float)$ing->entered_quantity,4,'.',''),'0'),'.') }} {{ $ing->entered_unit }})</span>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach
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
  const opts = INVENTORY_ITEMS.map(i => `<option value="${i.id}" data-base="${i.base_unit}" data-purchase="${i.purchase_unit}" ${i.id === prefill.item_id ? 'selected' : ''}>${i.name}</option>`).join('');
  const item = INVENTORY_ITEMS.find(i => i.id === prefill.item_id);
  tr.innerHTML =
    `<td><select name="ingredient_item_id[]" class="form-select form-select-sm" onchange="refreshUnits(this)"><option value="">— select —</option>${opts}</select></td>` +
    `<td><input type="number" step="0.0001" min="0" name="quantity[]" class="form-control form-control-sm" value="${prefill.quantity ?? ''}"></td>` +
    `<td><select name="unit[]" class="form-select form-select-sm">${unitOptions(item, prefill.unit)}</select></td>` +
    `<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>`;
  return tr;
}

function refreshUnits(select) {
  const opt = select.selectedOptions[0];
  const item = opt ? INVENTORY_ITEMS.find(i => i.id == select.value) : null;
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
