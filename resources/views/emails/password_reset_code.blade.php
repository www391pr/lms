<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Reset Password Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .email-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .email-header h1 {
            font-size: 28px;
            color: #4CAF50;
        }

        .email-body {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            text-align: center;
        }

        .email-body p {
            margin: 15px 0;
        }

        .pin-code {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
            background-color: #f1f8f4;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }

        .footer {
            font-size: 14px;
            color: #888;
            text-align: center;
            margin-top: 30px;
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }

        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        <h1>Your Reset Password Code</h1>
    </div>

    <div class="email-body">
        <p>You have requested to reset your password. Please use the code below to complete the process:</p>
        <div class="pin-code">{{ $pinCode }}</div>
        <p>This code will expire in 15 minutes.</p>
        <p>If you did not request a password reset, please ignore this email.</p>
    </div>

    <div class="footer">
        <p>&copy; 2025 {{ config('app.name') }} . All rights reserved.</p>
        <p>If you have any questions, feel free to <a href="mailto:aboodoth75@gmail.com">contact support</a>.</p>
    </div>
</div>
</body>
</html>
