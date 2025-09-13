<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Assignment Update - {{ $companyName }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
        }
        .update-message {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 25px;
            text-align: center;
        }
        .info-section {
            background-color: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #48bb78;
        }
        .info-section h3 {
            color: #2d3748;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .store-list {
            margin: 20px 0;
        }
        .store-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .store-item.new {
            border-left: 4px solid #48bb78;
            background-color: #f0fff4;
        }
        .store-item.removed {
            border-left: 4px solid #f56565;
            background-color: #fffafa;
        }
        .store-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .store-details {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .status-badge.new {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .status-badge.removed {
            background-color: #fed7e2;
            color: #742a2a;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-1px);
        }
        .summary-box {
            background-color: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #2d3748;
            color: #cbd5e0;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }
        .contact-info {
            margin: 15px 0;
        }
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 30px 0;
        }
        .section-title {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin: 25px 0 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Store Assignment Update</h1>
        </div>
        
        <div class="content">
            <div class="update-message">
                <strong>Hello {{ $managerName }}!</strong><br>
                Your store assignments have been updated by {{ $assignedByName }}.
            </div>

            <div class="summary-box">
                <h3 style="margin: 0 0 10px 0; color: #2d3748;">📊 Assignment Summary</h3>
                <p style="margin: 5px 0; font-size: 14px;">
                    <strong>Total Stores:</strong> {{ $totalStoreCount }}<br>
                    @if($hasNewStores)
                        <span style="color: #38a169;">✅ Added: {{ $newStores->count() }}</span>
                    @endif
                    @if($hasRemovedStores)
                        @if($hasNewStores) | @endif
                        <span style="color: #e53e3e;">❌ Removed: {{ $removedStores->count() }}</span>
                    @endif
                </p>
            </div>

            @if($hasNewStores)
                <div class="section-title">✅ Newly Assigned Stores</div>
                <div class="store-list">
                    @foreach($newStores as $store)
                    <div class="store-item new">
                        <div class="status-badge new">NEWLY ASSIGNED</div>
                        <div class="store-name">{{ $store->store_info }}</div>
                        <div class="store-details">
                            📍 {{ $store->address }}<br>
                            {{ $store->city }}, {{ $store->state }} {{ $store->zip }}<br>
                            📞 {{ $store->phone }}<br>
                            👤 Contact: {{ $store->contact_name }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            @if($hasRemovedStores)
                <div class="section-title">❌ Removed Store Assignments</div>
                <div class="store-list">
                    @foreach($removedStores as $store)
                    <div class="store-item removed">
                        <div class="status-badge removed">ASSIGNMENT REMOVED</div>
                        <div class="store-name">{{ $store->store_info }}</div>
                        <div class="store-details">
                            📍 {{ $store->address }}<br>
                            {{ $store->city }}, {{ $store->state }} {{ $store->zip }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            @if($allStores->isNotEmpty())
                <div class="divider"></div>
                <div class="section-title">🏪 All Your Current Store Assignments</div>
                <div class="store-list">
                    @foreach($allStores as $store)
                    <div class="store-item">
                        <div class="store-name">{{ $store->store_info }}</div>
                        <div class="store-details">
                            📍 {{ $store->address }}<br>
                            {{ $store->city }}, {{ $store->state }} {{ $store->zip }}<br>
                            📞 {{ $store->phone }}<br>
                            👤 Contact: {{ $store->contact_name }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $loginUrl }}" class="cta-button">
                    🔐 Access Restaurant Management System
                </a>
            </div>

            <div class="info-section">
                <h3>📝 Important Reminders</h3>
                <ul>
                    @if($hasNewStores)
                        <li>You can now create daily reports for newly assigned stores</li>
                    @endif
                    @if($hasRemovedStores)
                        <li>You no longer have access to create reports for removed stores</li>
                    @endif
                    <li>Your login credentials remain the same: {{ $managerEmail }}</li>
                    <li>Contact support if you have questions about these changes</li>
                    <li>All existing reports remain accessible through your dashboard</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <div class="contact-info">
                <strong>Questions About This Change?</strong><br>
                📧 {{ $supportEmail }}<br>
                📞 {{ $supportPhone }}
            </div>
            <p style="margin-top: 20px; color: #a0aec0; font-size: 12px;">
                This email was sent because your store assignments were modified by {{ $assignedByName }} ({{ $assignedByRole }}) in {{ $companyName }}.
            </p>
        </div>
    </div>
</body>
</html>