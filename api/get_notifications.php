<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the current user
$stmt = $conn->prepare("
    SELECT n.*, u.name as source_name 
    FROM notifications n 
    LEFT JOIN users u ON n.source_id = u.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Count unread notifications
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
?>

<div class="notifications-container">
    <div class="notifications-header">
        <h2>Notifications</h2>
        <div>
            <span><?php echo $unread_count; ?> unread</span>
            <button onclick="markAllAsRead()">Mark All as Read</button>
        </div>
    </div>
    <ul class="notification-list">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notification): ?>
                <li class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-notification-id="<?php echo $notification['id']; ?>">
                    <div class="notification-icon">
                        <?php
                        switch($notification['type']) {
                            case 'friend_request':
                                echo '👥';
                                break;
                            case 'friend_accept':
                                echo '✅';
                                break;
                            case 'new_post':
                                echo '📄';
                                break;
                            case 'new_message':
                                echo '💬';
                                break;
                            default:
                                echo '🔔';
                        }
                        ?>
                    </div>
                    <div>
                        <strong>
                            <?php
                            switch($notification['type']) {
                                case 'friend_request':
                                    echo 'Friend Request from ' . htmlspecialchars($notification['source_name']);
                                    break;
                                case 'friend_accept':
                                    echo htmlspecialchars($notification['source_name']) . ' accepted your friend request';
                                    break;
                                case 'new_post':
                                    echo 'New Post from ' . htmlspecialchars($notification['source_name']);
                                    break;
                                case 'new_message':
                                    echo 'New Message from ' . htmlspecialchars($notification['source_name']);
                                    break;
                                default:
                                    echo 'Notification';
                            }
                            ?>
                        </strong>
                        <p>
                            <?php
                            switch($notification['type']) {
                                case 'friend_request':
                                    echo htmlspecialchars($notification['source_name']) . ' sent you a friend request';
                                    break;
                                case 'friend_accept':
                                    echo 'You are now friends with ' . htmlspecialchars($notification['source_name']);
                                    break;
                                case 'new_post':
                                    echo htmlspecialchars($notification['source_name']) . ' made a new post';
                                    break;
                                case 'new_message':
                                    echo htmlspecialchars($notification['source_name']) . ' sent you a message';
                                    break;
                                default:
                                    echo 'You have a new notification';
                            }
                            ?>
                        </p>
                    </div>
                    <small><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="no-notifications">
                <p>No notifications yet.</p>
            </li>
        <?php endif; ?>
    </ul>
</div>

<script>
function markAllAsRead() {
    fetch('api/mark_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications page
            loadPage('notifications');
        }
    })
    .catch(console.error);
}

// Mark individual notification as read when clicked
document.addEventListener('click', function(e) {
    if (e.target.closest('.notification-item')) {
        const notificationItem = e.target.closest('.notification-item');
        const notificationId = notificationItem.dataset.notificationId;
        
        fetch('api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notificationItem.classList.remove('unread');
            }
        })
        .catch(console.error);
    }
});
</script> 