<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #f1f3f4; text-align: left; }
        td.num { text-align: right; }
        .green { background: #e6f4ea; }
        .yellow { background: #fef7e0; }
        .red { background: #fce8e6; }
        .incomplete { background: #f1f3f4; color: #999; }
        .tally span { display: inline-block; margin-right: 10px; }
    </style>
</head>
<body>
    @php $fmt = fn ($n) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); @endphp
    <h1>Variance Report</h1>
    <div class="muted">{{ $store->store_info ?? 'Store' }} · week of {{ $week->format('M j, Y') }}</div>
    <div class="tally muted" style="margin-top:6px;">
        <span>{{ $tally['green'] }} acceptable</span>
        <span>{{ $tally['yellow'] }} investigate</span>
        <span>{{ $tally['red'] }} problem</span>
        <span>{{ $tally['incomplete'] }} incomplete</span>
    </div>

    <table>
        <thead><tr>
            <th>Item</th><th>Unit</th><th>Start</th><th>Ordered</th><th>Available</th>
            <th>Theo. usage</th><th>Theo. ending</th><th>Actual</th><th>Variance</th><th>%</th>
        </tr></thead>
        <tbody>
            @foreach($rows as $r)
                @php $l = $r['line']; $cls = $l->isIncomplete ? 'incomplete' : $l->severity; @endphp
                <tr class="{{ $cls }}">
                    <td>{{ $r['item']->name }}</td>
                    <td>{{ $l->baseUnit }}</td>
                    <td class="num">{{ $fmt($l->startingStock) }}</td>
                    <td class="num">{{ $fmt($l->orderedQty) }}</td>
                    <td class="num">{{ $fmt($l->totalAvailable) }}</td>
                    <td class="num">{{ $fmt($l->theoreticalUsage) }}</td>
                    <td class="num">{{ $fmt($l->theoreticalEnding) }}</td>
                    <td class="num">{{ $l->isIncomplete ? '—' : $fmt($l->actualEnding) }}</td>
                    <td class="num">{{ $l->isIncomplete ? '—' : $fmt($l->variance) }}</td>
                    <td class="num">{{ $l->isIncomplete ? '—' : number_format((float) $l->variancePct, 2).'%' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
