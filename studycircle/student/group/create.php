<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = trim($_POST['group_name'] ?? '');
    $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description'] ?? '');
    $creatorId = (int) $_SESSION['user_id'];

    if ($groupName === '' || !$subjectId || $description === '') {
        $message = 'Please fill in all fields correctly.';
        $messageType = 'error';
    } else {
        // Check whether the selected subject exists
        $check = $conn->prepare(
            "SELECT subject_id FROM subjects WHERE subject_id = ?"
        );

        if (!$check) {
            $message = 'Unable to check the subject. Please try again.';
            $messageType = 'error';
        } else {
            $check->bind_param("i", $subjectId);
            $check->execute();
            $subjectResult = $check->get_result();
            $subjectExists = $subjectResult->num_rows > 0;
            $check->close();

            if (!$subjectExists) {
                $message = 'That subject does not exist. Please choose a valid subject ID.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO study_groups
                    (creator_id, subject_id, group_name, description)
                    VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $message = 'Unable to create the group. Please check your database.';
                    $messageType = 'error';
                } else {
                    $stmt->bind_param(
                        "iiss",
                        $creatorId,
                        $subjectId,
                        $groupName,
                        $description
                    );

                    if ($stmt->execute()) {
                        $newGroupId = $conn->insert_id;
                        $stmt->close();

                        // Add the creator as the first group member
                        $memberStmt = $conn->prepare(
                            "INSERT INTO group_members (group_id, user_id)
                             VALUES (?, ?)"
                        );

                        if ($memberStmt) {
                            $memberStmt->bind_param(
                                "ii",
                                $newGroupId,
                                $creatorId
                            );
                            $memberStmt->execute();
                            $memberStmt->close();
                        }

                        header("Location: index.php");
                        exit;
                    } else {
                        $message = 'Could not save the group. Please try again.';
                        $messageType = 'error';
                    }

                    if ($stmt->errno !== 0) {
                        $stmt->close();
                    }
                }
            }
        }
    }
}


$subjects = $conn->query(
    "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name"
);
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Study Group - StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f5fa;
            color: #222;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #173b70;
            color: white;
            padding: 25px 8%;
        }

        header h1 {
            margin: 0 0 8px;
        }

        header p {
            margin: 0;
        }

        nav {
            margin-top: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }

        main {
            width: 90%;
            max-width: 650px;
            margin: 35px auto;
            flex: 1;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000015;
        }

        h2 {
            color: #173b70;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd3df;
            border-radius: 5px;
            font: inherit;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            background: #173b70;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            margin-top: 20px;
            font-size: 15px;
        }

        .btn:hover {
            background: #285a9c;
        }

        .cancel {
            background: #666;
            margin-left: 8px;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            background: #fdeaea;
            color: #9b2222;
        }

        .hint {
            color: #666;
            font-size: 13px;
        }

        footer {
            background: #173b70;
            color: white;
            text-align: center;
            padding: 18px;
            margin-top: auto;
        }

        footer p {
            margin: 0;
        }

        @media (max-width: 600px) {
            header {
                padding: 22px 5%;
            }

            .form-card {
                padding: 22px;
            }

            nav a {
                display: inline-block;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
<header>
    <h1>StudyCircle</h1>
    <p>Learn Together. Grow Together.</p>

    <nav>
        <a href="../../index.php">Home</a>
        <a href="index.php">Study Groups</a>
        <a href="../sessions/index.php">Study Sessions</a>
        <a href="../group/home.php">Dashboard</a>
    </nav>
</header>

<main>
    <div class="form-card">
        <h2>Create Study Group</h2>
        <p>Create a group and study together with other students.</p>

        <?php if ($message !== ''): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$subjects): ?>

            <p>Could not load subjects. Please check that the
               <code>subjects</code> table exists and contains
               <code>subject_id</code> and <code>subject_name</code>.</p>

        <?php elseif ($subjects->num_rows === 0): ?>

            <p>No subjects have been added yet. Ask your team to add subjects
               to the database before creating a group.</p>

        <?php else: ?>

            <form method="POST" action="create.php">
                <label for="group_name">Group Name</label>
                <input
                    type="text"
                    id="group_name"
                    name="group_name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($_POST['group_name'] ?? '') ?>"
                    placeholder="e.g. Programming Study Group"
                >

                <label for="subject_id">Subject</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>

                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option
                            value="<?= (int)$subject['subject_id'] ?>"
                            <?= (isset($_POST['subject_id']) &&
                                 (int)$_POST['subject_id'] === (int)$subject['subject_id'])
                                 ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($subject['subject_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="description">Group Description</label>
                <textarea
                    id="description"
                    name="description"
                    required
                    placeholder="Describe what students will learn in this group..."
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                <button class="btn" type="submit">Create Group</button>
                <a class="btn cancel" href="index.php">Cancel</a>
            </form>

        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2026 StudyCircle | NSBM Study Community</p>
</footer>
</body>
</html>
