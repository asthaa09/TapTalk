<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}
$current_user_id = $_SESSION['user_id'];

$sql = "SELECT u.id, u.name, u.profile_pic, u.location, 
        f.status, f.action_user_id
        FROM users u
        LEFT JOIN friends f ON 
            ((f.user_one = u.id AND f.user_two = ?) OR (f.user_one = ? AND f.user_two = u.id))
        WHERE u.id != ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>All Users</h2>
<div class="user-grid">
    <?php while ($user = $result->fetch_assoc()): ?>
        <div class="user-card">
            <img src="uploads/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['location']); ?></p>
            <div class="user-card-actions" data-user-id="<?php echo $user['id']; ?>">
                <?php
                if ($user['status'] == '1') {
                    echo '<button class="friends">Friends</button>';
                    echo '<button class="chat" onclick="loadPage(\'chat\', { userId: ' . $user['id'] . ' })">Chat</button>';
                } elseif ($user['status'] == '0') {
                    if ($user['action_user_id'] == $current_user_id) {
                        echo '<button class="request-sent" disabled>Request Sent</button>';
                    } else {
                        echo '<button class="accept-request">Accept</button>';
                        echo '<button class="reject-request">Reject</button>';
                    }
                } else {
                    echo '<button class="add-friend">Add Friend</button>';
                }
                ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>