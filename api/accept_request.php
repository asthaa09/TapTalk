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

    $stmt = $conn->prepare("UPDATE friends 
                            SET status = 1, action_user_id = ? 
                            WHERE (user_one = ? AND user_two = ?) AND status = 0");
    $stmt->bind_param("iii", $current_user_id, $friend_id, $current_user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Create notification for the person who sent the request
            createNotification($conn, $friend_id, 'friend_accept', $current_user_id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No pending request found or you are not the receiver.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept request.']);
    }
}
?> 