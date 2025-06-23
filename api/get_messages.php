<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$chat_user_id = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
$last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$chat_user_id) {
    echo json_encode(['success' => false, 'message' => 'No chat partner specified.']);
    exit;
}

$sql = "SELECT * FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id, $last_message_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);
?> 