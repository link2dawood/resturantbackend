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
                <th>Date</th>
                <th>Store</th>
                @foreach($dataItems as $itemKey)
                    <th>{{ $dataItemLabels[$itemKey] ?? ucfirst(str_replace('_', ' ', $itemKey)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($summaryData['data'] as $date => $storesData)
                @foreach($summaryData['stores'] as $store)
                    @if(isset($storesData[$store->id]))
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                            <td><strong>{{ $store->store_info }}</strong></td>
                            @foreach($dataItems as $itemKey)
                                <td>
                                    @php
                                        $value = $storesData[$store->id]['data'][$itemKey] ?? 0;
                                        $isCurrency = in_array($itemKey, ['average_ticket', 'voids', 'adjustments', 'net_sales', 'cash_to_account', 'projected_sales', 'gross_sales', 'sales_tax', 'total_paidout', 'actual_deposit', 'cancels', 'coupons_amount', 'sales', 'credit_cards', 'short_over']);
                                    @endphp
                                    @if($isCurrency)
                                        ${{ number_format($value, 2) }}
                                    @else
                                        {{ number_format($value) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

