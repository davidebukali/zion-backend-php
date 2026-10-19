<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Tsiyon Early Access Waitlist</title>
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

        .logo-tag {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
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

        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 16px;
        }

        p {
            margin: 0 0 16px;
        }

        .highlight-box {
            background-color: #1e293b;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
        }

        .highlight-box p {
            margin: 0;
            font-size: 14px;
            color: #93c5fd;
        }

        .steps-list {
            margin: 20px 0;
            padding-left: 20px;
        }

        .steps-list li {
            margin-bottom: 10px;
            color: #cbd5e1;
        }

        .footer {
            padding: 24px 32px;
            background-color: #0d131f;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1f2937;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2 class="logo-text">Tsiyon</h2>
            <span class="logo-tag">Early Access Beta</span>
        </div>
        <div class="content">
            <h1>You're officially on the waitlist! 🎉</h1>
            <p>Thank you for joining us on our journey to create a Christ-centered social home built for what matters
                most.</p>

            <div class="highlight-box">
                <p><strong>Registered Email:</strong> {{ $waitlistEntry->email }}</p>
            </div>

            <p><strong>What to expect next:</strong></p>
            <ul class="steps-list">
                <li><strong>Rolling Invites:</strong> We are releasing beta invitations in batches to ensure the best
                    experience for every community member.</li>
                <li><strong>Android:</strong> You'll receive your unique access link as soon as your spot opens up.</li>
                <li><strong>No Spam Ever:</strong> We will only message you with essential early-access updates and
                    release announcements.</li>
            </ul>

            <p>We can't wait to welcome you inside.</p>
            <p style="margin-top: 24px; color: #94a3b8;">Warm regards,<br><strong>The Tsiyon Team</strong></p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Tsiyon. All rights reserved.</p>
            <p>If you did not request early access using this email address, you can safely ignore this email.</p>
        </div>
    </div>
</body>

</html>