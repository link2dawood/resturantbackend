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
        $query->orderByRaw('CAST(account_code AS UNSIGNED) ASC')->orderBy('account_name');

        $stores = Store::orderBy('store_info')->get(['id', 'store_info']);
        $accountTypes = self::ACCOUNT_TYPES;

        // Searching returns a flat, paginated list so a match is found at any depth.
        // Otherwise the list is a collapsible tree: categories/standalones at the
        // top, their sub-accounts hidden until a parent is expanded.
        if ($request->filled('search')) {
            $coas = $query->paginate(50)->withQueryString();

            return view('admin.coa.index', compact('coas', 'stores', 'accountTypes') + ['tree' => null]);
        }

        $tree = $this->buildAccountTree($query->get());

        return view('admin.coa.index', compact('tree', 'stores', 'accountTypes') + ['coas' => null]);
    }

    /**
     * Turn a flat account collection into top-level nodes, each carrying its
     * descendants on ->childNodes and its ->depth (0 = type root). A node is
     * top-level when its parent isn't in the set (a type root, or an account
     * whose parent was filtered out).
     *
     * @param  \Illuminate\Support\Collection<int, ChartOfAccount>  $accounts
     * @return \Illuminate\Support\Collection<int, ChartOfAccount>
     */
    private function buildAccountTree(\Illuminate\Support\Collection $accounts): \Illuminate\Support\Collection
    {
        $byId = $accounts->keyBy('id');
        $childrenByParent = $accounts->groupBy('parent_account_id');

        $assignDepth = function (ChartOfAccount $node, int $depth) use (&$assignDepth, $childrenByParent) {
            $node->depth = $depth;
            $node->childNodes = $childrenByParent->get($node->id, collect())
                ->sortBy(fn ($c) => (int) $c->account_code)
                ->values();
            foreach ($node->childNodes as $child) {
                $assignDepth($child, $depth + 1);
            }
        };

        $roots = $accounts
            ->filter(fn ($a) => ! $a->parent_account_id || ! $byId->has($a->parent_account_id))
            ->sortBy(fn ($a) => (int) $a->account_code)
            ->values();

        foreach ($roots as $root) {
            $assignDepth($root, 0);
        }

        return $roots;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $user = auth()->user();
        // Only the user's own stores (admins see all). Prevents owners seeing
        // other tenants' stores.
        $stores = $user->accessibleStores()->orderBy('store_info')->get(['id', 'store_info']);
        // Auto-select the owner's store(s); admins choose explicitly.
        $defaultStoreIds = $user->isAdmin() ? [] : $stores->pluck('id')->all();
        $parentAccounts = $this->parentAccountOptions();
        $accountTypes = self::ACCOUNT_TYPES;

        // "Add sub-account" from the list deep-links here with ?parent=<id> so the
        // form opens with that parent's type + category already selected.
        $preselectParent = $request->filled('parent')
            ? ChartOfAccount::find((int) $request->input('parent'))
            : null;

        return view('admin.coa.create', compact('stores', 'parentAccounts', 'accountTypes', 'defaultStoreIds', 'preselectParent'));
    }

    /**
     * Every account, annotated for the Type → Category → Sub-category picker.
     *
     * The hierarchy comes from the STORED `parent_account_id` — never inferred
     * from the account number. That's what lets an admin put an account in any
     * category regardless of its code (previously an account numbered 63xx was
     * forced under "Delivery Service Fees" 6300 by arithmetic alone).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function parentAccountOptions(?int $excludeId = null): \Illuminate\Support\Collection
    {
        $all = ChartOfAccount::orderByRaw('CAST(account_code AS UNSIGNED) ASC')
            ->get(['id', 'account_name', 'account_code', 'account_type', 'parent_account_id']);

        // Real child counts, straight off the stored tree.
        $childCounts = $all->groupBy('parent_account_id')->map->count();

        return $all
            ->when($excludeId, fn ($c) => $c->where('id', '!=', $excludeId))
            ->map(function ($acct) use ($childCounts) {
                return (object) [
                    'id' => $acct->id,
                    'account_code' => $acct->account_code,
                    'account_name' => $acct->account_name,
                    'account_type' => $acct->account_type,
                    'parent_account_id' => $acct->parent_account_id,
                    // A code can only hold sub-accounts if it has a sub-code range
                    // (trailing-zero header). Detail codes like 6451 cannot.
                    'can_have_children' => ChartOfAccount::childCodeRangeForParent((string) $acct->account_code) !== null,
                    'children_count' => (int) ($childCounts[$acct->id] ?? 0),
                ];
            })
            ->values();
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

        return $this->withRollupWarning(
            redirect()->route('coa.index')->with('success', 'Chart of Account created successfully.'),
            $chartOfAccount
        );
    }

    /**
     * Attach a non-blocking rollup-total warning to a redirect, if applicable.
     */
    private function withRollupWarning(RedirectResponse $redirect, ChartOfAccount $account): RedirectResponse
    {
        $parentCode = $account->parent_account_id
            ? optional(ChartOfAccount::find($account->parent_account_id))->account_code
            : null;

        $warning = ChartOfAccount::rollupWarningFor((string) $account->account_code, $parentCode);

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    /**
     * JSON: the sub-accounts filed under an account (e.g. Online Merchant 6450 ->
     * DoorDash, GrubHub, Uber Eats), plus the allowed sub-code range. Powers the
     * create/edit form's live hierarchy hints.
     *
     * Children come from the stored tree, NOT the code range: the range version
     * listed every account whose number happened to fall in the block, so e.g.
     * insurance accounts numbered 63xx appeared under "Delivery Service Fees".
     */
    public function children(ChartOfAccount $chartOfAccount)
    {
        return response()->json([
            'parent' => $chartOfAccount->only(['id', 'account_code', 'account_name', 'account_type']),
            'child_range' => ChartOfAccount::childCodeRangeForParent((string) $chartOfAccount->account_code),
            'is_rollup_total' => $chartOfAccount->isRollupTotal(),
            'children' => $chartOfAccount->children()
                ->active()
                ->orderBy('account_code')
                ->get(['id', 'account_code', 'account_name', 'account_type']),
        ]);
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
        abort_unless($chartOfAccount->canBeManagedBy(auth()->user()), 403, 'You can only edit accounts you created.');

        $chartOfAccount->load('stores:id,store_info');

        // Only the user's own stores (admins see all).
        $stores = auth()->user()->accessibleStores()->orderBy('store_info')->get(['id', 'store_info']);
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

        return $this->withRollupWarning(
            redirect()->route('coa.edit', $chartOfAccount)->with('success', 'Chart of Account updated successfully.'),
            $chartOfAccount
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        abort_unless($chartOfAccount->canBeManagedBy(auth()->user()), 403, 'You can only delete accounts you created.');

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

