<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #212529; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { font-size: 10px; color: #6c757d; margin-bottom: 12px; }
        .coa-report-type { font-size: 12px; margin: 12px 0 4px; border-bottom: 1.5px solid #999; padding-bottom: 2px; }
        .coa-report-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .coa-report-table th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6c757d; border-bottom: 1px solid #999; padding: 3px 4px; }
        .coa-report-table td { padding: 2px 4px; border-bottom: 1px solid #eee; font-size: 10.5px; }
    </style>
</head>
<body>
    <h1>Chart of Accounts{{ $type ? ' — '.$type : '' }}</h1>
    <div class="meta">By category and sub-account.</div>
    @include('admin.coa._report-body')
</body>
</html>
