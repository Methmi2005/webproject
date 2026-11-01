<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['full_name'] ?? 'Student';
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5fb;
            color: #263238;
        }

        header {
            background: #234b91;
            color: white;
            text-align: center;
            padding: 25px 15px;
        }

        .container {
            max-width: 900px;
            margin: 45px auto;
            padding: 20px;
        }

        .welcome {
            background: white;
            padding: 35px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
        }

        .links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            display: block;
            width: 260px;
            padding: 25px;
            border-radius: 10px;
            background: #f8faff;
            border: 1px solid #dbe5f5;
            color: #234b91;
            text-decoration: none;
            transition: .2s;
        }

        .card:hover {
            background: #e5edff;
            transform: translateY(-3px);
        }

        .card h3 {
            margin-top: 0;
        }

        .logout {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 22px;
            background: #dc2626;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .links {
                flex-direction: column;
                align-items: center;
            }

            .card {
                width: 100%;
                max-width: 320px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>StudyCircle</h1>
    <p>NSBM Student Study Community</p>
</header>

<div class="container">
    <div class="welcome">

        <h2>
            Hello, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!
        </h2>

        <p>Login successful! Welcome to StudyCircle.</p>
        <p>Select an option below to continue.</p>

        <div class="links">

            <a class="card" href="../group/index.php">
                <h3>Study Groups</h3>
                <p>Create, view and join study groups.</p>
            </a>

            <a class="card" href="../session/index.php">
                <h3>Study Sessions</h3>
                <p>Schedule and manage study sessions.</p>
            </a>

        </div>

        <a class="logout" href="logout.php">Logout</a>

    </div>
</div>

</body>
</html>