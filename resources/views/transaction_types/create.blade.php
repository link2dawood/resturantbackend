@extends('layouts.tabler')

@section('title', 'Create Transaction Type')

@section('content')
<div class="container-xl mt-5">
    <h2>Create Transaction Type</h2>

    <form action="{{ route('transaction-types.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Create</button>
    </form>
</div>
@endsection
