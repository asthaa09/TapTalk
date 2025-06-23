<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];

    // Check if the post belongs to the current user
    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own posts.']);
        exit;
    }

    $delete_stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $post_id, $user_id);

    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete post.']);
    }
}
?> 