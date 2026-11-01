<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../../config/database.php';

$message = '';

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Registration successful! Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {

        $message = 'Please enter your email and password.';

    } else {

        $stmt = $conn->prepare(
            'SELECT user_id, full_name, email, password, role
             FROM users
             WHERE email = ?'
        );

        if (!$stmt) {
            $message = 'Database error: ' . $conn->error;
        } else {

            $stmt->bind_param('s', $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {

                 session_regenerate_id(true);

                 $_SESSION['user_id'] = $user['user_id'];
                 $_SESSION['full_name'] = $user['full_name'];
                 $_SESSION['email'] = $user['email'];
                 $_SESSION['role'] = $user['role'];

                 header('Location: home.php');
                 exit;

            } else {
            $message = 'Incorrect email or password.';
        }
            $stmt->close();
        }
    }
}
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StudyCircle</title>

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
            margin: 40px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.10);
        }

        h1 {
            text-align: center;
            color: #234b91;
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

        .message {
            padding: 12px;
            background: #fff3cd;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">

        <h1>Login to StudyCircle</h1>

        <?php if ($message !== ''): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">Login</button>

        </form>

        <p class="register-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

    </div>
</body>
</html>
