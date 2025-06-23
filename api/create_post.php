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
    $user_id = $_SESSION['user_id'];
    $content = $_POST['content'];

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Post content cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $content);

    if ($stmt->execute()) {
        $post_id = $conn->insert_id;
        
        // Get all friends of the user to notify them
        $friends_stmt = $conn->prepare("
            SELECT u.id 
            FROM users u 
            JOIN friends f ON (u.id = f.user_one OR u.id = f.user_two) 
            WHERE (f.user_one = ? OR f.user_two = ?) AND f.status = 1 AND u.id != ?
        ");
        $friends_stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $friends_stmt->execute();
        $friends_result = $friends_stmt->get_result();
        
        // Create notifications for all friends
        while ($friend = $friends_result->fetch_assoc()) {
            createNotification($conn, $friend['id'], 'new_post', $user_id);
        }
        
        echo json_encode(['success' => true, 'post_id' => $post_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create post.']);
    }
}
?> 