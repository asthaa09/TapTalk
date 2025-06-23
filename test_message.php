<?php
session_start();
require 'db.php';

echo "<h2>Message Sending Test</h2>";

// Check if we have users
$users_result = $conn->query("SELECT id, name FROM users LIMIT 5");
if ($users_result->num_rows < 2) {
    echo "<p style='color: red;'>❌ Need at least 2 users to test messaging. Please register some users first.</p>";
    exit;
}

$users = $users_result->fetch_all(MYSQLI_ASSOC);
echo "<p>Found " . count($users) . " users:</p>";
foreach ($users as $user) {
    echo "<p>- " . $user['name'] . " (ID: " . $user['id'] . ")</p>";
}

// Check if we have any friend relationships
$friends_result = $conn->query("SELECT f.*, u1.name as user1_name, u2.name as user2_name 
                                FROM friends f 
                                JOIN users u1 ON f.user_one = u1.id 
                                JOIN users u2 ON f.user_two = u2.id 
                                WHERE f.status = 1 
                                LIMIT 5");
if ($friends_result->num_rows == 0) {
    echo "<p style='color: red;'>❌ No friend relationships found. Users need to be friends to send messages.</p>";
    echo "<p>To test messaging:</p>";
    echo "<ol>";
    echo "<li>Register at least 2 users</li>";
    echo "<li>Login as one user and send friend requests to others</li>";
    echo "<li>Login as the other user and accept the friend request</li>";
    echo "<li>Then try sending messages</li>";
    echo "</ol>";
    exit;
}

$friends = $friends_result->fetch_all(MYSQLI_ASSOC);
echo "<p>Found " . count($friends) . " friend relationships:</p>";
foreach ($friends as $friend) {
    echo "<p>- " . $friend['user1_name'] . " ↔ " . $friend['user2_name'] . "</p>";
}

// Test sending a message
if (isset($_POST['test_message'])) {
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    
    // Check if they are friends
    $friend_check = $conn->prepare("
        SELECT id FROM friends 
        WHERE ((user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)) 
        AND status = 1
    ");
    $friend_check->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $friend_check->execute();
    $friend_result = $friend_check->get_result();
    
    if ($friend_result->num_rows == 0) {
        echo "<p style='color: red;'>❌ Users are not friends. Cannot send message.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Message sent successfully! Message ID: " . $conn->insert_id . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send message: " . $conn->error . "</p>";
        }
    }
}

// Show existing messages
echo "<h3>Recent Messages:</h3>";
$messages_result = $conn->query("
    SELECT m.*, u1.name as sender_name, u2.name as receiver_name 
    FROM messages m 
    JOIN users u1 ON m.sender_id = u1.id 
    JOIN users u2 ON m.receiver_id = u2.id 
    ORDER BY m.created_at DESC 
    LIMIT 10
");

if ($messages_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>From</th><th>To</th><th>Message</th><th>Time</th></tr>";
    while ($message = $messages_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $message['sender_name'] . "</td>";
        echo "<td>" . $message['receiver_name'] . "</td>";
        echo "<td>" . htmlspecialchars($message['message']) . "</td>";
        echo "<td>" . $message['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No messages found.</p>";
}

// Test form
echo "<h3>Test Message Sending:</h3>";
echo "<form method='POST'>";
echo "<p>Sender: <select name='sender_id'>";
foreach ($users as $user) {
    echo "<option value='" . $user['id'] . "'>" . $user['name'] . "</option>";
}
echo "</select></p>";

echo "<p>Receiver: <select name='receiver_id'>";
foreach ($users as $user) {
    echo "<option value='" . $user['id'] . "'>" . $user['name'] . "</option>";
}
echo "</select></p>";

echo "<p>Message: <input type='text' name='message' value='Test message' required></p>";
echo "<p><input type='submit' name='test_message' value='Send Test Message'></p>";
echo "</form>";

$conn->close();
?> 