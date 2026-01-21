<?php

namespace App\Http\Controllers;

use App\Models\RevenueIncomeType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RevenueIncomeTypeController extends Controller
{
    public function index()
    {
        $revenueIncomeTypes = RevenueIncomeType::orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('revenue-income-types.index', compact('revenueIncomeTypes'));
    }

    public function create()
    {
        return view('revenue-income-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:revenue_income_types,name',
            'description' => 'nullable|string|max:500',
            'category' => 'required|in:cash,card,check,online,crypto',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        RevenueIncomeType::create($validated);

        return redirect()->route('revenue-income-types.index')
            ->with('success', 'Revenue income type created successfully.');
    }

    public function show(RevenueIncomeType $revenueIncomeType)
    {
        return view('revenue-income-types.show', compact('revenueIncomeType'));
    }

    public function edit(RevenueIncomeType $revenueIncomeType)
    {
        return view('revenue-income-types.edit', compact('revenueIncomeType'));
    }

    public function update(Request $request, RevenueIncomeType $revenueIncomeType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('revenue_income_types')->ignore($revenueIncomeType->id)],
            'description' => 'nullable|string|max:500',
            'category' => 'required|in:cash,card,check,online,crypto',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        $revenueIncomeType->update($validated);

        return redirect()->route('revenue-income-types.index')
            ->with('success', 'Revenue income type updated successfully.');
    }

    public function destroy(RevenueIncomeType $revenueIncomeType)
    {
        $revenueIncomeType->delete();

        return redirect()->route('revenue-income-types.index')
            ->with('success', 'Revenue income type deleted successfully.');
    }
}
