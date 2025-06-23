<?php
session_start();
require 'db.php';

echo "<h1>🔍 Message Sending Debug</h1>";

// 1. Check database connection
echo "<h2>1. Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// 2. Check if messages table exists and has correct structure
echo "<h2>2. Messages Table Structure</h2>";
$result = $conn->query("DESCRIBE messages");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Messages table does not exist!</p>";
    exit;
}

// 3. Check users
echo "<h2>3. Users</h2>";
$users_result = $conn->query("SELECT id, name, email FROM users");
if ($users_result->num_rows == 0) {
    echo "<p style='color: red;'>❌ No users found! Please register users first.</p>";
} else {
    echo "<p>Found " . $users_result->num_rows . " users:</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($user = $users_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check friends
echo "<h2>4. Friend Relationships</h2>";
$friends_result = $conn->query("
    SELECT f.*, u1.name as user1_name, u2.name as user2_name 
    FROM friends f 
    JOIN users u1 ON f.user_one = u1.id 
    JOIN users u2 ON f.user_two = u2.id
");
if ($friends_result->num_rows == 0) {
    echo "<p style='color: red;'>❌ No friend relationships found!</p>";
} else {
    echo "<p>Found " . $friends_result->num_rows . " friend relationships:</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User 1</th><th>User 2</th><th>Status</th><th>Action User</th></tr>";
    while ($friend = $friends_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $friend['user1_name'] . " (ID: " . $friend['user_one'] . ")</td>";
        echo "<td>" . $friend['user2_name'] . " (ID: " . $friend['user_two'] . ")</td>";
        echo "<td>" . ($friend['status'] ? 'Accepted' : 'Pending') . "</td>";
        echo "<td>" . $friend['action_user_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Check existing messages
echo "<h2>5. Existing Messages</h2>";
$messages_result = $conn->query("
    SELECT m.*, u1.name as sender_name, u2.name as receiver_name 
    FROM messages m 
    JOIN users u1 ON m.sender_id = u1.id 
    JOIN users u2 ON m.receiver_id = u2.id 
    ORDER BY m.created_at DESC 
    LIMIT 10
");
if ($messages_result->num_rows == 0) {
    echo "<p>No messages found.</p>";
} else {
    echo "<p>Found " . $messages_result->num_rows . " messages:</p>";
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
}

// 6. Test message insertion directly
echo "<h2>6. Test Message Insertion</h2>";
if (isset($_POST['test_insert'])) {
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    
    echo "<p>Testing insertion with:</p>";
    echo "<ul>";
    echo "<li>Sender ID: $sender_id</li>";
    echo "<li>Receiver ID: $receiver_id</li>";
    echo "<li>Message: $message</li>";
    echo "</ul>";
    
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
        echo "<p style='color: red;'>❌ Users are not friends!</p>";
    } else {
        echo "<p style='color: green;'>✅ Users are friends</p>";
        
        // Try to insert message
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Message inserted successfully! ID: " . $conn->insert_id . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to insert message: " . $conn->error . "</p>";
        }
    }
}

// 7. Test form
echo "<h2>7. Manual Test</h2>";
$users_result = $conn->query("SELECT id, name FROM users LIMIT 5");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

if (count($users) >= 2) {
    echo "<form method='POST' style='background: #f0f0f0; padding: 20px; border-radius: 10px;'>";
    echo "<p><strong>Sender:</strong> <select name='sender_id' required>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . $user['name'] . " (ID: " . $user['id'] . ")</option>";
    }
    echo "</select></p>";
    
    echo "<p><strong>Receiver:</strong> <select name='receiver_id' required>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . $user['name'] . " (ID: " . $user['id'] . ")</option>";
    }
    echo "</select></p>";
    
    echo "<p><strong>Message:</strong> <input type='text' name='message' value='Test message from debug script' required style='width: 300px;'></p>";
    echo "<p><input type='submit' name='test_insert' value='Test Message Insertion' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'></p>";
    echo "</form>";
} else {
    echo "<p style='color: red;'>Need at least 2 users to test. Please register more users.</p>";
}

// 8. Check for common issues
echo "<h2>8. Common Issues Check</h2>";
echo "<ul>";
echo "<li><strong>XAMPP Running:</strong> " . (function_exists('mysqli_connect') ? '✅ PHP MySQL extension available' : '❌ PHP MySQL extension missing') . "</li>";
echo "<li><strong>Database:</strong> " . ($conn->ping() ? '✅ Database responding' : '❌ Database not responding') . "</li>";
echo "<li><strong>Messages Table:</strong> " . ($conn->query("SHOW TABLES LIKE 'messages'")->num_rows > 0 ? '✅ Messages table exists' : '❌ Messages table missing') . "</li>";
echo "<li><strong>Users Count:</strong> " . $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] . " users</li>";
echo "<li><strong>Friends Count:</strong> " . $conn->query("SELECT COUNT(*) as count FROM friends WHERE status = 1")->fetch_assoc()['count'] . " accepted friendships</li>";
echo "<li><strong>Messages Count:</strong> " . $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'] . " messages</li>";
echo "</ul>";

$conn->close();
?> 