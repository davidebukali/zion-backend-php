<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion Request Received</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0b0f17;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #e2e8f0;
            -webkit-text-size-adjust: 100%;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .header {
            padding: 36px 32px 24px;
            text-align: center;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.08) 0%, rgba(17, 24, 39, 0) 100%);
            border-bottom: 1px solid #1f2937;
        }

        .logo-text {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin: 0;
        }

        .logo-tag {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .content {
            padding: 36px 32px;
            line-height: 1.65;
            font-size: 15px;
            color: #cbd5e1;
        }

        .card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 18px 20px;
            margin: 24px 0;
        }

        .card-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .card-row:last-child {
            border-bottom: none;
        }

        .card-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .card-value {
            color: #f1f5f9;
            font-weight: 600;
        }

        .notice-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid #f59e0b;
            padding: 14px 16px;
            border-radius: 0 8px 8px 0;
            font-size: 13px;
            color: #fcd34d;
            margin: 20px 0;
        }

        .footer {
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1f2937;
            background: #0d131f;
        }

        .footer a {
            color: #60a5fa;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="logo-text">Tsiyon</h1>
            <span class="logo-tag">Account Deletion Request</span>
        </div>

        <div class="content">
            <h2 style="font-size: 18px; color: #f1f5f9; margin-top: 0;">We've received your request</h2>

            <p>
                Hello,
            </p>
            <p>
                This email confirms that we have received your request to delete your Tsiyon account and associated personal data.
            </p>

            <div class="card">
                <div class="card-row">
                    <span class="card-label">Request Reference:</span>
                    <span class="card-value"><code>{{ $deletionRequest->id }}</code></span>
                </div>
                <div class="card-row">
                    <span class="card-label">Email:</span>
                    <span class="card-value">{{ $deletionRequest->email }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Requested Scope:</span>
                    <span class="card-value">
                        @if (is_array($deletionRequest->data_scope) && in_array('all', $deletionRequest->data_scope))
                            Full Account &amp; All Data
                        @else
                            {{ implode(', ', (array) $deletionRequest->data_scope) }}
                        @endif
                    </span>
                </div>
                <div class="card-row">
                    <span class="card-label">Received Date:</span>
                    <span class="card-value">{{ $deletionRequest->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="notice-box">
                <strong>What happens next:</strong>
                Our team will verify your account records and process the permanent deletion of your data within <strong>30 days</strong> in accordance with our Privacy Policy. Once complete, this action cannot be undone.
            </div>

            <p style="font-size: 13px; color: #94a3b8;">
                If you did not make this request or wish to cancel it, please contact our support team immediately at
                <a href="mailto:support@tsiyonapp.com" style="color: #60a5fa;">support@tsiyonapp.com</a> quoting reference <code>{{ $deletionRequest->id }}</code>.
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Tsiyon App. All rights reserved.<br>
            <a href="https://tsiyonapp.com/privacy">Privacy Policy</a> &bull;
            <a href="https://tsiyonapp.com/terms">Terms of Service</a>
        </div>
    </div>
</body>

</html>

