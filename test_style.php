<!DOCTYPE html>
<html>
<head>
    <title>Style Test</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile-pic">
                    <img src="uploads/images/default.svg" alt="User">
                </div>
                <h3>Test User</h3>
                <p>Test Location</p>
            </div>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Friends</a></li>
                    <li><a href="#">Chat</a></li>
                    <li><a href="#">Profile</a></li>
                </ul>
            </nav>
            <div class="logout-btn">
                <a href="#">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <div class="welcome-header">
                <h2>Welcome back, Test User!</h2>
            </div>
            
            <div class="post-box">
                <textarea placeholder="What's on your mind?"></textarea>
                <button>Post</button>
            </div>
            
            <div class="feed">
                <div class="post">
                    <div class="post-author">
                        <img src="uploads/images/default.svg" alt="User">
                        <div>
                            <strong>Test User</strong>
                            <small>Just now</small>
                        </div>
                    </div>
                    <p>This is a test post to check the styling.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 