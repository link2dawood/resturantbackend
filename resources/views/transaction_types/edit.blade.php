@extends('layouts.tabler')

@section('title', 'Edit Transaction Type')

@section('content')
<div class="container-xl mt-5">
    <h2>Edit Transaction Type</h2>

    <form action="{{ route('transaction-types.update', $transactionType->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Description Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $transactionType->name }}" required>
        </div>

        <div class="form-group">
            <label for="p_id">Category Transaction Type</label>
            <select name="p_id" id="p_id" class="form-control">
                <option value="">None</option>
                @foreach ($parentTransactionTypes as $parent)
                    <option value="{{ $parent->id }}" {{ $transactionType->p_id == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="default_coa_id">Default COA Category (Optional)</label>
            <select name="default_coa_id" id="default_coa_id" class="form-control">
                <option value="">None (Select Manually)</option>
                @foreach ($chartOfAccounts as $coa)
                    <option value="{{ $coa->id }}" {{ $transactionType->default_coa_id == $coa->id ? 'selected' : '' }}>
                        {{ $coa->account_code }} - {{ $coa->account_name }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">This COA will be automatically assigned when syncing cash expenses with this transaction type</small>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection
