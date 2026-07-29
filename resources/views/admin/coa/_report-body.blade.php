{{-- Shared report body: each account type, then its categories/sub-accounts.
     Renders each type root's children (the root itself is the section header). --}}
@forelse($groups as $typeName => $tree)
    <div class="coa-report-group">
        <h3 class="coa-report-type">{{ $typeName }}</h3>
        <table class="coa-report-table">
            <thead>
                <tr><th style="width:22%;">Code</th><th>Category / Sub-account</th></tr>
            </thead>
            <tbody>
                @foreach($tree as $root)
                    @if($root->childNodes->isNotEmpty())
                        @include('admin.coa._report-node', ['nodes' => $root->childNodes])
                    @else
                        {{-- A type root with no children: show the root itself. --}}
                        @include('admin.coa._report-node', ['nodes' => collect([$root])])
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p>No active accounts found.</p>
@endforelse
