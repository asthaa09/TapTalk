<?php
session_start();
require '../db.php';

echo "<h1>🔍 Chat State Check</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ Not logged in. Please login first.</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Get current user info
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

echo "<h2>Current User</h2>";
echo "<p><strong>ID:</strong> $current_user_id</p>";
echo "<p><strong>Name:</strong> " . $current_user['name'] . "</p>";
echo "<p><strong>Email:</strong> " . $current_user['email'] . "</p>";

// Check friends
echo "<h2>Friends Check</h2>";
$friends_sql = "SELECT u.id, u.name, u.profile_pic, u.location
                FROM users u
                JOIN friends f ON (u.id = f.user_one OR u.id = f.user_two)
                WHERE (f.user_one = ? OR f.user_two = ?) AND f.status = 1 AND u.id != ?";
$friends_stmt = $conn->prepare($friends_sql);
$friends_stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();
$friends = $friends_result->fetch_all(MYSQLI_ASSOC);

if (count($friends) == 0) {
    echo "<p style='color: red;'>❌ No friends found! This is why you can't send messages.</p>";
    echo "<p>To fix this:</p>";
    echo "<ol>";
    echo "<li>Register another user account</li>";
    echo "<li>Login as one user and send friend request to the other</li>";
    echo "<li>Login as the other user and accept the friend request</li>";
    echo "<li>Then you can send messages</li>";
    echo "</ol>";
    
    // Show all users for reference
    echo "<h3>All Users (for reference):</h3>";
    $all_users = $conn->query("SELECT id, name, email FROM users WHERE id != $current_user_id");
    if ($all_users->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        while ($user = $all_users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No other users found. Please register another account.</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Found " . count($friends) . " friends:</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Location</th></tr>";
    foreach ($friends as $friend) {
        echo "<tr>";
        echo "<td>" . $friend['id'] . "</td>";
        echo "<td>" . $friend['name'] . "</td>";
        echo "<td>" . $friend['location'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test chat interface
    echo "<h2>Chat Interface Test</h2>";
    $first_friend = $friends[0];
    echo "<p>Testing chat with: " . $first_friend['name'] . " (ID: " . $first_friend['id'] . ")</p>";
    
    // Check if there are any messages
    $msg_sql = "SELECT * FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at ASC";
    $msg_stmt = $conn->prepare($msg_sql);
    $msg_stmt->bind_param("iiii", $current_user_id, $first_friend['id'], $first_friend['id'], $current_user_id);
    $msg_stmt->execute();
    $messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<p>Messages with " . $first_friend['name'] . ": " . count($messages) . "</p>";
    
    if (count($messages) > 0) {
        echo "<h3>Recent Messages:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>From</th><th>Message</th><th>Time</th></tr>";
        foreach (array_slice($messages, -5) as $message) {
            $is_self = $message['sender_id'] == $current_user_id;
            echo "<tr>";
            echo "<td>" . ($is_self ? 'You' : $first_friend['name']) . "</td>";
            echo "<td>" . htmlspecialchars($message['message']) . "</td>";
            echo "<td>" . $message['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check pending friend requests
echo "<h2>Pending Friend Requests</h2>";
$pending_sql = "SELECT u.id, u.name, u.email
                FROM users u
                JOIN friends f ON u.id = f.user_one
                WHERE f.user_two = ? AND f.status = 0 AND f.action_user_id != ?";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("ii", $current_user_id, $current_user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_requests = $pending_result->fetch_all(MYSQLI_ASSOC);

if (count($pending_requests) > 0) {
    echo "<p style='color: orange;'>⚠️ You have " . count($pending_requests) . " pending friend request(s):</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Email</th></tr>";
    foreach ($pending_requests as $request) {
        echo "<tr>";
        echo "<td>" . $request['name'] . "</td>";
        echo "<td>" . $request['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><a href='index.php'>Go to Friends page to accept requests</a></p>";
} else {
    echo "<p>No pending friend requests.</p>";
}

// Check sent friend requests
echo "<h2>Sent Friend Requests</h2>";
$sent_sql = "SELECT u.id, u.name, u.email
             FROM users u
             JOIN friends f ON u.id = f.user_two
             WHERE f.user_one = ? AND f.status = 0 AND f.action_user_id = ?";
$sent_stmt = $conn->prepare($sent_sql);
$sent_stmt->bind_param("ii", $current_user_id, $current_user_id);
$sent_stmt->execute();
$sent_result = $sent_stmt->get_result();
$sent_requests = $sent_result->fetch_all(MYSQLI_ASSOC);

if (count($sent_requests) > 0) {
    echo "<p>You have sent " . count($sent_requests) . " friend request(s):</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Email</th></tr>";
    foreach ($sent_requests as $request) {
        echo "<tr>";
        echo "<td>" . $request['name'] . "</td>";
        echo "<td>" . $request['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sent friend requests.</p>";
}

$conn->close();
?> 