
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body>
    <h1>Hello, {{ $user->name }}!</h1>
    <p>We received a request to reset your password. Please click the button below to reset it:</p>
    <p>
        <a href="{{ $actionUrl }}" style="background-color: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Reset Password
        </a>
    </p>
    <p>If you didn't request this, just ignore this email.</p>
</body>
</html>
