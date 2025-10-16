<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $companyName }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 30px;
            text-align: center;
        }
        .info-section {
            background-color: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
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
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .instructions {
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .instructions h4 {
            color: #c53030;
            margin: 0 0 10px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to {{ $companyName }}</h1>
        </div>
        
        <div class="content">
            <div class="welcome-message">
                <strong>Hello {{ $managerName }}!</strong><br>
                Welcome to your new role as Store Manager! We're excited to have you on board.
            </div>

            <div class="info-section">
                <h3>📋 Your Assignment Details</h3>
                <p><strong>Assigned by:</strong> {{ $assignedByName }} ({{ $assignedByRole }})</p>
                <p><strong>Email:</strong> {{ $managerEmail }}</p>
                <p><strong>Assigned Stores:</strong> {{ $storeCount }} {{ $storeCount === 1 ? 'store' : 'stores' }}</p>
            </div>

            <div class="divider"></div>

            <h3>🏪 Your Assigned Stores</h3>
            <div class="store-list">
                @foreach($stores as $store)
                <div class="store-item">
                    <div class="store-name">{{ $store->store_info ?? 'Store' }}</div>
                    <div class="store-details">
                        📍 {{ $store->address }}<br>
                        {{ $store->city }}, {{ $store->state }} {{ $store->zip }}<br>
                        📞 {{ $store->phone }}<br>
                        👤 Contact: {{ $store->contact_name }}
                    </div>
                </div>
                @endforeach
            </div>

            <div class="divider"></div>

            <div class="instructions">
                <h4>📝 Getting Started Instructions</h4>
                <ul>
                    <li><strong>Login:</strong> Use your email address ({{ $managerEmail }}) to access the system</li>
                    <li><strong>Daily Reports:</strong> You can create and manage daily reports for your assigned stores</li>
                    <li><strong>Access:</strong> You have manager-level access to view reports, but cannot modify store settings</li>
                    <li><strong>Support:</strong> Contact our support team if you need help getting started</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="cta-button">
                    🔐 Access Restaurant Management System
                </a>
            </div>

            <div class="info-section">
                <h3>💡 Key Features You Can Access</h3>
                <ul>
                    <li>Create daily reports for your assigned stores</li>
                    <li>View historical reports and analytics</li>
                    <li>Track sales, transactions, and revenue data</li>
                    <li>Monitor store performance metrics</li>
                    <li>Generate reports for management review</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <div class="contact-info">
                <strong>Need Help?</strong><br>
                📧 {{ $supportEmail }}<br>
                📞 {{ $supportPhone }}
            </div>
            <p style="margin-top: 20px; color: #a0aec0; font-size: 12px;">
                This email was sent because you were assigned as a store manager in {{ $companyName }}.
            </p>
        </div>
    </div>
</body>
</html>