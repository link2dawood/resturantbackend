<?php

namespace App\Http\Controllers;

use App\Models\TransactionType;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactionTypes = TransactionType::all();

        return view('transaction_types.index', compact('transactionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->get();

        return view('transaction_types.create', compact('parentTransactionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
        ]);

        TransactionType::create($request->only(['name', 'p_id']));

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionType $transactionType)
    {
        $transactionType->load('parent', 'children');

        return view('transaction_types.show', compact('transactionType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionType $transactionType)
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->where('id', '!=', $transactionType->id)->get();

        return view('transaction_types.edit', compact('transactionType', 'parentTransactionTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
        ]);

        $transactionType->update($request->only(['name', 'p_id']));

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
