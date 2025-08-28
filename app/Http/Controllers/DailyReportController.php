<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\DailyReportTransaction;
use App\Models\Store;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = DailyReport::with(['store', 'creator', 'transactions'])
            ->orderBy('report_date', 'desc')
            ->paginate(10);
            
        return view('daily-reports.index', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::all();
        return view('daily-reports.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd($request->all());
        $validatedData = $request->validate([
            'report_date'         => 'required|date',
            'page_number'         => 'nullable|integer|min:1',
            'weather'             => 'nullable|string|max:50',
            'holiday_event'       => 'nullable|string|max:100',
        
            // decimals
            'projected_sales'     => 'required|numeric|min:0',
            'gross_sales'         => 'required|numeric|min:0',
            'amount_of_cancels'   => 'nullable|numeric|min:0',
            'amount_of_voids'     => 'nullable|numeric|min:0',
            'coupons_received'    => 'nullable|numeric|min:0',
            'adjustments_overrings' => 'nullable|numeric|min:0',
            'net_sales'           => 'nullable|numeric|min:0',
            'tax'                 => 'nullable|numeric|min:0',
            'average_ticket'      => 'nullable|numeric|min:0',
            'sales'               => 'nullable|numeric|min:0',
            'total_paid_outs'     => 'required|numeric|min:0',
            'credit_cards'        => 'nullable|numeric|min:0',
            'cash_to_account'     => 'nullable|numeric|min:0',
            'actual_deposit'      => 'nullable|numeric|min:0',
            'short'               => 'nullable|numeric|min:0',
            'over'                => 'nullable|numeric|min:0',
        
            // integers
            'number_of_no_sales'  => 'nullable|integer|min:0',
            'total_coupons'       => 'nullable|integer|min:0',
            'total_customers'     => 'nullable|integer|min:0',
        
            // relationships
            'store_id'            => 'required|exists:stores,id',
        
        ]);
        

        try {
            DB::beginTransaction();
            
            $validatedData['created_by'] = auth()->id();
            
            $dailyReport = DailyReport::create($validatedData);
            
            if ($request->has('transactions')) {
                foreach ($request->transactions as $transactionData) {
                    $transactionData['daily_report_id'] = $dailyReport->id;
                    DailyReportTransaction::create($transactionData);
                }
            }
            
            DB::commit();
            
            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', 'Daily report created successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            throw ValidationException::withMessages([
                'error' => ['Failed to create daily report. Please try again.']
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyReport $dailyReport)
    {
        $dailyReport->load(['store', 'creator', 'transactions']);
        return view('daily-reports.show', compact('dailyReport'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DailyReport $dailyReport)
    {
        $stores = Store::all();
        $dailyReport->load('transactions');
        return view('daily-reports.edit', compact('dailyReport', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyReport $dailyReport)
    {
        $validatedData = $request->validate([
            'restaurant_name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'report_date' => 'required|date',
            'weather' => 'nullable|string|max:50',
            'holiday_event' => 'nullable|string|max:100',
            'projected_sales' => 'required|numeric|min:0',
            'gross_sales' => 'required|numeric|min:0',
            'amount_of_cancels' => 'nullable|numeric|min:0',
            'amount_of_voids' => 'nullable|numeric',
            'number_of_no_sales' => 'nullable|integer|min:0',
            'total_coupons' => 'nullable|integer|min:0',
            'coupons_received' => 'nullable|numeric|min:0',
            'adjustments_overrings' => 'nullable|numeric',
            'total_customers' => 'nullable|integer|min:0',
            'credit_cards' => 'nullable|numeric|min:0',
            'actual_deposit' => 'nullable|numeric|min:0',
            'store_id' => 'nullable|exists:stores,id',
            'transactions' => 'nullable|array',
            'transactions.*.transaction_id' => 'required|integer',
            'transactions.*.company' => 'required|string|max:100',
            'transactions.*.transaction_type' => 'required|in:Food Cost,Rent,Accounting,Taxes,Other',
            'transactions.*.amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();
            
            $dailyReport->update($validatedData);
            
            $dailyReport->transactions()->delete();
            
            if ($request->has('transactions')) {
                foreach ($request->transactions as $transactionData) {
                    $transactionData['daily_report_id'] = $dailyReport->id;
                    DailyReportTransaction::create($transactionData);
                }
            }
            
            DB::commit();
            
            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', 'Daily report updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            throw ValidationException::withMessages([
                'error' => ['Failed to update daily report. Please try again.']
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReport $dailyReport)
    {
        try {
            $dailyReport->delete();
            return redirect()->route('daily-reports.index')
                ->with('success', 'Daily report deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('daily-reports.index')
                ->with('error', 'Failed to delete daily report.');
        }
    }

    /**
     * Show the reports form for creating/editing daily reports
     */
    public function reports($id)
    {
        $store = Store::find($id);
        $types = TransactionType::all();
        return view('daily-reports.form', compact('store','types'));
    }
}
