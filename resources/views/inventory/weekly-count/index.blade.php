@extends('layouts.tabler')

@section('title', 'Weekly Inventory Count')

@php
    $trim = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    // Counts are entered in the unit the item is ORDERED in, so the manager
    // counts 7 boxes rather than 371 portions. Stored in base units.
    $inOrderUnits = fn ($base, $item) => \App\Http\Controllers\WeeklyCountController::toPurchaseUnits(
        $base === null ? null : (float) $base, $item
    );
@endphp

@push('styles')
<style>
    /* Mobile-first: the manager does this on a phone, standing in a walk-in. */
    .count-input {
        font-size: 1.35rem;
        font-weight: 600;
        text-align: right;
        min-height: 56px;          /* comfortably above the 44px touch target floor */
    }
    .count-row { padding: 0.85rem 0; border-bottom: 1px solid #eceef1; }
    .count-row:last-child { border-bottom: 0; }
    .count-row.is-counted { background: #f2fbf5; }
    .prev-week { font-variant-numeric: tabular-nums; }
    .sticky-save {
        position: sticky; bottom: 0; z-index: 1030;
        background: #fff; border-top: 1px solid #e0e0e0;
        padding: 0.75rem 1rem; margin: 0 -0.75rem;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
    }
    .accordion-button { font-weight: 500; }
    .accordion-button:not(.collapsed) { background: #eef4ff; }
    .note-toggle { font-size: 0.8rem; }
    @media (min-width: 768px) {
        .sticky-save { margin: 0; border-radius: 0 0 12px 12px; }
    }
</style>
@endpush

@section('content')
<div class="container-xl mt-3 mb-5">

    <div class="mb-3">
        <h1 class="mb-1" style="font-size: 1.5rem;">Weekly Inventory Count</h1>
        <div class="text-muted">
            {{ $store->store_info }} &middot;
            week of <strong>{{ $week->format('M j') }} – {{ $weekEnd->format('M j, Y') }}</strong>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    {{-- Week navigation + store picker --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="{{ $previousWeekUrl }}" class="btn btn-outline-secondary btn-sm">&larr; Previous week</a>
        @if($nextWeekUrl)
            <a href="{{ $nextWeekUrl }}" class="btn btn-outline-secondary btn-sm">Next week &rarr;</a>
        @else
            <button class="btn btn-outline-secondary btn-sm" disabled title="You cannot count a week that has not started">Next week &rarr;</button>
        @endif

        <div class="ms-auto d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                @if($stores->isNotEmpty())
                    <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="group_by" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="category" @selected($groupBy === 'category')>Group by category</option>
                    <option value="vendor" @selected($groupBy === 'vendor')>Group by vendor (order guide)</option>
                </select>
            </form>
        </div>
    </div>

    @if($isFutureWeek)
        <div class="alert alert-warning">
            <strong>That week has not started yet.</strong> Counts can only be entered for the current week or a past one.
        </div>
    @elseif($submitted)
        <div class="alert alert-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><strong>This week is submitted and locked.</strong> The figures below are read-only.</span>
            @if($canUnlock)
            <form method="POST" action="{{ route('inventory.weekly-count.unlock') }}"
                  onsubmit="return confirm('Unlock this week so it can be edited again?');">
                @csrf
                <input type="hidden" name="store_id" value="{{ $store->id }}">
                <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                <button class="btn btn-sm btn-outline-danger">Unlock week</button>
            </form>
            @endif
        </div>
    @endif

    {{-- Progress --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong id="progressLabel">{{ $countedItems }} of {{ $totalItems }} items counted</strong>
                <span class="text-muted small" id="saveStatus"></span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" id="progressBar" role="progressbar"
                     style="width: {{ $totalItems > 0 ? round($countedItems / $totalItems * 100) : 0 }}%"
                     aria-valuenow="{{ $countedItems }}" aria-valuemin="0" aria-valuemax="{{ $totalItems }}"></div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.weekly-count.submit') }}" id="countForm">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week" value="{{ $week->toDateString() }}">

        <div class="accordion" id="countAccordion">
            @forelse($groups as $groupName => $groupRows)
            @php
                $slug = Str::slug($groupName ?: 'group').'-'.$loop->index;
                $groupCounted = $groupRows->filter(fn ($r) => $r->counted_at !== null)->count();
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#group-{{ $slug }}"
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                        <span class="flex-grow-1">{{ $groupName }}</span>
                        <span class="badge {{ $groupCounted === $groupRows->count() ? 'bg-success' : 'bg-secondary' }} me-2"
                              data-group-badge="{{ $slug }}" data-group-total="{{ $groupRows->count() }}">
                            {{ $groupCounted }}/{{ $groupRows->count() }}
                        </span>
                    </button>
                </h2>
                <div id="group-{{ $slug }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                     data-bs-parent="#countAccordion">
                    <div class="accordion-body py-0">
                        @foreach($groupRows as $row)
                        @php
                            $item = $row->inventoryItem;
                            $prev = $previous[$item->id] ?? null;
                        @endphp
                        <div class="count-row {{ $row->counted_at ? 'is-counted' : '' }}"
                             data-row="{{ $row->id }}" data-group="{{ $slug }}">
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-md-6">
                                    <div style="font-weight: 600; font-size: 1.05rem;">{{ $item->name }}</div>
                                    <div class="text-muted small">
                                        1 {{ $item->purchase_unit }} = {{ $trim($item->units_per_purchase) }} {{ $item->base_unit }}
                                        @if($item->portion_size)
                                            &middot; {{ $trim($item->portion_size) }} {{ $item->portion_unit }} portions
                                        @endif
                                        @if($groupBy === 'category' && $item->preferredVendor)
                                            &middot; {{ $item->preferredVendor->vendor_name }}
                                        @endif
                                    </div>
                                </div>

                                <div class="col-5 col-md-3 text-md-end">
                                    <div class="text-muted small prev-week">
                                        Last week:
                                        <strong>{{ $prev !== null ? $trim($inOrderUnits($prev, $item)) : '—' }}</strong>
                                        @if($prev !== null)<span class="text-muted">{{ $item->purchase_unit }}</span>@endif
                                    </div>
                                </div>

                                <div class="col-7 col-md-3">
                                    <div class="input-group">
                                        <input type="number" inputmode="decimal" step="0.01" min="0"
                                               class="form-control count-input"
                                               name="counts[{{ $row->id }}]"
                                               data-row-input="{{ $row->id }}"
                                               value="{{ $row->counted_at ? $trim($inOrderUnits($row->starting_stock, $item)) : '' }}"
                                               placeholder="0"
                                               aria-label="How many {{ $item->purchase_unit }} of {{ $item->name }} are on hand"
                                               @disabled(! $editable)>
                                        <span class="input-group-text">{{ $item->purchase_unit }}</span>
                                    </div>
                                    @if($editable)
                                    <button type="button" class="btn btn-link btn-sm note-toggle p-0 mt-1"
                                            onclick="toggleNote({{ $row->id }})">
                                        {{ $row->notes ? 'Edit note' : 'Add note' }}
                                    </button>
                                    @elseif($row->notes)
                                        <div class="small text-muted mt-1">{{ $row->notes }}</div>
                                    @endif
                                </div>

                                <div class="col-12 {{ $row->notes ? '' : 'd-none' }}" data-note-wrap="{{ $row->id }}">
                                    <input type="text" class="form-control form-control-sm mt-1"
                                           name="notes[{{ $row->id }}]" maxlength="500"
                                           value="{{ $row->notes }}"
                                           placeholder="Note, e.g. 2 boxes damaged and not counted"
                                           @disabled(! $editable)>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="alert alert-info">
                No active inventory items for this store yet. Add them under Inventory &rarr; Items first.
            </div>
            @endforelse
        </div>

        @if($editable && $totalItems > 0)
        <div class="sticky-save mt-3">
            @unless($canSeeSuggestions)
                <div class="text-center text-muted small mb-2">
                    Submitting sends your counts to the owner, who places the orders.
                </div>
            @endunless
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-outline-primary flex-fill" onclick="saveDraft(true)">
                    Save draft
                </button>
                <button type="button" class="btn btn-success flex-fill" data-bs-toggle="modal" data-bs-target="#submitModal">
                    Submit week
                </button>
            </div>
            <div class="text-center text-muted small mt-1">Draft saves automatically every 30 seconds.</div>
        </div>
        @endif
    </form>
</div>

<div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit this week?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Submitting locks the week of <strong>{{ $week->format('M j, Y') }}</strong>. After that only an admin can reopen it.</p>
                <p class="mb-0" id="submitSummary"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep editing</button>
                <button type="button" class="btn btn-success" onclick="doSubmit()">Submit and lock</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const EDITABLE = @json($editable);
const AUTOSAVE_URL = @json(route('inventory.weekly-count.autosave'));
const STORE_ID = {{ $store->id }};
const WEEK = @json($week->toDateString());
const TOTAL_ITEMS = {{ $totalItems }};

let dirty = false;
let submitting = false;
let autosaveTimer = null;

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function toggleNote(rowId) {
    const wrap = document.querySelector(`[data-note-wrap="${rowId}"]`);
    if (wrap) wrap.classList.toggle('d-none');
}

function collect() {
    const counts = {};
    const notes = {};

    document.querySelectorAll('[data-row-input]').forEach(input => {
        counts[input.dataset.rowInput] = input.value === '' ? null : input.value;
    });
    document.querySelectorAll('[name^="notes["]').forEach(input => {
        const id = input.name.replace('notes[', '').replace(']', '');
        notes[id] = input.value;
    });

    return { counts, notes };
}

function setStatus(text, isError) {
    const el = document.getElementById('saveStatus');
    el.textContent = text;
    el.className = 'small ' + (isError ? 'text-danger' : 'text-muted');
}

function refreshProgress(counted) {
    const total = TOTAL_ITEMS;
    document.getElementById('progressLabel').textContent = `${counted} of ${total} items counted`;
    document.getElementById('progressBar').style.width = total > 0 ? `${Math.round(counted / total * 100)}%` : '0%';
}

// Per-group badge and row shading update locally, so the page reflects work
// immediately rather than only after a save round-trip.
function refreshLocalState() {
    let counted = 0;
    const perGroup = {};

    document.querySelectorAll('.count-row').forEach(row => {
        const input = row.querySelector('[data-row-input]');
        const filled = input && input.value !== '';
        row.classList.toggle('is-counted', filled);

        const group = row.dataset.group;
        perGroup[group] = perGroup[group] || 0;
        if (filled) { counted++; perGroup[group]++; }
    });

    Object.entries(perGroup).forEach(([group, n]) => {
        const badge = document.querySelector(`[data-group-badge="${group}"]`);
        if (!badge) return;
        const total = parseInt(badge.dataset.groupTotal, 10);
        badge.textContent = `${n}/${total}`;
        badge.className = `badge ${n === total ? 'bg-success' : 'bg-secondary'} me-2`;
    });

    refreshProgress(counted);
    return counted;
}

function saveDraft(manual) {
    if (!EDITABLE) return Promise.resolve();
    if (!dirty && !manual) return Promise.resolve();

    setStatus('Saving...');
    const payload = collect();

    return fetch(AUTOSAVE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf()
        },
        credentials: 'same-origin',
        body: JSON.stringify({ store_id: STORE_ID, week: WEEK, ...payload })
    })
        .then(response => response.json().then(data => ({ status: response.status, data })))
        .then(({ status, data }) => {
            if (status !== 200) {
                setStatus(data.error || 'Could not save', true);
                return;
            }
            dirty = false;
            refreshProgress(data.counted_items);
            setStatus(`Draft saved at ${data.saved_at}`);
        })
        .catch(() => setStatus('Save failed, your entries are still on screen', true));
}

if (EDITABLE) {
    document.querySelectorAll('[data-row-input], [name^="notes["]').forEach(input => {
        input.addEventListener('input', () => {
            dirty = true;
            refreshLocalState();
            setStatus('Unsaved changes');
        });
    });

    autosaveTimer = setInterval(() => saveDraft(false), 30000);

    // Warn before losing work. Submitting is not "losing" anything.
    window.addEventListener('beforeunload', function (event) {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });

    // A phone backgrounding the tab is the most likely way work is lost.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && dirty) saveDraft(false);
    });

    const submitModal = document.getElementById('submitModal');
    submitModal.addEventListener('show.bs.modal', function () {
        const counted = refreshLocalState();
        const missing = TOTAL_ITEMS - counted;
        document.getElementById('submitSummary').textContent = missing > 0
            ? `${counted} of ${TOTAL_ITEMS} items are counted. ${missing} will be submitted uncounted.`
            : `All ${TOTAL_ITEMS} items are counted.`;
    });
}

function doSubmit() {
    submitting = true;
    dirty = false;
    document.getElementById('countForm').submit();
}
</script>
@endpush
