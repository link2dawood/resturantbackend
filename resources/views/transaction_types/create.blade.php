@extends('layouts.tabler')

@section('title', 'Create Transaction Type')

@section('content')
<div class="container-xl mt-5">
    <h2>Create Transaction Type</h2>

    <form action="{{ route('transaction-types.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Description Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="p_id">Category Transaction Type</label>
            <select name="p_id" id="p_id" class="form-control">
                <option value="">None</option>
                @foreach ($parentTransactionTypes as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="default_coa_id">Default COA Category (Optional)</label>
            <select name="default_coa_id" id="default_coa_id" class="form-control">
                <option value="">None (Select Manually)</option>
                @foreach ($chartOfAccounts as $coa)
                    <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">This COA will be automatically assigned when syncing cash expenses with this transaction type</small>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Create</button>
    </form>
</div>
@endsection
