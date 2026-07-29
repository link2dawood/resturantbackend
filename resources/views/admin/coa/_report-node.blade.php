{{-- Recursive report rows. Indentation uses (depth - 1) so categories (depth 1)
     sit flush and sub-accounts nest under them. --}}
@foreach($nodes as $node)
    @php $indent = max(0, $node->depth - 1) * 22; @endphp
    <tr>
        <td style="white-space:nowrap; padding-left: {{ $indent }}px;"><strong>{{ $node->account_code }}</strong></td>
        <td style="padding-left: {{ $indent }}px;">
            @if($node->depth <= 1)
                <strong>{{ $node->account_name }}</strong>
            @else
                {{ $node->account_name }}
            @endif
        </td>
    </tr>
    @if($node->childNodes->isNotEmpty())
        @include('admin.coa._report-node', ['nodes' => $node->childNodes])
    @endif
@endforeach
