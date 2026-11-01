<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];
$error = "";

$session_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
}

if (!$session_id || $session_id <= 0) {
    exit("Invalid session ID.");
}


$stmt = $conn->prepare(
    "SELECT ss.session_id, ss.group_id, ss.created_by,
            ss.session_date, ss.description, sg.group_name
     FROM study_sessions ss
     JOIN study_groups sg ON ss.group_id = sg.group_id
     WHERE ss.session_id = ?"
);

$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();
$stmt->close();

if (!$session) {
    exit("Study session not found.");
}


$permission = $conn->prepare(
    "SELECT ss.session_id
     FROM study_sessions ss
     JOIN study_groups sg ON ss.group_id = sg.group_id
     WHERE ss.session_id = ?
       AND (ss.created_by = ? OR sg.creator_id = ?)"
);

$permission->bind_param("iii", $session_id, $user_id, $user_id);
$permission->execute();
$can_edit = $permission->get_result()->num_rows > 0;
$permission->close();

if (!$can_edit) {
    http_response_code(403);
    exit("You do not have permission to edit this session.");
}

$session_date = date(
    'Y-m-d\TH:i',
    strtotime($session['session_date'])
);
$description = $session['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_date = trim($_POST['session_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $date_object = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $session_date
    );

    if ($session_date === '' || $description === '') {
        $error = "Please fill in all fields.";
    } elseif (
        !$date_object ||
        $date_object->format('Y-m-d\TH:i') !== $session_date
    ) {
        $error = "Please enter a valid date and time.";
    } elseif ($date_object->getTimestamp() <= time()) {
        $error = "Please select a future date and time.";
    } else {
        $formatted_date = $date_object->format('Y-m-d H:i:s');

        $update = $conn->prepare(
            "UPDATE study_sessions
             SET session_date = ?, description = ?
             WHERE session_id = ?"
        );

        $update->bind_param(
            "ssi",
            $formatted_date,
            $description,
            $session_id
        );

        if ($update->execute()) {
            $update->close();
            header("Location: index.php");
            exit();
        } else {
            $error = "Could not update the session. Please try again.";
            $update->close();
        }
    }
}
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Study Session | StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #263238;
        }

        header {
            background: #1769aa;
            color: white;
            padding: 18px 7%;
        }

        header h1 {
            margin: 0;
            font-size: 26px;
        }

        nav {
            margin-top: 12px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            flex: 1;
            width: 100%;
            max-width: 700px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-top: 0;
            color: #1769aa;
        }

        .group-name {
            color: #64748b;
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            font-family: inherit;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .error {
            padding: 12px;
            margin-bottom: 15px;
            color: #b42318;
            background: #fee4e2;
            border-radius: 7px;
        }

        .buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }

        .btn-primary {
            background: #1769aa;
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #263238;
        }

        footer {
            margin-top: auto;
            padding: 18px;
            background: #e5eaf1;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 20px;
            }

            .buttons .btn {
                width: 100%;
            }

            nav a {
                display: inline-block;
                margin: 5px 12px 5px 0;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>StudyCircle</h1>
    <nav>
        <a href="../group/home.php">Home</a>
        <a href="../groups/index.php">Study Groups</a>
        <a href="index.php">Study Sessions</a>
    </nav>
</header>

<main>
    <div class="form-card">
        <h2>Edit Study Session</h2>

        <p class="group-name">
            Group: <?= htmlspecialchars(
                $session['group_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input
                type="hidden"
                name="session_id"
                value="<?= (int) $session_id ?>"
            >

            <label for="session_date">Session Date and Time</label>
            <input
                type="datetime-local"
                id="session_date"
                name="session_date"
                value="<?= htmlspecialchars(
                    $session_date,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                min="<?= date('Y-m-d\TH:i') ?>"
                required
            >

            <label for="description">Session Description</label>
            <textarea
                id="description"
                name="description"
                required
            ><?= htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">
                    Update Session
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> StudyCircle | NSBM Study Community
</footer>

</body>
</html>