<?php

namespace App\Http\Controllers;

use App\Models\TransactionType;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactionTypes = TransactionType::with('defaultCoa', 'parent', 'children')->get();

        return view('transaction_types.index', compact('transactionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->get();
        $chartOfAccounts = ChartOfAccount::where('account_type', 'Expense')
            ->orWhere('account_type', 'COGS')
            ->active()
            ->orderBy('account_code')
            ->get();

        return view('transaction_types.create', compact('parentTransactionTypes', 'chartOfAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        TransactionType::create($request->only(['name', 'p_id', 'default_coa_id']));

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionType $transactionType)
    {
        $transactionType->load('parent', 'children', 'defaultCoa');

        return view('transaction_types.show', compact('transactionType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionType $transactionType)
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->where('id', '!=', $transactionType->id)->get();
        $chartOfAccounts = ChartOfAccount::where('account_type', 'Expense')
            ->orWhere('account_type', 'COGS')
            ->active()
            ->orderBy('account_code')
            ->get();

        return view('transaction_types.edit', compact('transactionType', 'parentTransactionTypes', 'chartOfAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $transactionType->update($request->only(['name', 'p_id', 'default_coa_id']));

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionType $transactionType)
    {
        $transactionType->delete();

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type deleted successfully.');
    }
}
