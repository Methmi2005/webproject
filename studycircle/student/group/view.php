<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$userId = (int) $_SESSION['user_id'];
$groupId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$groupId || $groupId < 1) {
    header("Location: index.php");
    exit;
}


$stmt = $conn->prepare(
    "SELECT sg.group_id, sg.group_name, sg.description,
            sg.subject_id, sg.creator_id,
            u.full_name AS creator_name
     FROM study_groups sg
     LEFT JOIN users u ON sg.creator_id = u.user_id
     WHERE sg.group_id = ?"
);

$stmt->bind_param("i", $groupId);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$group) {
    http_response_code(404);
    $notFound = true;
    $isMember = false;
    $subjectName = '';
    $members = null;
} else {
    $notFound = false;


    $stmt = $conn->prepare(
        "SELECT user_id FROM group_members
         WHERE group_id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $groupId, $userId);
    $stmt->execute();
    $isMember = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    
    $subjectName = 'Subject ID: ' . $group['subject_id'];

    $stmt = $conn->prepare(
        "SELECT subject_name FROM subjects WHERE subject_id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $group['subject_id']);
        $stmt->execute();
        $subjectResult = $stmt->get_result();

        if ($subject = $subjectResult->fetch_assoc()) {
            $subjectName = $subject['subject_name'];
        }

        $stmt->close();
    }

    
    $stmt = $conn->prepare(
        "SELECT u.user_id, u.full_name
         FROM group_members gm
         JOIN users u ON gm.user_id = u.user_id
         WHERE gm.group_id = ?
         ORDER BY gm.joined_at ASC"
    );

    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $members = $stmt->get_result();
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Details - StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
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
            width: 85%;
            max-width: 1000px;
            margin: 35px auto;
            flex: 1;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000015;
        }

        h2, h3 {
            color: #173b70;
        }

        .subject {
            display: inline-block;
            background: #e3edff;
            color: #173b70;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13px;
        }

        .description {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            background: #173b70;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px 5px 5px 0;
        }

        .btn:hover {
            background: #285a9c;
        }

        .back-btn {
            background: #666;
        }

        .leave-btn {
            background: #a12626;
        }

        .leave-btn:hover {
            background: #801b1b;
        }

        .member {
            padding: 14px 0;
            border-bottom: 1px solid #e5e8ee;
        }

        .member:last-child {
            border-bottom: none;
        }

        .empty {
            color: #666;
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

            main {
                width: 92%;
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

    <?php if ($notFound): ?>

        <div class="card">
            <h2>Group Not Found</h2>
            <p>This group does not exist or may have been removed.</p>
            <a class="btn back-btn" href="index.php">Back to Groups</a>
        </div>

    <?php else: ?>

        <div class="card">
            <span class="subject">
                <?= htmlspecialchars($subjectName) ?>
            </span>

            <h2>
                <?= htmlspecialchars($group['group_name']) ?>
            </h2>

            <p class="description">
                <?= nl2br(htmlspecialchars($group['description'] ?? '')) ?>
            </p>

            <p>
                <strong>Created by:</strong>
                <?= htmlspecialchars($group['creator_name'] ?? 'Student') ?>
            </p>

            <?php if ((int)$group['creator_id'] === $userId): ?>

                <p>You created this group.</p>

            <?php elseif ($isMember): ?>

                <p>You are a member of this study group.</p>

                <form method="POST" action="leave.php"
                      onsubmit="return confirm('Are you sure you want to leave this group?');">
                    <input type="hidden" name="group_id"
                           value="<?= (int)$groupId ?>">

                    <button class="btn leave-btn" type="submit">
                        Leave Group
                    </button>
                </form>

            <?php else: ?>

                <form method="POST" action="join.php">
                    <input type="hidden" name="group_id"
                           value="<?= (int)$groupId ?>">

                    <button class="btn" type="submit">
                        Join Group
                    </button>
                </form>

            <?php endif; ?>

            <?php if ($isMember || (int)$group['creator_id'] === $userId): ?>
                <a class="btn" href="../sessions/index.php?group_id=<?= (int)$groupId ?>">
                    View Study Sessions
                </a>
            <?php endif; ?>

            <a class="btn back-btn" href="index.php">
                Back to Groups
            </a>
        </div>

        <div class="card">
            <h3>Group Members</h3>

            <?php if ($members && $members->num_rows > 0): ?>

                <?php while ($member = $members->fetch_assoc()): ?>

                    <div class="member">
                        <strong>
                            <?= htmlspecialchars($member['full_name']) ?>
                        </strong>

                        <?php if ((int)$member['user_id'] === (int)$group['creator_id']): ?>
                            <span class="subject">Group Creator</span>
                        <?php endif; ?>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="empty">
                    No members have joined this group yet.
                </p>

            <?php endif; ?>

        </div>

        <?php
        if (isset($stmt)) {
            $stmt->close();
        }
        ?>

    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2026 StudyCircle | NSBM Study Community</p>
</footer>

</body>
</html>
