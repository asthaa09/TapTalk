<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}
$current_user_id = $_SESSION['user_id'];

// Fetch pending friend requests where the logged-in user is the receiver
$pending_sql = "SELECT u.id, u.name, u.profile_pic, u.location
                FROM users u
                JOIN friends f ON u.id = f.user_one
                WHERE f.user_two = ? AND f.status = 0 AND f.action_user_id != ?";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("ii", $current_user_id, $current_user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_requests = $pending_result->fetch_all(MYSQLI_ASSOC);

// Fetch accepted friends
$friends_sql = "SELECT u.id, u.name, u.profile_pic, u.location
                FROM users u
                JOIN friends f ON (u.id = f.user_one OR u.id = f.user_two)
                WHERE (f.user_one = ? OR f.user_two = ?) AND f.status = 1 AND u.id != ?";
$friends_stmt = $conn->prepare($friends_sql);
$friends_stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();
$friends = $friends_result->fetch_all(MYSQLI_ASSOC);
?>

<h2>Friends</h2>

<div class="friends-section">
    <h3>Pending Requests (<?php echo count($pending_requests); ?>)</h3>
    <div class="user-grid">
        <?php if (count($pending_requests) > 0): ?>
            <?php foreach ($pending_requests as $request): ?>
                <div class="user-card">
                    <img src="uploads/images/<?php echo htmlspecialchars($request['profile_pic']); ?>" alt="<?php echo htmlspecialchars($request['name']); ?>">
                    <h3><?php echo htmlspecialchars($request['name']); ?></h3>
                    <p><?php echo htmlspecialchars($request['location']); ?></p>
                    <div class="user-card-actions" data-user-id="<?php echo $request['id']; ?>">
                        <button class="accept-request">Accept</button>
                        <button class="reject-request">Reject</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No pending friend requests.</p>
        <?php endif; ?>
    </div>
</div>

<div class="friends-section">
    <h3>Your Friends (<?php echo count($friends); ?>)</h3>
    <div class="user-grid">
        <?php if (count($friends) > 0): ?>
            <?php foreach ($friends as $friend): ?>
                <div class="user-card">
                    <img src="uploads/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" alt="<?php echo htmlspecialchars($friend['name']); ?>">
                    <h3><?php echo htmlspecialchars($friend['name']); ?></h3>
                    <p><?php echo htmlspecialchars($friend['location']); ?></p>
                    <div class="user-card-actions" data-user-id="<?php echo $friend['id']; ?>">
                        <button class="chat" onclick="loadPage('chat', { userId: <?php echo $friend['id']; ?> })">Chat</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have no friends yet.</p>
        <?php endif; ?>
    </div>
</div> 