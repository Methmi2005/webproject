<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

if (!$group_id || $group_id < 1) {
    header("Location: index.php?error=invalid_group");
    exit();
}


$check = $conn->prepare(
    "SELECT group_id, creator_id
     FROM study_groups
     WHERE group_id = ?"
);
$check->bind_param("i", $group_id);
$check->execute();
$group = $check->get_result()->fetch_assoc();
$check->close();

if (!$group) {
    $conn->close();
    header("Location: index.php?error=group_not_found");
    exit();
}


if ((int) $group['creator_id'] === $user_id) {
    $conn->close();
    header("Location: index.php?error=creator_cannot_leave");
    exit();
}


$stmt = $conn->prepare(
    "DELETE FROM group_members
     WHERE group_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

$left = $stmt->affected_rows > 0;

$stmt->close();
$conn->close();

if ($left) {
    header("Location: index.php?message=left");
} else {
    header("Location: index.php?error=not_a_member");
}
exit();
?>
