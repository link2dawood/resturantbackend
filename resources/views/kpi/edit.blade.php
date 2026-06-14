@extends('layouts.tabler')

@section('title', 'KPI Targets')

@section('content')
<div class="container-xl py-4" style="max-width: 900px;">
    <h2 class="mb-1">KPI Targets</h2>
    <p class="text-muted">
        Set the target cost percentages used by your dashboard rings. Leave a field blank to use the
        default ({{ $defaults['food'] }}% food, {{ $defaults['payroll'] }}% payroll, {{ $defaults['rent'] }}% rent).
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($stores->isEmpty())
        <div class="alert alert-info">You don't have any stores yet. Create a store first to set its KPI targets.</div>
    @else
        <form method="POST" action="{{ route('kpi.update') }}">
            @csrf
            @method('PUT')

            @foreach ($stores as $store)
                @php($t = $targets->get($store->id))
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title mb-0">{{ $store->store_info }}</h3></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Food Cost % target</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control"
                                           name="targets[{{ $store->id }}][food_cost_pct]"
                                           value="{{ old("targets.{$store->id}.food_cost_pct", $t->food_cost_pct ?? '') }}"
                                           placeholder="{{ $defaults['food'] }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payroll % target</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control"
                                           name="targets[{{ $store->id }}][payroll_pct]"
                                           value="{{ old("targets.{$store->id}.payroll_pct", $t->payroll_pct ?? '') }}"
                                           placeholder="{{ $defaults['payroll'] }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rent % target</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control"
                                           name="targets[{{ $store->id }}][rent_pct]"
                                           value="{{ old("targets.{$store->id}.rent_pct", $t->rent_pct ?? '') }}"
                                           placeholder="{{ $defaults['rent'] }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Save KPI targets</button>
        </form>
    @endif
</div>
@endsection
