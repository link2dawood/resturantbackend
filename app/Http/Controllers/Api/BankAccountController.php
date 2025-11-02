<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * Display a listing of bank accounts
     */
    public function index(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = BankAccount::with('store');

        // Filter by store if specified
        if ($request->has('store_id') && $request->store_id) {
            $query->forStore($request->store_id);
        }

        // Only active accounts by default
        if ($request->has('include_inactive') && $request->boolean('include_inactive')) {
            // Show all
        } else {
            $query->active();
        }

        $accounts = $query->orderBy('bank_name')->orderBy('account_number_last_four')->get();

        return response()->json($accounts);
    }

    /**
     * Store a newly created bank account
     */
    public function store(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_number_last_four' => 'required|string|size:4',
            'account_type' => 'required|in:checking,savings,credit_card',
            'store_id' => 'nullable|exists:stores,id',
            'opening_balance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account = BankAccount::create([
            'bank_name' => $request->bank_name,
            'account_number_last_four' => $request->account_number_last_four,
            'account_type' => $request->account_type,
            'store_id' => $request->store_id,
            'opening_balance' => $request->opening_balance,
            'current_balance' => $request->opening_balance,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Bank account created successfully',
            'data' => $account->load('store')
        ], 201);
    }

    /**
     * Display the specified bank account
     */
    public function show($id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $account = BankAccount::with(['store', 'transactions'])->findOrFail($id);

        return response()->json($account);
    }

    /**
     * Update the specified bank account
     */
    public function update(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $account = BankAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bank_name' => 'sometimes|required|string|max:100',
            'account_number_last_four' => 'sometimes|required|string|size:4',
            'account_type' => 'sometimes|required|in:checking,savings,credit_card',
            'store_id' => 'nullable|exists:stores,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account->update($request->only([
            'bank_name',
            'account_number_last_four',
            'account_type',
            'store_id',
            'is_active'
        ]));

        return response()->json([
            'message' => 'Bank account updated successfully',
            'data' => $account->load('store')
        ]);
    }
}
