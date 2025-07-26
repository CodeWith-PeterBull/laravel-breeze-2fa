<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $appName }} - Verification Code</title>

    <style>
        /* Reset styles */
        body,
        table,
        td,
        p,
        a,
        li,
        blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #374151;
            line-height: 1.6;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Container styles */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
        }

        .email-body {
            padding: 40px 20px;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 30px 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        /* Typography */
        .logo {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
            margin: 0;
        }

        .tagline {
            color: #e0e7ff;
            font-size: 14px;
            margin: 8px 0 0 0;
        }

        h1 {
            color: #1f2937;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 20px 0;
            text-align: center;
        }

        p {
            margin: 0 0 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Verification code styles */
        .verification-code-container {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px dashed #0891b2;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }

        .verification-code {
            font-family: 'Courier New', Consolas, Monaco, monospace;
            font-size: 32px;
            font-weight: bold;
            color: #0c4a6e;
            letter-spacing: 8px;
            margin: 10px 0;
            text-align: center;
            display: block;
        }

        .code-label {
            color: #0891b2;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .code-expiry {
            color: #ef4444;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
        }

        /* Security notice */
        .security-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }

        .security-notice h3 {
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .security-notice p {
            color: #92400e;
            font-size: 14px;
            margin: 0;
        }

        /* Button styles */
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }

        .button:hover {
            opacity: 0.9;
        }

        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }

            .email-header,
            .email-body,
            .email-footer {
                padding: 20px 15px !important;
            }

            .verification-code {
                font-size: 28px !important;
                letter-spacing: 6px !important;
            }

            h1 {
                font-size: 20px !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #1f2937 !important;
            }

            .email-body {
                color: #e5e7eb !important;
            }

            h1 {
                color: #f9fafb !important;
            }

            p {
                color: #d1d5db !important;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding: 20px 0;">
                <div class="email-container">

                    <!-- Header -->
                    <div class="email-header">
                        <h2 class="logo">{{ $appName }}</h2>
                        <p class="tagline">Secure Authentication</p>
                    </div>

                    <!-- Body -->
                    <div class="email-body">
                        <h1>Verification Code</h1>

                        <p>Hello {{ $userName }},</p>

                        <p>You requested a verification code for two-factor authentication. Please use the code below to
                            complete your login:</p>

                        <!-- Verification Code -->
                        <div class="verification-code-container">
                            <div class="code-label">Your Verification Code</div>
                            <div class="verification-code">{{ $formattedCode }}</div>
                            <div class="code-expiry">⏰ Expires in {{ $expiryMinutes }} minutes</div>
                        </div>

                        <p>If you're having trouble, you can copy and paste this code:
                            <strong>{{ $code }}</strong>
                        </p>

                        <!-- Security Notice -->
                        <div class="security-notice">
                            <h3>🔒 Security Notice</h3>
                            <p>
                                Never share this code with anyone. {{ $appName }} will never ask for your
                                verification code via phone, email, or any other method.
                            </p>
                        </div>

                        <p>If you didn't request this verification code, please ignore this email. Your account remains
                            secure.</p>

                        <p>
                            If you're having trouble with two-factor authentication, you can visit our
                            <a href="{{ config('app.url') }}/help" style="color: #667eea;">help center</a>
                            or contact support.
                        </p>

                        <p>
                            Best regards,<br>
                            The {{ $appName }} Team
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="email-footer">
                        <p>
                            This email was sent to {{ $userEmail }}.<br>
                            If you no longer wish to receive these emails, you can
                            <a href="{{ config('app.url') }}/account/settings" style="color: #6b7280;">manage your
                                preferences</a>.
                        </p>

                        <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                            © {{ date('Y') }} {{ $appName }}. All rights reserved.<br>
                            This is an automated message, please do not reply to this email.
                        </p>

                        <div style="margin-top: 20px;">
                            <a href="{{ config('app.url') }}"
                                style="color: #6b7280; text-decoration: none; margin: 0 10px;">Website</a>
                            <a href="{{ config('app.url') }}/privacy"
                                style="color: #6b7280; text-decoration: none; margin: 0 10px;">Privacy</a>
                            <a href="{{ config('app.url') }}/terms"
                                style="color: #6b7280; text-decoration: none; margin: 0 10px;">Terms</a>
                            <a href="{{ config('app.url') }}/support"
                                style="color: #6b7280; text-decoration: none; margin: 0 10px;">Support</a>
                        </div>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</body>

</html>
