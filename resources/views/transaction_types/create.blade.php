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
            <small class="form-text text-muted">Category will be automatically assigned if a match is found based on the description name.</small>
        </div>

        <!-- <div class="form-group">
            <label for="p_id">Category Transaction Type</label>
            <select name="p_id" id="p_id" class="form-control">
                <option value="">None</option>
                @foreach ($parentTransactionTypes as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div> -->

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const categorySelect = document.getElementById('p_id');
    
    if (nameInput && categorySelect) {
        // Store all parent categories for matching
        const parentCategories = [];
        categorySelect.querySelectorAll('option').forEach(option => {
            if (option.value) {
                parentCategories.push({
                    id: option.value,
                    name: option.textContent.trim().toLowerCase()
                });
            }
        });
        
        // Function to find matching category based on description name
        function findMatchingCategory(descriptionName) {
            if (!descriptionName) return null;
            
            const descLower = descriptionName.toLowerCase().trim();
            
            // First, try exact match (case-insensitive)
            for (const category of parentCategories) {
                if (category.name === descLower) {
                    return category.id;
                }
            }
            
            // Then try partial match (description contains category name or vice versa)
            for (const category of parentCategories) {
                if (descLower.includes(category.name) || category.name.includes(descLower)) {
                    return category.id;
                }
            }
            
            // Try word-by-word matching
            const descWords = descLower.split(/\s+/);
            for (const category of parentCategories) {
                const categoryWords = category.name.split(/\s+/);
                for (const descWord of descWords) {
                    for (const catWord of categoryWords) {
                        if (descWord.length >= 3 && catWord.length >= 3 && 
                            (descWord.includes(catWord) || catWord.includes(descWord))) {
                            return category.id;
                        }
                    }
                }
            }
            
            return null;
        }
        
        // Auto-assign category when description name changes
        let timeout;
        nameInput.addEventListener('input', function() {
            clearTimeout(timeout);
            
            // Only auto-assign if category is not already selected
            if (!categorySelect.value) {
                timeout = setTimeout(function() {
                    const matchingCategoryId = findMatchingCategory(nameInput.value);
                    if (matchingCategoryId) {
                        categorySelect.value = matchingCategoryId;
                        
                        // Show a brief visual feedback
                        const originalBg = categorySelect.style.backgroundColor;
                        categorySelect.style.backgroundColor = '#d4edda';
                        setTimeout(function() {
                            categorySelect.style.backgroundColor = originalBg;
                        }, 1000);
                    }
                }, 500); // Wait 500ms after user stops typing
            }
        });
        
        // Also check on blur (when user leaves the field)
        nameInput.addEventListener('blur', function() {
            if (!categorySelect.value && nameInput.value) {
                const matchingCategoryId = findMatchingCategory(nameInput.value);
                if (matchingCategoryId) {
                    categorySelect.value = matchingCategoryId;
                }
            }
        });
    }
});
</script>
@endsection
