<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../../config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {

        $message = 'Please enter a valid name, email and password with at least 8 characters.';

    } else {

    
        $check = $conn->prepare(
            'SELECT user_id FROM users WHERE email = ?'
        );

        if (!$check) {

            $message = 'Database error: ' . $conn->error;

        } else {

            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {

                $message = 'This email is already registered. Please use another email.';

            } else {

                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'student';

                $stmt = $conn->prepare(
                    'INSERT INTO users (full_name, email, password, role)
                     VALUES (?, ?, ?, ?)'
                );

                if (!$stmt) {

                    $message = 'Database error: ' . $conn->error;

                } else {

                    $stmt->bind_param(
                        'ssss',
                        $full_name,
                        $email,
                        $password_hash,
                        $role
                    );

                    if ($stmt->execute()) {
                        $success = true;
                        $message = 'Registration successful! Your account has been created.';
                    } else {
                        $message = 'Registration failed: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5fb;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 420px;
            margin: 30px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.10);
        }

        h1 {
            text-align: center;
            color: #234b91;
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 16px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            background: #234b91;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #17366d;
        }

        .message {
            padding: 12px;
            background: #fff3cd;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">

        <h1>Create Account</h1>

        <?php if ($message !== ''): ?>
            <p class="message <?= $success ? 'success' : '' ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (!$success): ?>

            <form method="POST" action="register.php">

                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    required
                >

                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    required
                >

                <label for="password">Password (minimum 8 characters)</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

                <button type="submit" name="register">Register</button>

            </form>

        <?php else: ?>

            <p class="login-link">
                <a href="login.php">Continue to Login</a>
            </p>

        <?php endif; ?>

        <p class="login-link">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

    </div>
</body>
</html>