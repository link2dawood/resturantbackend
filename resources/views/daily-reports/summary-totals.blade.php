@php
    $dataItemLabels = [
        'total_sales' => 'Total # of Sales',
        'average_ticket' => 'Average Ticket',
        'voids' => 'Voids',
        'adjustments' => 'Adjustments',
        'net_sales' => 'Net Sales',
        'cash_to_account' => 'Cash To Account',
        'total_coupons' => 'Total # of Coupons',
        'projected_sales' => 'Projected Sales',
        'gross_sales' => 'Gross Sales',
        'sales_tax' => 'Sales Tax',
        'total_paidout' => 'Total Paidout',
        'actual_deposit' => 'Actual Deposit',
        'total_customers' => 'Total # of Customers',
        'cancels' => 'Cancels',
        'coupons_amount' => 'Coupons Amount',
        'sales' => 'Sales',
        'credit_cards' => 'Credit Cards',
        'short_over' => 'Short Over',
    ];
@endphp

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Data Item</th>
                @foreach($summaryData['store_totals'] as $storeTotal)
                    <th>{{ $storeTotal['store']->store_info ?? 'N/A' }}</th>
                @endforeach
                <th class="table-primary"><strong>Grand Total</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataItems as $itemKey)
                <tr>
                    <td><strong>{{ $dataItemLabels[$itemKey] ?? ucfirst(str_replace('_', ' ', $itemKey)) }}</strong></td>
                    @foreach($summaryData['store_totals'] as $storeTotal)
                        <td>
                            @php
                                $value = $storeTotal['data'][$itemKey] ?? 0;
                                $isCurrency = in_array($itemKey, ['average_ticket', 'voids', 'adjustments', 'net_sales', 'cash_to_account', 'projected_sales', 'gross_sales', 'sales_tax', 'total_paidout', 'actual_deposit', 'cancels', 'coupons_amount', 'sales', 'credit_cards', 'short_over']);
                            @endphp
                            @if($isCurrency)
                                ${{ number_format($value, 2) }}
                            @else
                                {{ number_format($value) }}
                            @endif
                        </td>
                    @endforeach
                    <td class="table-primary">
                        @php
                            $grandValue = $summaryData['grand_totals'][$itemKey] ?? 0;
                            $isCurrency = in_array($itemKey, ['average_ticket', 'voids', 'adjustments', 'net_sales', 'cash_to_account', 'projected_sales', 'gross_sales', 'sales_tax', 'total_paidout', 'actual_deposit', 'cancels', 'coupons_amount', 'sales', 'credit_cards', 'short_over']);
                        @endphp
                        @if($isCurrency)
                            <strong>${{ number_format($grandValue, 2) }}</strong>
                        @else
                            <strong>{{ number_format($grandValue) }}</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

