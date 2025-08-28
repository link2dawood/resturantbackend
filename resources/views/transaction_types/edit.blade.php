@extends('layouts.tabler')

@section('title', 'Edit Transaction Type')

@section('content')
<div class="container-xl mt-5">
    <h2>Edit Transaction Type</h2>

    <form action="{{ route('transaction-types.update', $transactionType->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $transactionType->name }}" required>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection
