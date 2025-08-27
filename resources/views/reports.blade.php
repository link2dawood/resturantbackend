@extends('layouts.tabler')
@section('title', 'Report')
@section('content')






    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-header {
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 1.4em;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
            border: 1px solid #dee2e6;
            margin: 20px 0 10px 0;
        }
        .form-table {
            border-collapse: collapse;
            width: 100%;
        }
        .form-table td, .form-table th {
            border: 1px solid #dee2e6;
            padding: 8px;
            vertical-align: top;
        }
        .form-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .input-field {
            border: none;
            background: transparent;
            width: 100%;
            padding: 4px;
        }
        .input-field:focus {
            outline: none;
            background-color: #fff3cd;
        }
        .number-input {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .side-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .page-number {
            position: absolute;
            top: 10px;
            right: 20px;
            font-weight: bold;
        }
    </style>


    <div class="container-fluid p-4">


        <!-- Transaction Expenses Section -->
        <div class="section-title">Transaction Expenses</div>
        
        <div class="row">
        <div class="col-md-8">
    <table id="transactionTable" class="form-table">
        <thead>
            <tr>
                <th style="width: 15%;">Transaction ID</th>
                <th style="width: 25%;">Company</th>
                <th style="width: 25%;">Transaction Type</th>
                <th style="width: 15%;">Amount</th>
                <th style="width: 10%;">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="text" class="input-field" value="0"></td>
                <td>
                    <select class="input-field">
                      
                            <option value="1">WebBro</option>
                        
                    </select>
                </td>
                <td>
                    <select class="input-field">
                        
                            <option value="1">Bat</option>
                     
                    </select>
                </td>
                <td><input type="number" class="input-field number-input" placeholder="$"></td>
                <td><button type="button" class="btn btn-success addRow">+</button></td>
            </tr>
            
            <tr class="total-row">
                <td colspan="3"><strong>Total Paid Outs:</strong></td>
                <td id="totalAmount"><strong>0</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>

            <div class="col-md-4">
                <div class="side-info">
                    <div class="mb-3">
                        <label class="form-label">Date:</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Weather:</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holiday/Special Event:</label>
                        <input type="text" class="form-control">
                    </div>
                </div>
                <div class="side-info">
                    <div class="mb-2"><strong>Accounting</strong></div>
                    <div class="mb-2"><strong>Food Cost</strong></div>
                    <div class="mb-2"><strong>Rent</strong></div>
                    <div class="mb-2"><strong>Taxes</strong></div>
                </div>
            </div>
        </div>

        <!-- Sales Section -->
        <div class="section-title">Sales</div>
        
        <div class="row">
            <div class="col-md-6">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td style="width: 60%;"><strong>Projected Sales</strong></td>
                            <td><input type="number" class="input-field number-input" value="1200"></td>
                        </tr>
                        <tr>
                            <td><strong>Amount of Cancels</strong></td>
                            <td><input type="number" class="input-field number-input" value="12.53" step="0.01"></td>
                        </tr>
                        <tr>
                            <td><strong>Amount of Voids</strong></td>
                            <td><input type="number" class="input-field number-input" value="-136.23" step="0.01"></td>
                        </tr>
                        <tr>
                            <td><strong>Number of No Sales</strong></td>
                            <td><input type="number" class="input-field number-input" value="7"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Summary Section -->
        <div class="mt-4">
            <div class="row">
                <div class="col-md-6">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td style="width: 15%;">Total # of<br>Coupons</td>
                                <td style="width: 35%;"><strong>Gross Sales:</strong></td>
                                <td><input type="number" class="input-field number-input" value="1126.45" step="0.01"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Total Amount of<br>Coupons Received:</strong></td>
                                <td><input type="number" class="input-field number-input" value="3.92" step="0.01"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Adjustments:<br>Overrings/Returns:</strong></td>
                                <td><input type="number" class="input-field number-input" value="4.32" step="0.01"></td>
                            </tr>
                            <tr>
                                <td>Total # of<br>Customers</td>
                                <td><strong>Net Sales:</strong></td>
                                <td><input type="number" class="input-field number-input" value="1118.21" step="0.01"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Tax</strong></td>
                                <td><input type="number" class="input-field number-input" value="85.22" step="0.01"></td>
                            </tr>
                            <tr>
                                <td>Average<br>Ticket</td>
                                <td><strong>Sales</strong></td>
                                <td><input type="number" class="input-field number-input" value="1032.99" step="0.01"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td style="width: 50%;"><strong>Net Sales:</strong></td>
                                <td><input type="number" class="input-field number-input" value="1118.21" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>Total Paid Outs:</strong></td>
                                <td><input type="number" class="input-field number-input" value="204" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>Credit Cards:</strong></td>
                                <td><input type="number" class="input-field number-input" value="504.91" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td><input type="number" class="input-field number-input" value="409.3" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td><input type="number" class="input-field number-input" value="409" step="0.01"></td>
                            </tr>
                            <tr>
                                <td><strong>Short</strong></td>
                                <td style="width: 25%;"><input type="number" class="input-field number-input" value="-0.3" step="0.01"></td>
                                <td style="width: 25%;"><strong>Over</strong></td>
                                <td><input type="number" class="input-field number-input" value="0" step="0.01"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>






@endsection