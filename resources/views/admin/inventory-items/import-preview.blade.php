@extends('layouts.tabler')

@section('title', 'Bulk Import Preview')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <a href="{{ route('admin.inventory-items.import', ['store_id' => $store->id]) }}" class="text-muted text-decoration-none small">&larr; Upload a different file</a>
        <h1 class="mb-0 mt-2" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400;">Bulk Import Preview</h1>
        <p class="text-muted mb-0">Step 2 of 2 &middot; nothing has been saved yet</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">New items</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $newCount }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Already on file</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $duplicateCount }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Rows with errors</div>
                <div style="font-size: 1.5rem; font-weight: 500; {{ $errorCount > 0 ? 'color: #d63939;' : '' }}">{{ $errorCount }}</div>
            </div></div>
        </div>
    </div>

    <form action="{{ route('admin.inventory-items.import.commit') }}" method="POST">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">

        @if(! empty($missingCategories))
        <div class="alert alert-warning">
            <strong>New categories in this file:</strong> {{ implode(', ', $missingCategories) }}
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="create_missing_categories" value="1" id="createCategories" checked>
                <label class="form-check-label" for="createCategories">
                    Create {{ count($missingCategories) === 1 ? 'this category' : 'these categories' }} during the import
                </label>
            </div>
            <small class="text-muted">Unticked, rows using them are skipped.</small>
        </div>
        @endif

        @if($duplicateCount > 0)
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>{{ $duplicateCount }} {{ Str::plural('item', $duplicateCount) }} already exist in this store. Choose update or skip per row.</span>
            <span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAllDuplicates('update')">Update all</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAllDuplicates('skip')">Skip all</button>
            </span>
        </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                        <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                            <tr>
                                <th style="padding: 0.75rem;">Line</th>
                                <th style="padding: 0.75rem;">Name</th>
                                <th style="padding: 0.75rem;">Category</th>
                                <th style="padding: 0.75rem;">Unit</th>
                                <th style="padding: 0.75rem; text-align: right;">Portions / Unit</th>
                                <th style="padding: 0.75rem;">Portion</th>
                                <th style="padding: 0.75rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $row)
                            @php $hasErrors = ! empty($row['errors']); @endphp
                            <tr class="{{ $hasErrors ? 'table-danger' : ($row['is_duplicate'] ? 'table-warning' : '') }}">
                                <td style="padding: 0.75rem; vertical-align: middle;">{{ $row['line'] }}</td>
                                <td style="padding: 0.75rem; vertical-align: middle;">
                                    {{ $row['name'] }}
                                    @if($hasErrors)
                                        <ul class="mb-0 mt-1 ps-3 small text-danger">
                                            @foreach($row['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; vertical-align: middle;">
                                    {{ $row['category'] !== '' ? $row['category'] : '—' }}
                                    @if($row['category_missing'])
                                        <span class="badge bg-warning text-dark">new</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; vertical-align: middle;">{{ $row['unit'] !== '' ? $row['unit'] : '—' }}</td>
                                <td style="padding: 0.75rem; vertical-align: middle; text-align: right;">
                                    {{ $row['portions_per_unit'] !== null ? rtrim(rtrim(number_format($row['portions_per_unit'], 4, '.', ''), '0'), '.') : '—' }}
                                </td>
                                <td style="padding: 0.75rem; vertical-align: middle;">
                                    @if($row['portion_size'] !== null)
                                        {{ rtrim(rtrim(number_format($row['portion_size'], 2, '.', ''), '0'), '.') }} {{ $row['portion_unit'] }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; vertical-align: middle;">
                                    @if($hasErrors)
                                        <span class="badge bg-danger">Skipped</span>
                                        <input type="hidden" name="rows[{{ $i }}][action]" value="skip">
                                    @else
                                        <select class="form-select form-select-sm row-action" name="rows[{{ $i }}][action]"
                                                data-duplicate="{{ $row['is_duplicate'] ? '1' : '0' }}">
                                            @if($row['is_duplicate'])
                                                <option value="update" selected>Update existing</option>
                                                <option value="skip">Skip</option>
                                            @else
                                                <option value="create" selected>Create</option>
                                                <option value="skip">Skip</option>
                                            @endif
                                        </select>
                                    @endif
                                </td>
                            </tr>

                            <input type="hidden" name="rows[{{ $i }}][name]" value="{{ $row['name'] }}">
                            <input type="hidden" name="rows[{{ $i }}][category]" value="{{ $row['category'] }}">
                            <input type="hidden" name="rows[{{ $i }}][unit]" value="{{ $row['unit'] }}">
                            <input type="hidden" name="rows[{{ $i }}][portion_unit]" value="{{ $row['portion_unit'] }}">
                            <input type="hidden" name="rows[{{ $i }}][portions_per_unit]" value="{{ $row['portions_per_unit'] }}">
                            <input type="hidden" name="rows[{{ $i }}][portion_size]" value="{{ $row['portion_size'] }}">
                            <input type="hidden" name="rows[{{ $i }}][inventory_category_id]" value="{{ $row['inventory_category_id'] }}">
                            <input type="hidden" name="rows[{{ $i }}][existing_item_id]" value="{{ $row['existing_item_id'] }}">
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('admin.inventory-items.import', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Confirm import</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function setAllDuplicates(action) {
    document.querySelectorAll('.row-action[data-duplicate="1"]').forEach(select => {
        select.value = action;
    });
}
</script>
@endpush
