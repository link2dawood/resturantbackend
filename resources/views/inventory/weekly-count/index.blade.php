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
<link href="{{ asset('css/inventory-count.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-xl mt-3 mb-5">

    <div class="mb-3 count-page-head">
        <h1 class="mb-1 count-page-title" style="font-size: 1.35rem; font-weight: 600;">Weekly Inventory Count</h1>
        <div class="text-muted">
            {{ $store->store_info }} &middot;
            week of <strong>{{ $week->format('M j') }} – {{ $weekEnd->format('M j, Y') }}</strong>
        </div>
    </div>

    <x-flash />

    {{-- Week navigation + store picker --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 count-weeknav">
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
                    <x-store-picker :stores="$stores" :selected="$store" :auto-submit="true" class="form-select form-select-sm" />
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

    {{-- Toolbar: progress, search, and a filter down to what is still
         outstanding. With 130 items, finding one by scrolling is the slowest
         part of the job. --}}
    <div class="count-toolbar">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <strong id="progressLabel" class="me-auto">{{ $countedItems }} of {{ $totalItems }} items counted</strong>
            <span class="text-muted small" id="saveStatus"></span>
        </div>

        <div class="count-progress mb-2">
            <div class="count-progress__bar" id="progressBar"
                 style="width: {{ $totalItems > 0 ? round($countedItems / $totalItems * 100) : 0 }}%"
                 role="progressbar" aria-valuenow="{{ $countedItems }}" aria-valuemin="0" aria-valuemax="{{ $totalItems }}"></div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" id="itemSearch" class="form-control form-control-sm count-search"
                   placeholder="Search items" autocomplete="off" aria-label="Search the item list">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="onlyUncounted">
                <label class="form-check-label small" for="onlyUncounted">Left to count</label>
            </div>
            <button type="button" class="btn btn-sm btn-link text-decoration-none ms-auto" id="expandAll">Open all groups</button>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.weekly-count.submit') }}" id="countForm">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week" value="{{ $week->toDateString() }}">

        <div class="accordion count-wrap" id="countAccordion">
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
                        @php
                            $lastItemGroup = null;
                        @endphp
                        @foreach($groupRows as $row)
                        @php
                            $item = $row->inventoryItem;
                            $prev = $previous[$item->id] ?? null;
                            // Related items carry a shared label and are already
                            // sorted together, so the heading prints once at the
                            // top of the run.
                            $itemGroup = $item->item_group;
                            $showGroupHeading = filled($itemGroup) && $itemGroup !== ($lastItemGroup ?? null);
                            $lastItemGroup = $itemGroup;
                        @endphp
                        @if($showGroupHeading)
                            <div class="item-group-heading">{{ $itemGroup }}</div>
                        @endif
                        <div class="count-row {{ $row->counted_at ? 'is-counted' : '' }}"
                             data-row="{{ $row->id }}" data-group="{{ $slug }}"
                             data-name="{{ Str::lower($item->name) }}">
                            @php
                                // Whole units on the left, the partial on the right. For an item
                                // with a real pack size the partial is loose pieces; otherwise it
                                // is a quarter of a unit, picked from the preset list.
                                $split = \App\Services\Inventory\CountEntry::split($item, $row->counted_at ? (float) $row->starting_stock : null);
                                $inPieces = \App\Services\Inventory\CountEntry::countsInPieces($item);
                                $maxPartial = \App\Services\Inventory\CountEntry::maxPartial($item);
                                $wholeLabel = \App\Services\Inventory\CountEntry::unitLabel($item->purchase_unit, 'unit');
                                $partialLabel = $inPieces ? \App\Services\Inventory\CountEntry::unitLabel($item->base_unit) : 'partial';
                            @endphp

                            <div class="count-line">
                                <div class="count-name">
                                    <div class="count-name__title">{{ $item->name }}</div>
                                    <div class="count-name__meta">
                                        <span>1 {{ $item->purchase_unit }} = {{ $trim($item->units_per_purchase) }} {{ $item->base_unit }}</span>
                                        @if($groupBy === 'category' && $item->preferredVendor)
                                            <span class="count-name__dot">&middot;</span><span>{{ $item->preferredVendor->vendor_name }}</span>
                                        @endif
                                        <span class="count-name__dot">&middot;</span>
                                        <span class="prev-week">last week
                                            <strong>{{ $prev !== null ? $trim($inOrderUnits($prev, $item)) : '—' }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="count-fields">
                                    <div class="count-cell">
                                        <label class="count-cell__label" for="whole-{{ $row->id }}">{{ $wholeLabel }}</label>
                                        <input type="number" inputmode="numeric" step="1" min="0"
                                               id="whole-{{ $row->id }}"
                                               class="form-control count-input"
                                               name="whole[{{ $row->id }}]"
                                               data-row-whole="{{ $row->id }}"
                                               data-pack="{{ \App\Services\Inventory\CountEntry::packSize($item) }}"
                                               data-unit="{{ $item->purchase_unit }}"
                                               value="{{ $split['whole'] !== null ? $trim($split['whole']) : '' }}"
                                               placeholder="0"
                                               aria-label="How many whole {{ $wholeLabel }} of {{ $item->name }} are on hand"
                                               @disabled(! $editable)>
                                    </div>

                                    <div class="count-cell">
                                        <label class="count-cell__label" for="partial-{{ $row->id }}">
                                            {{ $partialLabel }}@if($inPieces)<span class="count-cell__max">/{{ $trim($maxPartial) }}</span>@endif
                                        </label>
                                        @if($inPieces)
                                            <input type="number" inputmode="numeric" step="1" min="0" max="{{ $trim($maxPartial) }}"
                                                   id="partial-{{ $row->id }}"
                                                   class="form-control count-input"
                                                   name="partial[{{ $row->id }}]"
                                                   data-row-partial="{{ $row->id }}"
                                                   data-max-partial="{{ $trim($maxPartial) }}"
                                                   value="{{ $split['partial'] ? $trim($split['partial']) : '' }}"
                                                   placeholder="0"
                                                   aria-label="Loose {{ $partialLabel }} of {{ $item->name }} outside a full {{ $item->purchase_unit }}"
                                                   @disabled(! $editable)>
                                        @else
                                            <select class="form-select count-input count-select"
                                                    id="partial-{{ $row->id }}"
                                                    name="partial[{{ $row->id }}]"
                                                    data-row-partial="{{ $row->id }}"
                                                    data-max-partial="0.75"
                                                    aria-label="Partial {{ $item->purchase_unit }} of {{ $item->name }}"
                                                    @disabled(! $editable)>
                                                <option value="">0</option>
                                                @foreach(\App\Services\Inventory\CountEntry::FRACTIONS as $fraction)
                                                    <option value="{{ $fraction }}"
                                                        @selected((float) ($split['partial'] ?? 0) === (float) $fraction)>{{ $fraction }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    {{-- The unit labels above differ per item, so they earn their
                                         place; "total" would just repeat 130 times. --}}
                                    <div class="count-cell count-cell--total">
                                        <div class="count-total" data-row-total="{{ $row->id }}">&mdash;</div>
                                    </div>

                                    @if($editable)
                                        <button type="button"
                                                class="count-note-btn {{ $row->notes ? 'has-note' : '' }}"
                                                data-note-btn="{{ $row->id }}"
                                                onclick="toggleNote({{ $row->id }})"
                                                title="{{ $row->notes ? 'Edit note' : 'Add a note' }}"
                                                aria-label="{{ $row->notes ? 'Edit note' : 'Add note' }} for {{ $item->name }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                                <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($editable)
                                <div class="count-note {{ $row->notes ? '' : 'd-none' }}" data-note-wrap="{{ $row->id }}">
                                    <input type="text" class="form-control form-control-sm"
                                           name="notes[{{ $row->id }}]" maxlength="500"
                                           value="{{ $row->notes }}"
                                           placeholder="Note, e.g. 2 boxes damaged and not counted">
                                </div>
                            @elseif($row->notes)
                                <div class="count-note"><div class="count-note__read">{{ $row->notes }}</div></div>
                            @endif
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
            <div class="count-empty-search" id="noSearchMatch">No item matches that search.</div>
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
    if (!wrap) return;
    wrap.classList.toggle('d-none');
    const input = wrap.querySelector('input');
    if (input && !wrap.classList.contains('d-none')) input.focus();
}

function collect() {
    const whole = {};
    const partial = {};
    const notes = {};

    document.querySelectorAll('[data-row-whole]').forEach(input => {
        whole[input.dataset.rowWhole] = input.value === '' ? null : input.value;
    });
    document.querySelectorAll('[data-row-partial]').forEach(input => {
        partial[input.dataset.rowPartial] = input.value === '' ? null : input.value;
    });
    document.querySelectorAll('[name^="notes["]').forEach(input => {
        const id = input.name.replace('notes[', '').replace(']', '');
        notes[id] = input.value;
    });

    return { whole, partial, notes };
}

// "2 boxes + 15 loose = 121 portions", under the boxes, so the counter can see
// the two entries add up to something sensible.
function refreshRowTotal(row) {
    const wholeInput = row.querySelector('[data-row-whole]');
    const partialInput = row.querySelector('[data-row-partial]');
    const out = row.querySelector('[data-row-total]');
    if (!wholeInput || !out) return false;

    const pack = parseFloat(wholeInput.dataset.pack || '1') || 1;
    const inPieces = pack > 1;
    const wholeVal = parseFloat(wholeInput.value || '0') || 0;
    const partialVal = parseFloat((partialInput && partialInput.value) || '0') || 0;
    const filled = wholeInput.value !== '' || (partialInput && partialInput.value !== '' && partialInput.value !== null);

    const max = partialInput ? parseFloat(partialInput.dataset.maxPartial || '0') : 0;
    const over = partialVal > max;

    if (partialInput) partialInput.classList.toggle('is-invalid', over);

    if (!filled) {
        out.textContent = '\u2014';
        out.className = 'count-total is-empty';
        return false;
    }

    if (over) {
        out.textContent = inPieces ? 'over ' + max : 'max \u00be';
        out.className = 'count-total is-error';
        return true;
    }

    const base = inPieces ? (wholeVal * pack) + partialVal : (wholeVal + partialVal) * pack;
    out.textContent = Math.round(base * 100) / 100;
    out.className = 'count-total';
    return true;
}

// Search and the "left to count" switch work across every group, and open a
// collapsed group when something inside it matches.
function applyFilters() {
    const term = (document.getElementById('itemSearch').value || '').trim().toLowerCase();
    const onlyLeft = document.getElementById('onlyUncounted').checked;
    let shown = 0;

    document.querySelectorAll('.count-row').forEach(row => {
        const name = row.dataset.name || '';
        const counted = row.classList.contains('is-counted') || rowHasEntry(row);
        const hide = (term && !name.includes(term)) || (onlyLeft && counted);
        row.classList.toggle('is-hidden', hide);
        if (!hide) shown++;
    });

    // A group with nothing left to show gets out of the way.
    document.querySelectorAll('#countAccordion .accordion-item').forEach(group => {
        const visible = group.querySelectorAll('.count-row:not(.is-hidden)').length;
        group.style.display = visible === 0 ? 'none' : '';

        if (visible > 0 && (term || onlyLeft)) {
            const panel = group.querySelector('.accordion-collapse');
            if (panel && !panel.classList.contains('show')) {
                panel.classList.add('show');
                const btn = group.querySelector('.accordion-button');
                if (btn) { btn.classList.remove('collapsed'); btn.setAttribute('aria-expanded', 'true'); }
            }
        }
    });

    document.getElementById('noSearchMatch').style.display = shown === 0 ? 'block' : 'none';
}

function rowHasEntry(row) {
    const w = row.querySelector('[data-row-whole]');
    const p = row.querySelector('[data-row-partial]');
    return (w && w.value !== '') || (p && p.value !== '' && p.value !== null);
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

// The toolbar works whether or not the week is still open, because reading a
// locked week is exactly when you want to search it.
(function wireToolbar() {
    const search = document.getElementById('itemSearch');
    const onlyLeft = document.getElementById('onlyUncounted');
    const expand = document.getElementById('expandAll');
    if (!search) return;

    search.addEventListener('input', applyFilters);
    onlyLeft.addEventListener('change', applyFilters);

    expand.addEventListener('click', () => {
        document.querySelectorAll('#countAccordion .accordion-collapse').forEach(panel => {
            panel.classList.add('show');
            // Bootstrap's parent link closes siblings; without it every group
            // can stay open, which is what "open all" means.
            panel.removeAttribute('data-bs-parent');
        });
        document.querySelectorAll('#countAccordion .accordion-button').forEach(btn => {
            btn.classList.remove('collapsed');
            btn.setAttribute('aria-expanded', 'true');
        });
    });
})();

if (EDITABLE) {
    document.querySelectorAll('[data-row-whole], [data-row-partial], [name^="notes["]').forEach(input => {
        ['input', 'change'].forEach(evt => input.addEventListener(evt, () => {
            dirty = true;
            refreshLocalState();
            setStatus('Unsaved changes');
        }));
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
