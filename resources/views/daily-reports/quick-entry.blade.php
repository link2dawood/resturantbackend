@extends('layouts.tabler')
@section('title', 'Quick Daily Report Entry')
@section('content')

<style>
    .quick-entry-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .quick-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .quick-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 20px;
    }
    
    .section-header {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: 600;
        color: #495057;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .form-row.single {
        grid-template-columns: 1fr;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        display: block;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 2px solid #e9ecef;
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }
    
    .calculated-display {
        background: #e7f3ff;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .calc-item {
        text-align: center;
        background: white;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .calc-label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .calc-value {
        font-size: 1.2rem;
        font-weight: bold;
        color: #007bff;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 25px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        margin-top: 20px;
        transition: transform 0.2s;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
    }
    
    .mode-toggle {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .toggle-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }
    
    .toggle-link:hover {
        text-decoration: underline;
    }
</style>

<div class="quick-entry-container">
    <div class="quick-header">
        <h1>⚡ Quick Daily Report Entry</h1>
        <p>Streamlined form for fast data entry - only essential fields</p>
    </div>

    <div class="mode-toggle">
        <a href="{{ route('daily-reports.create') }}" class="toggle-link">
            🔧 Switch to Full Entry Mode →
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">⚠️ Please Review and Fix:</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form id="quickEntryForm" method="POST" action="{{ route('daily-reports.store') }}">
        @csrf
        
        <!-- Store and Date Selection -->
        <div class="quick-card">
            <div class="section-header">📍 Store & Date Information</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-control" required>
                        <option value="">Select Store</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="report_date" class="form-control" value="{{ old('report_date', date('Y-m-d')) }}" required>
                </div>
            </div>
        </div>

        <!-- Essential Sales Data -->
        <div class="quick-card">
            <div class="section-header">💰 Essential Sales Data</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Projected Sales ($)</label>
                    <input type="number" name="projected_sales" class="form-control" step="0.01" value="{{ old('projected_sales') }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gross Sales ($)</label>
                    <input type="number" name="gross_sales" class="form-control" step="0.01" value="{{ old('gross_sales') }}" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Coupons Received ($)</label>
                    <input type="number" name="coupons_received" class="form-control" step="0.01" value="{{ old('coupons_received', '0') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Credit Cards ($)</label>
                    <input type="number" name="credit_cards" class="form-control" step="0.01" value="{{ old('credit_cards', '0') }}">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Actual Deposit ($)</label>
                    <input type="number" name="actual_deposit" class="form-control" step="0.01" value="{{ old('actual_deposit', '0') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Customers</label>
                    <input type="number" name="total_customers" class="form-control" value="{{ old('total_customers', '0') }}">
                </div>
            </div>
        </div>

        <!-- Auto-calculated Results -->
        <div class="calculated-display" id="quickCalculations">
            <div class="calc-item">
                <div class="calc-label">Net Sales</div>
                <div class="calc-value" id="qNetSales">$0.00</div>
            </div>
            <div class="calc-item">
                <div class="calc-label">Tax Amount</div>
                <div class="calc-value" id="qTax">$0.00</div>
            </div>
            <div class="calc-item">
                <div class="calc-label">Cash to Account</div>
                <div class="calc-value" id="qCashToAccount">$0.00</div>
            </div>
            <div class="calc-item">
                <div class="calc-label">Short/Over</div>
                <div class="calc-value" id="qShortOver">$0.00</div>
            </div>
        </div>

        <!-- Optional Notes -->
        <div class="quick-card">
            <div class="section-header">📝 Optional Information</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Weather</label>
                    <input type="text" name="weather" class="form-control" value="{{ old('weather') }}" placeholder="e.g., Sunny, Rainy">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Special Events</label>
                    <input type="text" name="holiday_event" class="form-control" value="{{ old('holiday_event') }}" placeholder="Holidays, promotions, etc.">
                </div>
            </div>
        </div>

        <!-- Hidden calculated fields -->
        <input type="hidden" name="net_sales" id="hiddenNetSales">
        <input type="hidden" name="tax" id="hiddenTax">
        <input type="hidden" name="sales" id="hiddenSales">
        <input type="hidden" name="cash_to_account" id="hiddenCashToAccount">
        <input type="hidden" name="short" id="hiddenShort">
        <input type="hidden" name="over" id="hiddenOver">
        <input type="hidden" name="total_paid_outs" value="0">
        <input type="hidden" name="amount_of_cancels" value="0">
        <input type="hidden" name="amount_of_voids" value="0">
        <input type="hidden" name="adjustments_overrings" value="0">
        <input type="hidden" name="number_of_no_sales" value="0">
        <input type="hidden" name="total_coupons" value="0">
        <input type="hidden" name="average_ticket" value="0">

        <button type="submit" class="submit-btn">💾 Save Quick Report</button>
    </form>
</div>

<script>
function quickCalculate() {
    const grossSales = parseFloat(document.querySelector('input[name="gross_sales"]').value || 0);
    const couponsReceived = parseFloat(document.querySelector('input[name="coupons_received"]').value || 0);
    const creditCards = parseFloat(document.querySelector('input[name="credit_cards"]').value || 0);
    const actualDeposit = parseFloat(document.querySelector('input[name="actual_deposit"]').value || 0);
    const totalCustomers = parseInt(document.querySelector('input[name="total_customers"]').value || 0);
    
    // Calculate net sales
    const netSales = grossSales - couponsReceived;
    
    // Calculate tax (8.25% tax rate)
    const tax = netSales - (netSales / 1.0825);
    
    // Calculate sales pre-tax
    const salesPreTax = netSales - tax;
    
    // Calculate cash to account (no paid outs in quick mode)
    const cashToAccount = netSales - creditCards;
    
    // Calculate short/over
    let shortOver = actualDeposit - cashToAccount;
    
    // Update display
    document.getElementById('qNetSales').textContent = `$${netSales.toFixed(2)}`;
    document.getElementById('qTax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('qCashToAccount').textContent = `$${cashToAccount.toFixed(2)}`;
    document.getElementById('qShortOver').textContent = `$${shortOver.toFixed(2)}`;
    
    // Update hidden fields
    document.getElementById('hiddenNetSales').value = netSales.toFixed(2);
    document.getElementById('hiddenTax').value = tax.toFixed(2);
    document.getElementById('hiddenSales').value = salesPreTax.toFixed(2);
    document.getElementById('hiddenCashToAccount').value = cashToAccount.toFixed(2);
    
    if (shortOver < 0) {
        document.getElementById('hiddenShort').value = shortOver.toFixed(2);
        document.getElementById('hiddenOver').value = '0.00';
    } else {
        document.getElementById('hiddenShort').value = '0.00';
        document.getElementById('hiddenOver').value = shortOver.toFixed(2);
    }
    
    // Calculate average ticket if customers > 0
    if (totalCustomers > 0) {
        const avgTicket = grossSales / totalCustomers;
        document.querySelector('input[name="average_ticket"]').value = avgTicket.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for calculation
    const calcInputs = ['gross_sales', 'coupons_received', 'credit_cards', 'actual_deposit', 'total_customers'];
    
    calcInputs.forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
            input.addEventListener('input', quickCalculate);
            input.addEventListener('blur', function() {
                if (this.type === 'number' && this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        }
    });
    
    // Form submission loading state
    document.getElementById('quickEntryForm').addEventListener('submit', function() {
        const submitBtn = document.querySelector('.submit-btn');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        submitBtn.disabled = true;
    });
    
    // Initial calculation
    quickCalculate();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter to submit
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('quickEntryForm').submit();
        }
        
        // Tab navigation enhancement
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('input, select'));
            const currentIndex = inputs.indexOf(e.target);
            if (currentIndex < inputs.length - 1) {
                inputs[currentIndex + 1].focus();
            }
        }
    });
});
</script>

@endsection