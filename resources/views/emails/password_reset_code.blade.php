<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code - Tsiyon</title>
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
            padding: 40px 32px 24px;
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

        .content {
            padding: 36px 32px;
            line-height: 1.65;
            font-size: 15px;
            color: #cbd5e1;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 16px;
        }

        .code-box {
            margin: 28px 0;
            padding: 24px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            text-align: center;
        }

        .code-title {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .code-value {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #60a5fa;
            font-family: 'Courier New', Courier, monospace;
        }

        .expiry-warning {
            font-size: 13px;
            color: #f59e0b;
            margin-top: 12px;
            font-weight: 500;
        }

        .footer {
            padding: 24px 32px;
            background: #0d131f;
            border-top: 1px solid #1f2937;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="logo-text">Tsiyon</h1>
        </div>

        <div class="content">
            <div class="greeting">Hello, {{ $name }}</div>
            <p>You recently requested to reset your password. Use the verification code below to proceed with resetting your password:</p>

            <div class="code-box">
                <div class="code-title">Verification Code</div>
                <div class="code-value">{{ $code }}</div>
                <div class="expiry-warning">⏱ This code expires in 2 minutes.</div>
            </div>

            <p>If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged and your account stays secure.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Tsiyon. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
