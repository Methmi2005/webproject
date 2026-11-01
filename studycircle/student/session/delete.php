<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../group/login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);

if (!$session_id || $session_id < 1) {
    header("Location: index.php?error=invalid_session");
    exit();
}


$sql = "DELETE ss
        FROM study_sessions ss
        INNER JOIN study_groups sg
            ON sg.group_id = ss.group_id
        WHERE ss.session_id = ?
          AND (ss.created_by = ? OR sg.creator_id = ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $session_id, $user_id, $user_id);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: index.php?error=delete_failed");
    exit();
}

if ($stmt->affected_rows > 0) {
    $stmt->close();
    $conn->close();

    header("Location: index.php?message=deleted");
    exit();
}

$stmt->close();
$conn->close();

header("Location: index.php?error=delete_failed");
exit();
?>
