<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];
$group_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$group_id || $group_id < 1) {
    exit("Invalid group ID.");
}


$sql = "SELECT group_id, group_name, description, subject_id
        FROM study_groups
        WHERE group_id = ? AND creator_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();
$stmt->close();

if (!$group) {
    http_response_code(404);
    exit("Group not found, or you do not have permission to edit it.");
}

$error = "";


$subjects = $conn->query(
    "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject_id = filter_input(
        INPUT_POST,
        'subject_id',
        FILTER_VALIDATE_INT
    );

    if ($group_name === '' || !$subject_id || $subject_id < 1) {
        $error = "Please enter a group name and select a subject.";
    } else {
        
        $check = $conn->prepare(
            "SELECT subject_id FROM subjects WHERE subject_id = ?"
        );
        $check->bind_param("i", $subject_id);
        $check->execute();
        $subject_exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$subject_exists) {
            $error = "Please select a valid subject.";
        } else {
            $update = $conn->prepare(
                "UPDATE study_groups
                 SET group_name = ?, description = ?, subject_id = ?
                 WHERE group_id = ? AND creator_id = ?"
            );

            $update->bind_param(
                "ssiii",
                $group_name,
                $description,
                $subject_id,
                $group_id,
                $user_id
            );

            if ($update->execute()) {
                $update->close();
                $conn->close();

                header("Location: index.php?updated=1");
                exit();
            } else {
                $error = "Could not update the group. Please try again.";
                $update->close();
            }
        }
    }

    
    $group['group_name'] = $group_name;
    $group['description'] = $description;
    $group['subject_id'] = $subject_id ?: $group['subject_id'];
}
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Study Group | StudyCircle</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px 15px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #263238;
        }

        .container {
            max-width: 650px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
        }

        h1 { color: #1769aa; }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 17px;
            border: 0;
            border-radius: 6px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .cancel { background: #64748b; }

        .error {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Study Group</h1>

    <?php if ($error !== ''): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label for="group_name">Group Name</label>
        <input
            type="text"
            id="group_name"
            name="group_name"
            maxlength="255"
            required
            value="<?= htmlspecialchars(
                $group['group_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="subject_id">Subject</label>
        <select id="subject_id" name="subject_id" required>
            <option value="">Select a subject</option>

            <?php while ($subject = $subjects->fetch_assoc()): ?>
                <option
                    value="<?= (int) $subject['subject_id'] ?>"
                    <?= (int) $subject['subject_id'] ===
                        (int) $group['subject_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(
                        $subject['subject_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="description">Description</label>
        <textarea
            id="description"
            name="description"
        ><?= htmlspecialchars(
            $group['description'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>

        <button type="submit" class="btn">Save Changes</button>
        <a href="index.php" class="btn cancel">Cancel</a>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
