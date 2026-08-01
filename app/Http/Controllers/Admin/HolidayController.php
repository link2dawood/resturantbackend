<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.holidays.index', compact('holidays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:holidays,name',
        ]);

        Holiday::create([
            'name' => trim($data['name']),
            'sort_order' => (int) (Holiday::max('sort_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Holiday added.');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:holidays,name,'.$holiday->id,
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $holiday->update([
            'name' => trim($data['name']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', $holiday->sort_order),
        ]);

        return back()->with('success', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
