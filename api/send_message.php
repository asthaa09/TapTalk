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
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    if (empty($receiver_id)) {
        echo json_encode(['success' => false, 'message' => 'Receiver ID is required.']);
        exit;
    }

    // Check if the two users are friends
    $friend_check = $conn->prepare("
        SELECT id FROM friends 
        WHERE ((user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)) 
        AND status = 1
    ");
    $friend_check->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $friend_check->execute();
    $friend_result = $friend_check->get_result();

    if ($friend_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'You can only send messages to your friends.']);
        exit;
    }

    // Verify receiver exists
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->bind_param("i", $receiver_id);
    $user_check->execute();
    $user_result = $user_check->get_result();

    if ($user_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Receiver not found.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);

    if ($stmt->execute()) {
        // Create notification for the receiver
        createNotification($conn, $receiver_id, 'new_message', $sender_id);
        echo json_encode(['success' => true, 'message_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 