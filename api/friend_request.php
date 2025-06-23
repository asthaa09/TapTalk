<?php
session_start();
require '../db.php';
require 'create_notification.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_user_id = $_SESSION['user_id'];
    $friend_id = $_POST['friend_id'];

    // Check if a request already exists
    $check_stmt = $conn->prepare("SELECT id FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
    $check_stmt->bind_param("iiii", $current_user_id, $friend_id, $friend_id, $current_user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Friend request already sent or you are already friends.']);
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO friends (user_one, user_two, action_user_id) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $current_user_id, $friend_id, $current_user_id);
        
        if ($insert_stmt->execute()) {
            // Create notification for the friend
            createNotification($conn, $friend_id, 'friend_request', $current_user_id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send request.']);
        }
    }
}
?> 