<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion Request</title>
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
            padding: 32px 32px 20px;
            text-align: left;
            background: linear-gradient(180deg, rgba(239, 68, 68, 0.1) 0%, rgba(17, 24, 39, 0) 100%);
            border-bottom: 1px solid #1f2937;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            color: #f87171;
            margin: 0 0 6px;
        }

        .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
        }

        .content {
            padding: 28px 32px;
            line-height: 1.6;
            font-size: 14px;
            color: #cbd5e1;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #1e293b;
            border-radius: 8px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid #1e293b;
        }

        .data-table th {
            width: 35%;
            color: #94a3b8;
            font-weight: 600;
            background: rgba(30, 41, 59, 0.5);
        }

        .data-table td {
            color: #f1f5f9;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .details-box {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 16px 0;
            font-size: 13px;
            color: #e2e8f0;
            white-space: pre-wrap;
        }

        .footer {
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1f2937;
            background: #0d131f;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="title">⚠️ Account &amp; Data Deletion Request</h1>
            <p class="subtitle">A user has submitted an account deletion request via the web portal.</p>
        </div>

        <div class="content">
            <p>A new account deletion request requires review and processing:</p>

            <table class="data-table">
                <tr>
                    <th>Request ID</th>
                    <td><code>{{ $deletionRequest->id }}</code></td>
                </tr>
                <tr>
                    <th>Submitted Email</th>
                    <td><strong>{{ $deletionRequest->email }}</strong></td>
                </tr>
                <tr>
                    <th>Associated User</th>
                    <td>
                        @if ($deletionRequest->user_id)
                            <span class="badge badge-danger">Registered User (ID: {{ $deletionRequest->user_id }})</span>
                        @else
                            <span class="badge badge-warning">No Direct Match Found</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Deletion Scope</th>
                    <td>
                        @if (is_array($deletionRequest->data_scope) && in_array('all', $deletionRequest->data_scope))
                            <span class="badge badge-danger">Full Account &amp; All Associated Data</span>
                        @else
                            <strong>Custom Categories:</strong>
                            {{ implode(', ', (array) $deletionRequest->data_scope) }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Reason</th>
                    <td>{{ $deletionRequest->reason ?: 'Not specified' }}</td>
                </tr>
                <tr>
                    <th>Submitted At</th>
                    <td>{{ $deletionRequest->created_at->toDayDateTimeString() }} (UTC)</td>
                </tr>
                <tr>
                    <th>Client IP</th>
                    <td><code>{{ $deletionRequest->ip_address ?: 'Unknown' }}</code></td>
                </tr>
            </table>

            @if ($deletionRequest->details)
                <p><strong>User Comments / Notes:</strong></p>
                <div class="details-box">{{ $deletionRequest->details }}</div>
            @endif

            <p style="font-size: 12px; color: #94a3b8; margin-top: 24px;">
                Please ensure all relevant user data across database records, S3 media buckets, and cached indexes are purged in compliance with Tsiyon Privacy Policy and regulatory requirements within 30 days.
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Tsiyon App Administration. Automated system notification.
        </div>
    </div>
</body>

</html>

