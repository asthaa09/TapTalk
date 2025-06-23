<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}
$current_user_id = $_SESSION['user_id'];

// Fetch friends for the sidebar
$friends_sql = "SELECT u.id, u.name, u.profile_pic, u.location
                FROM users u
                JOIN friends f ON (u.id = f.user_one OR u.id = f.user_two)
                WHERE (f.user_one = ? OR f.user_two = ?) AND f.status = 1 AND u.id != ?";
$friends_stmt = $conn->prepare($friends_sql);
$friends_stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();
$friends = $friends_result->fetch_all(MYSQLI_ASSOC);

// Determine which chat to show
$chat_user_id = isset($_GET['userId']) ? (int)$_GET['userId'] : (isset($friends[0]) ? $friends[0]['id'] : null);
$chat_user = null;
$messages = [];

if ($chat_user_id) {
    // Fetch chat user's details
    $user_stmt = $conn->prepare("SELECT id, name, profile_pic FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $chat_user_id);
    $user_stmt->execute();
    $chat_user = $user_stmt->get_result()->fetch_assoc();

    // Fetch message history
    $msg_sql = "SELECT * FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at ASC";
    $msg_stmt = $conn->prepare($msg_sql);
    $msg_stmt->bind_param("iiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id);
    $msg_stmt->execute();
    $messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-header">
            <h3>Friends (<?php echo count($friends); ?>)</h3>
        </div>
        <ul class="friend-list">
            <?php if (count($friends) > 0): ?>
                <?php foreach ($friends as $friend): ?>
                    <li class="friend-item <?php echo ($friend['id'] == $chat_user_id) ? 'active' : ''; ?>" 
                        onclick="loadPage('chat', { userId: <?php echo $friend['id']; ?> })">
                        <img src="uploads/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" alt="<?php echo htmlspecialchars($friend['name']); ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($friend['name']); ?></strong>
                            <small><?php echo htmlspecialchars($friend['location']); ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="padding: 20px; text-align: center; color: #718096;">
                    <p>No friends yet.</p>
                    <p><a href="#" onclick="loadPage('users')" style="color: #667eea;">Find friends</a></p>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="chat-area" data-chat-with="<?php echo $chat_user_id; ?>">
        <?php if ($chat_user): ?>
            <div class="chat-header">
                <h3><?php echo htmlspecialchars($chat_user['name']); ?></h3>
                <button class="delete-chat">Delete Chat</button>
            </div>
            <div class="chat-messages">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo ($message['sender_id'] == $current_user_id) ? 'self' : ''; ?>" data-message-id="<?php echo $message['id']; ?>">
                            <p><?php echo htmlspecialchars($message['message']); ?></p>
                            <small><?php echo date('h:i A', strtotime($message['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #718096; padding: 40px;">
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Type your message..." id="chat-message-input">
                <button onclick="sendChatMessage()">Send</button>
            </div>
        <?php else: ?>
            <div class="chat-header">
                <h3>Select a chat</h3>
            </div>
            <div class="chat-messages">
                <?php if (count($friends) > 0): ?>
                    <p>Please select a friend from the list to start chatting.</p>
                <?php else: ?>
                    <div style="text-align: center; color: #718096; padding: 40px;">
                        <p>You need friends to start chatting.</p>
                        <p><a href="#" onclick="loadPage('users')" style="color: #667eea;">Find friends</a></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function sendChatMessage() {
    const chatArea = document.querySelector('.chat-area');
    const chatInput = document.getElementById('chat-message-input');
    const message = chatInput.value.trim();
    const receiverId = chatArea.dataset.chatWith;

    console.log('sendChatMessage called:', { message, receiverId }); // Debug log

    if (message && receiverId) {
        const formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', message);

        fetch('api/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success) {
                chatInput.value = '';
                // Instantly add the message to the UI for better UX
                const messagesContainer = chatArea.querySelector('.chat-messages');
                const newMessage = document.createElement('div');
                newMessage.className = 'message self';
                newMessage.dataset.messageId = data.message_id || Date.now();
                newMessage.innerHTML = `<p>${message}</p><small>Just now</small>`;
                messagesContainer.appendChild(newMessage);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                alert(data.message || 'Failed to send message.');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('Error sending message. Please try again.');
        });
    } else {
        if (!message) {
            alert('Please enter a message.');
        } else if (!receiverId) {
            alert('Please select a friend to chat with.');
        }
    }
}

// Also handle Enter key
document.getElementById('chat-message-input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendChatMessage();
    }
});
</script> 