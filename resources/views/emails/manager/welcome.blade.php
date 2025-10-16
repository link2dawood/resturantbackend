<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .credentials-box {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn:hover {
            background: #218838;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .stores-list {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .store-item {
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .store-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to {{ $appName }}!</h1>
        <p>Your Manager Account Has Been Created</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $manager->name }},</h2>
        
        <div class="success">
            <strong>Welcome aboard!</strong> {{ $createdBy->name }} ({{ $createdBy->role->label() }}) has created a manager account for you in {{ $appName }}.
        </div>
        
        <p>As a restaurant manager, you now have access to the following capabilities:</p>
        
        <ul>
            <li>📊 <strong>Daily Reports</strong> - Create and submit daily financial reports for your assigned stores</li>
            <li>👀 <strong>Store Access</strong> - View information for stores you're assigned to manage</li>
            <li>📈 <strong>Performance Tracking</strong> - Monitor daily sales, transactions, and revenue data</li>
            <li>📋 <strong>Report Management</strong> - Edit and resubmit reports as needed</li>
            <li>👤 <strong>Profile Management</strong> - Update your personal information and preferences</li>
        </ul>
        
        <h3>🔐 Your Login Credentials</h3>
        <div class="credentials-box">
            <p><strong>Email:</strong> {{ $manager->email }}</p>
            <p><strong>Temporary Password:</strong> <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-family: monospace;">{{ $temporaryPassword }}</code></p>
        </div>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Please change your password immediately after logging in for security purposes.
        </div>
        
        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="btn">Login to Your Account</a>
        </div>
        
        @if($stores->count() > 0)
        <h3>🏪 Your Assigned Stores ({{ $stores->count() }})</h3>
        <div class="stores-list">
            @foreach($stores as $store)
            <div class="store-item">
                <strong>{{ $store->store_info ?? 'Store' }}</strong><br>
                <small>📍 {{ $store->address }}, {{ $store->city }}, {{ $store->state }} {{ $store->zip }}</small><br>
                <small>📞 {{ $store->phone }}</small>
            </div>
            @endforeach
        </div>
        <div class="success">
            <strong>🎯 Your Responsibility:</strong> You can create and manage daily reports for the stores listed above.
        </div>
        @else
        <div class="warning">
            <strong>📋 Store Assignment:</strong> No stores have been assigned to you yet. Contact your administrator to get store assignments.
        </div>
        @endif
        
        <h3>📋 Account Information</h3>
        <ul>
            <li><strong>Name:</strong> {{ $manager->name }}</li>
            <li><strong>Email:</strong> {{ $manager->email }}</li>
            <li><strong>Role:</strong> Manager</li>
            @if($manager->username)
            <li><strong>Username:</strong> {{ $manager->username }}</li>
            @endif
            <li><strong>Account Created:</strong> {{ $manager->created_at->format('F j, Y \a\t g:i A') }}</li>
            <li><strong>Created By:</strong> {{ $createdBy->name }} ({{ $createdBy->role->label() }})</li>
            <li><strong>Store Access:</strong> {{ $stores->count() }} store(s)</li>
        </ul>
        
        <h3>🚀 Getting Started</h3>
        <ol>
            <li><strong>Log in</strong> using the credentials above</li>
            <li><strong>Change your password</strong> in the profile settings</li>
            <li><strong>Complete your profile</strong> with additional personal information</li>
            <li><strong>Familiarize yourself</strong> with your assigned stores</li>
            <li><strong>Start creating daily reports</strong> for your stores</li>
        </ol>
        
        <h3>📊 Daily Reports Guide</h3>
        <p>As a manager, your main responsibility is to create accurate daily reports that include:</p>
        <ul>
            <li>Sales figures and customer counts</li>
            <li>Transaction details and payment methods</li>
            <li>Revenue breakdowns by income type</li>
            <li>Cash reconciliation and deposit information</li>
            <li>Any adjustments, voids, or special circumstances</li>
        </ul>
        
        <div class="success">
            <strong>💡 Tips for Success:</strong>
            <ul style="margin: 10px 0;">
                <li>Submit reports daily for accurate tracking</li>
                <li>Double-check all calculations before submission</li>
                <li>Include notes for any unusual circumstances</li>
                <li>Contact support if you need assistance</li>
            </ul>
        </div>
        
        <p>If you have any questions or need assistance getting started, please don't hesitate to reach out to your administrator or the support team.</p>
        
        <p>We're excited to have you on the team!</p>
        <p><strong>The {{ $appName }} Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This email was sent automatically by {{ $appName }}.</p>
        <p>If you did not expect this email, please contact your system administrator.</p>
        <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
    </div>
</body>
</html>