<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Http\Requests\UpdateChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    private const ACCOUNT_TYPES = ['Assets', 'Liability', 'Equity', 'Taxes', 'Revenue', 'COGS', 'Expense', 'Adjustments'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = ChartOfAccount::with([
            'stores:id,store_info',
            'parent:id,account_name,account_code',
        ]);

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('store_id')) {
            $storeId = (int) $request->input('store_id');
            $query->where(function ($q) use ($storeId) {
                $q->whereDoesntHave('stores')
                    ->orWhereHas('stores', function ($storeQuery) use ($storeId) {
                        $storeQuery->where('stores.id', $storeId);
                    });
            });
        }

        // Sort by numeric account code so the list matches the COA code ranges (1000, 1010, 1100, ...)
        // account_code is stored as string, so cast to unsigned for correct ordering.
        $coas = $query
            ->orderByRaw('CAST(account_code AS UNSIGNED) ASC')
            ->orderBy('account_name')
            ->paginate(25)
            ->onEachSide(1)
            ->withQueryString();

        $stores = Store::orderBy('store_info')->get(['id', 'store_info']);

        $accountTypes = self::ACCOUNT_TYPES;

        return view('admin.coa.index', compact('coas', 'stores', 'accountTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $stores = Store::orderBy('store_info')->get(['id', 'store_info']);
        $parentAccounts = ChartOfAccount::orderBy('account_name')->get(['id', 'account_name', 'account_code']);
        $accountTypes = self::ACCOUNT_TYPES;

        return view('admin.coa.create', compact('stores', 'parentAccounts', 'accountTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $chartOfAccount = ChartOfAccount::create([
            'account_code' => $data['account_code'],
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'],
            'parent_account_id' => $data['parent_account_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_system_account' => false,
            'created_by' => auth()->id(),
        ]);

        $this->syncStoreAssignments($chartOfAccount, $data);

        return redirect()
            ->route('coa.index')
            ->with('success', 'Chart of Account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load([
            'stores:id,store_info',
            'parent:id,account_name,account_code',
            'children:id,account_code,account_name,account_type,is_active,parent_account_id',
            'creator:id,name',
        ]);

        return view('admin.coa.show', compact('chartOfAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load('stores:id,store_info');

        $stores = Store::orderBy('store_info')->get(['id', 'store_info']);
        $parentAccounts = ChartOfAccount::where('id', '!=', $chartOfAccount->id)
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_code']);
        $accountTypes = self::ACCOUNT_TYPES;
        $assignedStoreIds = $chartOfAccount->stores->pluck('id')->all();

        return view('admin.coa.edit', compact(
            'chartOfAccount',
            'stores',
            'parentAccounts',
            'accountTypes',
            'assignedStoreIds'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $data = $request->validated();

        $chartOfAccount->update([
            'account_code' => $data['account_code'],
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'],
            'parent_account_id' => $data['parent_account_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $this->syncStoreAssignments($chartOfAccount, $data);

        return redirect()
            ->route('coa.edit', $chartOfAccount)
            ->with('success', 'Chart of Account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $chartOfAccount->delete();

        return redirect()
            ->route('coa.index')
            ->with('success', 'Chart of Account deleted successfully.');
    }

    /**
     * Sync store assignments on the pivot table.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncStoreAssignments(ChartOfAccount $chartOfAccount, array $data): void
    {
        $chartOfAccount->stores()->sync([]);

        $isGlobal = $data['is_global'] ?? false;
        $storeIds = $data['store_ids'] ?? [];

        if (! $isGlobal && ! empty($storeIds)) {
            $syncData = collect($storeIds)
                ->unique()
                ->mapWithKeys(fn ($storeId) => [(int) $storeId => ['is_global' => false]])
                ->toArray();

            $chartOfAccount->stores()->sync($syncData);
        }
    }
}

