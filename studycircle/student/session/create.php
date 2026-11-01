<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Student';

$sql = "SELECT ss.session_id, ss.group_id, ss.created_by,
               ss.session_date, ss.description,
               sg.group_name,
               u.full_name AS creator_name,
               CASE
                   WHEN ss.created_by = ? OR sg.creator_id = ?
                   THEN 1 ELSE 0
               END AS can_manage
        FROM study_sessions ss
        INNER JOIN study_groups sg ON ss.group_id = sg.group_id
        LEFT JOIN users u ON ss.created_by = u.user_id
        WHERE EXISTS (
            SELECT 1
            FROM group_members gm
            WHERE gm.group_id = sg.group_id
              AND gm.user_id = ?
        )
        OR sg.creator_id = ?
        ORDER BY ss.session_date ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Study Sessions SQL Error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Sessions | StudyCircle</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5fb;
            color: #263238;
        }
        header {
            background: #234b91;
            color: white;
            padding: 20px;
        }
        .nav {
            max-width: 1100px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        header a { color: white; text-decoration: none; margin-right: 12px; }
        main { max-width: 1100px; margin: 30px auto; padding: 0 18px; }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #234b91;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            border: 0;
            cursor: pointer;
            font-size: 14px;
        }
        .danger { background: #c62828; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }
        .card h3 { margin-top: 0; color: #234b91; }
        .muted { color: #667085; line-height: 1.6; }
        .empty { background: white; padding: 25px; border-radius: 10px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
        @media(max-width: 600px) {
            .nav { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<header>
    <div class="nav">
        <div>
            <h2>StudyCircle</h2>
            <span>Study Sessions</span>
        </div>
        <nav>
            <a href="../group/home.php">Home</a>
            <a href="../groups/index.php">Study Groups</a>
            <a href="../group/logout.php">Logout</a>
        </nav>
    </div>
</header>

<main>
    <div class="top">
        <div>
            <h1>Study Sessions</h1>
            <p>Welcome, <?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>!</p>
        </div>
        <a class="btn" href="create.php">+ Schedule Session</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid">
            <?php while ($session = $result->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= htmlspecialchars($session['group_name']) ?></h3>

                    <p>
                        <strong>Date &amp; Time:</strong><br>
                        <?= htmlspecialchars(
                            date('d M Y, h:i A', strtotime($session['session_date']))
                        ) ?>
                    </p>

                    <p>
                        <?= nl2br(htmlspecialchars($session['description'])) ?>
                    </p>

                    <p class="muted">
                        Created by:
                        <?= htmlspecialchars($session['creator_name'] ?? 'Unknown') ?>
                    </p>

                    <?php if ((int) $session['can_manage'] === 1): ?>
                        <div class="actions">
                            <a class="btn"
                               href="edit.php?id=<?= (int) $session['session_id'] ?>">
                                Edit
                            </a>

                            <form action="delete.php" method="POST"
                                  onsubmit="return confirm('Cancel this study session?');">
                                <input type="hidden" name="session_id"
                                       value="<?= (int) $session['session_id'] ?>">
                                <button type="submit" class="btn danger">
                                    Cancel Session
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty">
            <h3>No study sessions found</h3>
            <p>Join a study group or create a session for a group you belong to.</p>
            <a class="btn" href="../groups/index.php">Browse Study Groups</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
<?php
$stmt->close();
