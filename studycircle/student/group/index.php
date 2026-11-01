<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];

$sql = "SELECT
            sg.group_id,
            sg.group_name,
            sg.description,
            sg.creator_id,
            s.subject_name,
            u.full_name AS creator_name,
            EXISTS (
                SELECT 1
                FROM group_members gm
                WHERE gm.group_id = sg.group_id
                  AND gm.user_id = ?
            ) AS is_member
        FROM study_groups sg
        LEFT JOIN subjects s ON sg.subject_id = s.subject_id
        LEFT JOIN users u ON sg.creator_id = u.user_id
        ORDER BY sg.group_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Study Groups | StudyCircle</title>

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
            margin-right: 18px;
            font-size: 14px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            flex: 1;
            width: 100%;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h2 {
            margin: 0;
            color: #1769aa;
        }

        .group-card {
            background: white;
            padding: 22px;
            margin-bottom: 18px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .group-card h3 {
            margin-top: 0;
            color: #1769aa;
            overflow-wrap: anywhere;
        }

        .info {
            line-height: 1.7;
            color: #475569;
            overflow-wrap: anywhere;
        }

        .description {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: #1769aa;
            color: white;
        }

        .btn-edit {
            background: #e0f2fe;
            color: #075985;
        }

        .btn-join {
            background: #16a34a;
            color: white;
        }

        .btn-leave {
            background: #dc2626;
            color: white;
        }

        .btn:hover {
            opacity: 0.88;
        }

        .actions form {
            margin: 0;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 7px;
            background: #dbeafe;
            color: #1e40af;
        }

        .empty-message {
            padding: 30px;
            background: white;
            border-radius: 12px;
            text-align: center;
            color: #64748b;
        }

        footer {
            margin-top: auto;
            padding: 18px;
            background: #e5eaf1;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 480px) {
            .page-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .page-heading .btn {
                width: 100%;
            }

            .actions {
                flex-direction: column;
            }

            .actions .btn,
            .actions form,
            .actions form button {
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
        <a href="home.php">Home</a>
       <a href="index.php">Study Groups</a>
        <a href="../session/index.php">Study Sessions</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>

    <div class="page-heading">
        <h2>Study Groups</h2>

        <a href="create.php" class="btn btn-primary">
            + Create Study Group
        </a>
    </div>

    <?php if (isset($_GET['created'])): ?>
        <div class="notice">Study group created successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice">Study group updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice">
            <?php
            $messages = [
                'joined' => 'You joined the group successfully.',
                'left' => 'You left the group successfully.',
                'already_joined' => 'You are already a member of this group.'
            ];

            echo htmlspecialchars(
                $messages[$_GET['message']] ?? 'Action completed.',
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice">
            The requested action could not be completed. Please try again.
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="group-card">

                <h3>
                    <?= htmlspecialchars(
                        $row['group_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h3>

                <div class="info">

                    <p>
                        <strong>Subject:</strong>
                        <?= htmlspecialchars(
                            $row['subject_name'] ?? 'Not specified',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        <strong>Created by:</strong>
                        <?= htmlspecialchars(
                            $row['creator_name'] ?? 'Unknown',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p><strong>Description:</strong></p>

                    <div class="description"><?= htmlspecialchars(
                        $row['description'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></div>

                </div>

                <div class="actions">

                    <?php if ((int) $row['creator_id'] === $user_id): ?>

                        <a
                            href="edit.php?id=<?= (int) $row['group_id'] ?>"
                            class="btn btn-edit"
                        >
                            Edit
                        </a>

                    <?php endif; ?>

                    <?php if ((int) $row['is_member'] === 1): ?>

                        <form
                            action="leave.php"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to leave this group?');"
                        >
                            <input
                                type="hidden"
                                name="group_id"
                                value="<?= (int) $row['group_id'] ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-leave"
                            >
                                Leave Group
                            </button>
                        </form>

                    <?php else: ?>

                        <form action="join.php" method="POST">
                            <input
                                type="hidden"
                                name="group_id"
                                value="<?= (int) $row['group_id'] ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-join"
                            >
                                Join Group
                            </button>
                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-message">
            <h3>No Study Groups Yet</h3>
            <p>Create a study group to get started.</p>
        </div>

    <?php endif; ?>

</main>

<footer>
    &copy; <?= date('Y') ?> StudyCircle | NSBM Study Community
</footer>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
