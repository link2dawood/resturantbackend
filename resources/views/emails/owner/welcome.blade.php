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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to {{ $appName }}!</h1>
        <p>Your Owner Account Has Been Created</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $owner->name }},</h2>
        
        <div class="success">
            <strong>Great news!</strong> {{ $createdBy->name }} has created an owner account for you in {{ $appName }}.
        </div>
        
        <p>You now have access to the restaurant management system with the following capabilities:</p>
        
        <ul>
            <li>✅ <strong>Store Management</strong> - Create and manage your restaurant locations</li>
            <li>✅ <strong>Manager Assignment</strong> - Assign managers to your stores</li>
            <li>✅ <strong>Daily Reports</strong> - View and manage daily financial reports</li>
            <li>✅ <strong>Report Approval</strong> - Approve or reject submitted reports</li>
            <li>✅ <strong>Performance Analytics</strong> - Export and analyze store performance</li>
        </ul>
        
        <h3>🔐 Your Login Credentials</h3>
        <div class="credentials-box">
            <p><strong>Email:</strong> {{ $owner->email }}</p>
            <p><strong>Temporary Password:</strong> <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-family: monospace;">{{ $temporaryPassword }}</code></p>
        </div>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Please change your password immediately after logging in for security purposes.
        </div>
        
        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="btn">Login to Your Account</a>
        </div>
        
        <h3>📋 Account Information</h3>
        <ul>
            <li><strong>Name:</strong> {{ $owner->name }}</li>
            <li><strong>Email:</strong> {{ $owner->email }}</li>
            <li><strong>Role:</strong> Owner</li>
            <li><strong>State:</strong> {{ $owner->state ?? 'Not specified' }}</li>
            <li><strong>Account Created:</strong> {{ $owner->created_at->format('F j, Y \a\t g:i A') }}</li>
            <li><strong>Created By:</strong> {{ $createdBy->name }} ({{ $createdBy->role->label() }})</li>
        </ul>
        
        @if($owner->corporate_email)
        <h3>💼 Business Information</h3>
        <ul>
            @if($owner->corporate_email)
                <li><strong>Corporate Email:</strong> {{ $owner->corporate_email }}</li>
            @endif
            @if($owner->corporate_phone)
                <li><strong>Corporate Phone:</strong> {{ $owner->corporate_phone }}</li>
            @endif
            @if($owner->corporate_ein)
                <li><strong>EIN:</strong> {{ $owner->corporate_ein }}</li>
            @endif
        </ul>
        @endif
        
        <h3>🚀 Getting Started</h3>
        <ol>
            <li><strong>Log in</strong> using the credentials above</li>
            <li><strong>Change your password</strong> in the profile settings</li>
            <li><strong>Complete your profile</strong> with additional business information</li>
            <li><strong>Create your first store</strong> to start managing operations</li>
            <li><strong>Assign managers</strong> to your stores for daily report management</li>
        </ol>
        
        <div class="success">
            <strong>💡 Tip:</strong> You can manage multiple stores and assign different managers to each location. Each manager will only see reports for their assigned stores.
        </div>
        
        <p>If you have any questions or need assistance getting started, please don't hesitate to reach out.</p>
        
        <p>Welcome aboard!</p>
        <p><strong>The {{ $appName }} Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This email was sent automatically by {{ $appName }}.</p>
        <p>If you did not expect this email, please contact your system administrator.</p>
        <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
    </div>
</body>
</html>