<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background-color: #2C9139; /* Company primary color */
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .email-body {
            padding: 20px;
            line-height: 1.6;
        }
        .verification-code {
            font-size: 28px;
            font-weight: bold;
            color: #2C9139; /* Company primary color */
            text-align: center;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
        .email-footer a {
            color: #2C9139; /* Company primary color */
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1 style="color: white">Verification Code</h1>
        </div>
        <div class="email-body">
            <p>Dear User,</p>
            <p>Thank you for using <strong>ShopittPlus</strong>. To complete your action, please use the verification code below:</p>
            <div class="verification-code">
                {{ $verification_code }}
            </div>
            <p>This code is valid for the next {{ $expiry_minutes }} minutes. If you did not request this code, please ignore this email.</p>
            <p>Thank you,<br>The ShopittPlus Team</p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} ShopittPlus. All rights reserved.</p>
        </div>
    </div>
</body>
</html>