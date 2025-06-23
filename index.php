<?php
session_start();
require 'db.php'; // Make sure to include your DB connection

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, profile_pic, location FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch friend count
$friend_count_stmt = $conn->prepare("SELECT COUNT(*) as friend_count FROM friends WHERE (user_one = ? OR user_two = ?) AND status = 1");
$friend_count_stmt->bind_param("ii", $user_id, $user_id);
$friend_count_stmt->execute();
$friend_count_result = $friend_count_stmt->get_result();
$friend_count = $friend_count_result->fetch_assoc()['friend_count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TapTalk</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body data-user-id="<?php echo $user_id; ?>">
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile-pic">
                    <img src="uploads/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="User">
                </div>
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['location']); ?></p>
                <p><?php echo $friend_count; ?> Friends</p>
            </div>
            <nav>
                <ul>
                    <li class="active" data-page="home"><a href="#">Home</a></li>
                    <li data-page="friends"><a href="#">Friends</a></li>
                    <li data-page="users"><a href="#">Users</a></li>
                    <li data-page="chat"><a href="#">Chat</a></li>
                    <li data-page="notifications"><a href="#">Notifications</a></li>
                    <li data-page="profile"><a href="#">Profile</a></li>
                </ul>
            </nav>
            <div class="logout-btn">
                <a href="logout.php">Logout</a>
            </div>
        </div>
        <div class="content">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html> 