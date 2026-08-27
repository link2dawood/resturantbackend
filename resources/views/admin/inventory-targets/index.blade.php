@extends('layouts.tabler')

@section('title', 'Inventory Targets')

@php
    $trim = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Inventory Targets</h1>
            <p class="text-muted mb-0">
                {{ $store->store_info }} &middot; how much of each item this store keeps on hand.
                Levels are in the item's ordering unit, so "15" against Steak means 15 boxes.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventory-items.index', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">Inventory items</a>
            @if($otherStores->isNotEmpty())
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#copyModal">Copy from another store</button>
            @endif
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#defaultModal">Set default for all</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="alert alert-info py-2">
        <small>
            <strong>{{ $setCount }}</strong> of <strong>{{ $items->count() }}</strong> items have a target set.
            Items with no target are ordered from their usage projection alone.
            Leave both fields blank to remove a target.
        </small>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="inventory_category_id" class="form-select" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('inventory_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.inventory-targets.update', $store) }}">
        @csrf
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 200px;">Item</th>
                                <th>Category</th>
                                <th style="width: 190px;">Target Stock</th>
                                <th style="width: 190px;">Min Stock (reorder point)</th>
                                <th>In base units</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            @php $target = $targets->get($item->id); @endphp
                            <tr class="{{ $target ? '' : 'table-warning' }}">
                                <td>
                                    {{ $item->name }}
                                    @unless($target)<span class="badge bg-warning text-dark">no target</span>@endunless
                                    <span class="text-muted small d-block">
                                        1 {{ $item->purchase_unit }} = {{ $trim($item->units_per_purchase) }} {{ $item->base_unit }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $item->inventoryCategory?->name ?? $item->category ?? '—' }}</td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" class="form-control text-end target-input"
                                               name="targets[{{ $item->id }}][target_stock_level]"
                                               data-per-purchase="{{ (float) $item->units_per_purchase }}"
                                               data-base-unit="{{ $item->base_unit }}"
                                               data-item="{{ $item->id }}"
                                               value="{{ $trim($target?->target_stock_level) }}">
                                        <span class="input-group-text">{{ $item->purchase_unit }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" class="form-control text-end"
                                               name="targets[{{ $item->id }}][min_stock_level]"
                                               value="{{ $trim($target?->min_stock_level) }}">
                                        <span class="input-group-text">{{ $item->purchase_unit }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small" data-base-for="{{ $item->id }}">
                                    @if($target)
                                        {{ $trim($target->baseTargetFor($item)) }} {{ $item->base_unit }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No active items for this store.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($items->isNotEmpty())
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Save targets</button>
            </div>
            @endif
        </div>
    </form>
</div>

<div class="modal fade" id="defaultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.inventory-targets.bulk-default', $store) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set a default for every item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Applies the same level to every active item, in each item's own ordering unit.
                        Useful for bringing a new store up quickly, then adjusting the handful that differ.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Target stock <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="default_target" class="form-control" required placeholder="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Min stock (reorder point)</label>
                        <input type="number" step="0.01" min="0" name="default_min" class="form-control" placeholder="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Limit to category</label>
                        <select name="inventory_category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="overwrite_existing" value="1" id="defaultOverwrite">
                        <label class="form-check-label" for="defaultOverwrite">
                            Overwrite targets that are already set
                        </label>
                        <div><small class="text-muted">Unticked, only items with no target are touched.</small></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply default</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($otherStores->isNotEmpty())
<div class="modal fade" id="copyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.inventory-targets.copy-from', $store) }}">
            @csrf
            <input type="hidden" name="store_id" value="{{ $store->id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Copy targets from another store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Items are matched by name, since each store keeps its own item rows. Anything the other
                        store stocks that this one does not carry is reported and skipped.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Copy from <span class="text-danger">*</span></label>
                        <select name="source_store_id" class="form-select" required>
                            <option value="">Select a store</option>
                            @foreach($otherStores as $other)
                                <option value="{{ $other->id }}">{{ $other->store_info }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="overwrite_existing" value="1" id="copyOverwrite">
                        <label class="form-check-label" for="copyOverwrite">
                            Overwrite targets that are already set here
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Copy targets</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// Live base-unit readout, so "15 boxes" is visibly 795 portions before saving.
document.querySelectorAll('.target-input').forEach(input => {
    input.addEventListener('input', function () {
        const cell = document.querySelector(`[data-base-for="${this.dataset.item}"]`);
        const value = parseFloat(this.value);
        const perPurchase = parseFloat(this.dataset.perPurchase);

        if (!cell) return;

        if (isNaN(value) || isNaN(perPurchase)) {
            cell.textContent = '';
            return;
        }

        const total = value * perPurchase;
        cell.textContent = `${parseFloat(total.toFixed(4))} ${this.dataset.baseUnit}`;
    });
});
</script>
@endpush
