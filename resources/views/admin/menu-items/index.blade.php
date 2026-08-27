@extends('layouts.tabler')

@section('title', 'Menu Items & Recipes')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Menu Items &amp; Recipes</h1>
            <p class="text-muted mb-0">Define menu items and map each size to its ingredient portions.</p>
        </div>
        <div class="d-flex gap-2">
            @if($stores->isNotEmpty())
                <form method="GET">
                    <select name="store_id" class="form-select" onchange="this.form.submit()">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('admin.menu-items.import.form', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">Import CSV</a>
            <a href="{{ route('admin.menu-items.create', ['store_id' => $store->id]) }}" class="btn btn-primary">New menu item</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning">
            <strong>{{ count(session('import_errors')) }} row(s) skipped:</strong>
            <ul class="mb-0 mt-1">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Name</th><th>Category</th><th>Square name</th>
                        <th class="text-center">Recipe versions</th><th class="text-end">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse($menuItems as $mi)
                            <tr>
                                <td><a href="{{ route('admin.menu-items.show', $mi) }}" class="fw-bold text-decoration-none">{{ $mi->name }}</a>
                                    @unless($mi->is_active)<span class="badge bg-secondary ms-1">inactive</span>@endunless</td>
                                <td>{{ $mi->category ?: '—' }}</td>
                                <td class="text-muted">{{ $mi->square_name ?: '—' }}</td>
                                <td class="text-center">{{ $mi->recipes_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.menu-items.show', $mi) }}" class="btn btn-sm btn-outline-primary">Recipes</a>
                                    <a href="{{ route('admin.menu-items.edit', $mi) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('admin.menu-items.destroy', $mi) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $mi->name }} and its recipes?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No menu items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($menuItems->hasPages())<div class="card-footer">{{ $menuItems->appends(['store_id' => $store->id])->links() }}</div>@endif
    </div>
</div>
@endsection
