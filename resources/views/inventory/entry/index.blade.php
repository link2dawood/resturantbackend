@extends('layouts.tabler')

@section('title', 'Inventory Count')

@section('content')
<div class="container-xl mt-3 mb-6" style="max-width: 720px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0" style="font-size: 1.5rem;">Weekly Inventory Count</h1>
            <div class="text-muted">{{ $store->store_info ?? 'Store' }} · week of {{ $week->format('M j, Y') }}</div>
        </div>
        @if($stores->isNotEmpty())
            <form method="GET" action="{{ route('inventory.entry.index') }}">
                <select name="store_id" class="form-select form-select-lg" onchange="this.form.submit()" style="min-width: 200px;">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($locked)
        <div class="alert alert-warning">
            <strong>Closed.</strong> This week's count passed the Monday cutoff and can no longer be edited.
        </div>
    @endif

    @if($grouped->isEmpty())
        <div class="card"><div class="card-body text-center text-muted py-5">
            No inventory items for this store yet. An admin can add them.
        </div></div>
    @else
        <form method="POST" action="{{ route('inventory.entry.submit') }}" id="inventoryForm">
            @csrf
            <input type="hidden" name="store_id" value="{{ $store->id }}">

            @foreach($grouped as $category => $rows)
                <div class="card mb-3">
                    <div class="card-header py-2"><h3 class="card-title mb-0 text-capitalize">{{ $category ?: 'Uncategorized' }}</h3></div>
                    <div class="card-body p-2">
                        @foreach($rows as $row)
                            <label class="d-flex align-items-center justify-content-between gap-3 py-2 border-bottom"
                                   style="min-height: 56px;" for="count-{{ $row->id }}">
                                <span class="flex-fill">
                                    <span style="font-size: 1.05rem;">{{ $row->inventoryItem->name }}</span>
                                    <span class="d-block text-muted small">{{ $row->inventoryItem->base_unit }}</span>
                                </span>
                                <input type="number" step="0.0001" min="0" inputmode="decimal"
                                       id="count-{{ $row->id }}"
                                       name="counts[{{ $row->id }}]"
                                       value="{{ old('counts.'.$row->id, (float) $row->starting_stock) }}"
                                       class="form-control form-control-lg text-end"
                                       style="max-width: 130px; font-size: 1.25rem; height: 52px;"
                                       @disabled($locked)>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @unless($locked)
                {{-- Sticky action bar (thumb-friendly on phones) --}}
                <div class="position-sticky bottom-0 bg-body py-2 border-top d-flex gap-2" style="z-index: 10;">
                    <button type="submit" formaction="{{ route('inventory.entry.draft') }}"
                            class="btn btn-lg btn-outline-secondary flex-fill" style="height: 52px;">
                        Save draft
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary flex-fill" style="height: 52px;"
                            onclick="return confirm('Submit this week\'s count?');">
                        Submit count
                    </button>
                </div>
            @endunless
        </form>
    @endif
</div>
@endsection
