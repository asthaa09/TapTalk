<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch user's name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_name = $user['name'];

// Fetch posts from friends and self
$posts_stmt = $conn->prepare("
    SELECT p.*, u.name, u.profile_pic 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id = ? OR p.user_id IN (
        SELECT CASE 
            WHEN f.user_one = ? THEN f.user_two 
            ELSE f.user_one 
        END 
        FROM friends f 
        WHERE (f.user_one = ? OR f.user_two = ?) AND f.status = 1
    )
    ORDER BY p.created_at DESC 
    LIMIT 50
");
$posts_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="welcome-header">
    <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
</div>

<div class="post-box">
    <textarea id="post-content" placeholder="What's on your mind?"></textarea>
    <button onclick="createPost()">Post</button>
</div>

<div class="feed">
    <?php if (count($posts) > 0): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post <?php echo ($post['user_id'] == $user_id) ? 'self-post' : ''; ?>">
                <div class="post-author">
                    <img src="uploads/images/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="<?php echo htmlspecialchars($post['name']); ?>">
                    <div>
                        <strong><?php echo htmlspecialchars($post['name']); ?></strong>
                        <small><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                    </div>
                </div>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <?php if ($post['user_id'] == $user_id): ?>
                    <button class="delete-post" onclick="deletePost(<?php echo $post['id']; ?>)">🗑️</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-posts">
            <p>No posts yet. Be the first to share something!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function createPost() {
    const content = document.getElementById('post-content').value.trim();
    if (!content) {
        alert('Please enter some content for your post.');
        return;
    }

    const formData = new FormData();
    formData.append('content', content);

    // Debug: Log formData content
    console.log('Submitting post:', content);
    alert('Submitting post: ' + content);

    fetch('api/create_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('API response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API response data:', data);
        if (data.success) {
            document.getElementById('post-content').value = '';
            alert('Post created successfully!');
            // Reload the home page to show the new post
            loadPage('home');
        } else {
            alert(data.message || 'Failed to create post.');
        }
    })
    .catch(error => {
        alert('Error: ' + error);
        console.error('Error:', error);
    });
}

function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post?')) {
        const formData = new FormData();
        formData.append('post_id', postId);

        fetch('api/delete_post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the home page
                loadPage('home');
            } else {
                alert(data.message || 'Failed to delete post.');
            }
        })
        .catch(console.error);
    }
}
</script> 