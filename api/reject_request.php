<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_user_id = $_SESSION['user_id'];
    $friend_id = $_POST['friend_id'];

    // The user rejecting the request is user_two
    $stmt = $conn->prepare("DELETE FROM friends 
                            WHERE (user_one = ? AND user_two = ?) AND status = 0");
    $stmt->bind_param("ii", $friend_id, $current_user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No pending request found to reject.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject request.']);
    }
}
?> 