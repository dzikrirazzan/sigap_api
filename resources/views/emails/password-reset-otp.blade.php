<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }

        .otp-box {
            background-color: #fff;
            border: 2px dashed #dc3545;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
            letter-spacing: 8px;
            margin: 10px 0;
        }

        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #dc3545;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">🚨 SIGAP UNDIP</div>
            <h2 style="color: #dc3545;">Password Reset Request</h2>
        </div>

        <p>Hello <strong>{{ $userName }}</strong>,</p>

        <p>We received a request to reset your password for your SIGAP UNDIP account. Use the OTP code below to reset your password:</p>

        <div class="otp-box">
            <p style="margin: 0; font-size: 14px; color: #666;">Your OTP Code:</p>
            <div class="otp-code">{{ $otp }}</div>
            <p style="margin: 0; font-size: 12px; color: #666;">Valid for 10 minutes</p>
        </div>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>This OTP is valid for <strong>10 minutes</strong> only</li>
                <li>Never share this OTP with anyone</li>
                <li>If you didn't request a password reset, please ignore this email</li>
                <li>Your account will remain secure</li>
            </ul>
        </div>

        <p>If you didn't request this password reset, you can safely ignore this email. Your password will not be changed.</p>

        <p>If you have any questions or need assistance, please contact our support team.</p>

        <div class="footer">
            <p><strong>SIGAP UNDIP Emergency Response System</strong></p>
            <p>Universitas Diponegoro</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>

</html>