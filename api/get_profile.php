<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, profile_pic, location FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<div class="profile-container">
    <div class="profile-header">
        <h2>Profile Settings</h2>
        <p>Manage your profile information and picture</p>
    </div>
    
    <div class="profile-content">
        <div class="profile-picture-section">
            <h3>Profile Picture</h3>
            <div class="current-picture">
                <img src="uploads/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Current Profile Picture" id="current-profile-pic">
            </div>
            
            <form id="profile-pic-form" enctype="multipart/form-data">
                <div class="file-input-wrapper">
                    <input type="file" id="profile-pic-input" name="profile_pic" accept="image/*" style="display: none;">
                    <button type="button" onclick="document.getElementById('profile-pic-input').click()" class="upload-btn">
                        📷 Choose New Picture
                    </button>
                </div>
                <button type="submit" class="save-btn" style="display: none;">Save Picture</button>
            </form>
            
            <div class="upload-info">
                <p><small>Supported formats: JPG, JPEG, PNG, GIF</small></p>
                <p><small>Maximum size: 5MB</small></p>
            </div>
        </div>
        
        <div class="profile-info-section">
            <h3>Profile Information</h3>
            <form id="profile-info-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>">
                </div>
                
                <button type="submit" class="save-btn">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
// Handle profile picture upload
document.getElementById('profile-pic-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('current-profile-pic').src = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // Show save button
        document.querySelector('#profile-pic-form .save-btn').style.display = 'block';
    }
});

document.getElementById('profile-pic-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const fileInput = document.getElementById('profile-pic-input');
    
    if (fileInput.files.length > 0) {
        formData.append('profile_pic', fileInput.files[0]);
        
        fetch('api/upload_profile_pic.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Profile picture updated successfully!');
                // Update the sidebar profile picture
                const sidebarPic = document.querySelector('.sidebar-header .profile-pic img');
                if (sidebarPic) {
                    sidebarPic.src = 'uploads/images/' + data.filename;
                }
                // Hide save button
                document.querySelector('#profile-pic-form .save-btn').style.display = 'none';
            } else {
                alert(data.message || 'Failed to upload profile picture');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading profile picture');
        });
    }
});

// Handle profile information update
document.getElementById('profile-info-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('location', document.getElementById('location').value);
    
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile information updated successfully!');
            // Update sidebar info
            const sidebarName = document.querySelector('.sidebar-header h3');
            const sidebarLocation = document.querySelector('.sidebar-header p');
            if (sidebarName) sidebarName.textContent = document.getElementById('name').value;
            if (sidebarLocation) sidebarLocation.textContent = document.getElementById('location').value;
        } else {
            alert(data.message || 'Failed to update profile information');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating profile information');
    });
});
</script> 