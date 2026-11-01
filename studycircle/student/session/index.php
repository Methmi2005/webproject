<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];


$sql = "SELECT
            ss.session_id,
            ss.group_id,
            ss.session_date,
            ss.description,
            ss.created_by,
            sg.group_name,
            u.full_name AS creator_name,
            CASE
                WHEN ss.created_by = ? OR sg.creator_id = ?
                THEN 1
                ELSE 0
            END AS can_manage
        FROM study_sessions ss
        INNER JOIN study_groups sg
            ON ss.group_id = sg.group_id
        INNER JOIN users u
            ON ss.created_by = u.user_id
        WHERE sg.creator_id = ?
           OR EXISTS (
                SELECT 1
                FROM group_members gm
                WHERE gm.group_id = sg.group_id
                  AND gm.user_id = ?
           )
        ORDER BY ss.session_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

$successMessages = [
    'created' => 'Study session created successfully!',
    'updated' => 'Study session updated successfully!',
    'deleted' => 'Study session cancelled successfully!'
];

$errorMessages = [
    'invalid_session' => 'Invalid session selected.',
    'delete_failed' => 'Unable to cancel the session. It may not exist or you may not have permission.',
    'invalid_id' => 'Invalid session ID.'
];
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Study Sessions | StudyCircle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #263238;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0 0 8px;
            color: #1769aa;
        }

        .subtitle {
            color: #64748b;
            margin: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: #1769aa;
        }

        .btn-secondary {
            background: #64748b;
        }

        .btn-edit {
            background: #d97706;
        }

        .btn-delete {
            background: #dc2626;
        }

        .message {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .session-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .session-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
            overflow-wrap: anywhere;
        }

        .session-card h2 {
            margin: 0 0 12px;
            font-size: 21px;
            color: #1769aa;
        }

        .group-name {
            font-weight: bold;
            margin-bottom: 12px;
        }

        .session-date {
            color: #334155;
            margin-bottom: 12px;
        }

        .description {
            line-height: 1.6;
            white-space: pre-wrap;
            margin-bottom: 18px;
        }

        .creator {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            align-items: center;
        }

        .card-actions form {
            margin: 0;
        }

        .empty-state {
            background: white;
            padding: 35px 20px;
            border-radius: 10px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .header {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div>
            <h1>Study Sessions</h1>
            <p class="subtitle">
                View and manage sessions for your study groups.
            </p>
        </div>

        <div class="actions">
            <a href="create.php" class="btn btn-primary">
                + Create Session
            </a>

            <a href="../group/index.php" class="btn btn-secondary">
                Study Groups
            </a>

            <a href="../group/home.php" class="btn btn-secondary">
                Home
            </a>
        </div>
    </div>

    <?php if (isset($successMessages[$message])): ?>
        <div class="message success">
            <?= h($successMessages[$message]) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessages[$error])): ?>
        <div class="message error">
            <?= h($errorMessages[$error]) ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <div class="session-list">

            <?php while ($row = $result->fetch_assoc()): ?>

                <div class="session-card">

                    <h2>Study Session</h2>

                    <div class="group-name">
                        Group: <?= h($row['group_name']) ?>
                    </div>

                    <div class="session-date">
                        <strong>Date & Time:</strong><br>

                        <?= h(
                            date(
                                'd M Y, h:i A',
                                strtotime($row['session_date'])
                            )
                        ) ?>
                    </div>

                    <div class="description">
                        <?= h($row['description']) ?>
                    </div>

                    <div class="creator">
                        Created by: <?= h($row['creator_name']) ?>
                    </div>

                    <?php if ((int) $row['can_manage'] === 1): ?>
                        <div class="card-actions">

                            <a
                                href="edit.php?id=<?= (int) $row['session_id'] ?>"
                                class="btn btn-edit"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="delete.php"
                                onsubmit="return confirm('Are you sure you want to cancel this session?');"
                            >
                                <input
                                    type="hidden"
                                    name="session_id"
                                    value="<?= (int) $row['session_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-delete"
                                >
                                    Cancel Session
                                </button>
                            </form>

                        </div>
                    <?php endif; ?>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <h2>No Study Sessions Yet</h2>

            <p>
                There are no sessions for your study groups yet.
                Create a session to get started.
            </p>

            <a href="create.php" class="btn btn-primary">
                Create Your First Session
            </a>
        </div>

    <?php endif; ?>

</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
