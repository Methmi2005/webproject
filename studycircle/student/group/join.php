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

// Check whether the group exists.
$stmt = $conn->prepare(
    "SELECT group_id, creator_id
     FROM study_groups
     WHERE group_id = ?"
);

$stmt->bind_param("i", $group_id);
$stmt->execute();

$group = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$group) {
    $conn->close();
    header("Location: index.php?error=group_not_found");
    exit();
}

// A group creator does not need to join their own group.
if ((int) $group['creator_id'] === $user_id) {
    $conn->close();
    header("Location: index.php?error=own_group");
    exit();
}

// Check whether the user has already joined.
$stmt = $conn->prepare(
    "SELECT group_id
     FROM group_members
     WHERE group_id = ? AND user_id = ?"
);

$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

$already_joined = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($already_joined) {
    $conn->close();
    header("Location: index.php?message=already_joined");
    exit();
}

// Add the user to the group.
$stmt = $conn->prepare(
    "INSERT INTO group_members (group_id, user_id)
     VALUES (?, ?)"
);

$stmt->bind_param("ii", $group_id, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: index.php?message=joined");
    exit();
}

$stmt->close();
$conn->close();

header("Location: index.php?error=join_failed");
exit();
?>