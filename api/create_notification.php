<?php
// Helper function to create notifications
function createNotification($conn, $user_id, $type, $source_id) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, source_id) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $user_id, $type, $source_id);
    return $stmt->execute();
}
?> 