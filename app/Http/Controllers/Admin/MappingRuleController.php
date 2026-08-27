<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategorizationDecision;
use App\Models\ChartOfAccount;
use App\Models\TransactionMappingRule;
use App\Services\CategorizationEngine;
use Illuminate\Http\Request;

/**
 * Phase 4: admin view over the learned categorization rules — inspect, edit,
 * deactivate, or delete them, and see a feed of recent categorization decisions.
 * Owners manage their own (and see global) rules; admins manage all.
 */
class MappingRuleController extends Controller
{
    public function __construct(private CategorizationEngine $engine)
    {
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $ownerId = $this->engine->ownerIdForUser($user);

        $query = TransactionMappingRule::with(['coa', 'owner', 'vendor'])
            ->orderByDesc('is_active')
            ->orderByDesc('confidence_score')
            ->orderByDesc('times_used');

        // Owners see their own rules + global fallbacks; admins see everything.
        if (! $user->isAdmin()) {
            $query->forOwner($ownerId);
        }

        $rules = $query->paginate(25);

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $recentDecisions = CategorizationDecision::with(['chosenCoa', 'suggestedCoa'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('owner_id', $ownerId))
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.mapping-rules.index', compact('rules', 'accounts', 'recentDecisions'));
    }

    public function update(Request $request, TransactionMappingRule $rule)
    {
        $this->authorizeRule($rule);

        $data = $request->validate([
            'description_pattern' => 'required|string|max:255',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'is_active' => 'nullable|boolean',
        ]);

        $rule->update([
            'description_pattern' => $data['description_pattern'],
            'coa_id' => $data['coa_id'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Mapping rule updated.');
    }

    public function toggle(TransactionMappingRule $rule)
    {
        $this->authorizeRule($rule);
        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', 'Mapping rule '.($rule->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroy(TransactionMappingRule $rule)
    {
        $this->authorizeRule($rule);
        $rule->delete();

        return back()->with('success', 'Mapping rule deleted.');
    }

    /** Owners may manage only their own rules; admins may manage any. */
    private function authorizeRule(TransactionMappingRule $rule): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        $ownerId = $this->engine->ownerIdForUser($user);
        abort_unless(
            $rule->owner_id !== null && (int) $rule->owner_id === $ownerId,
            403,
            'You can only manage your own categorization rules.'
        );
    }
}
