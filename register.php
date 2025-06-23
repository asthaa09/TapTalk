<?php
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, profile_pic) VALUES (?, ?, ?, ?)');
            $default_pic = 'default.png';
            $stmt->bind_param('ssss', $name, $email, $hashed_password, $default_pic);
            if ($stmt->execute()) {
                header('Location: login.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TapTalk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-toggle">
                <a href="login.php">Login</a>
                <a href="register.php" class="active">Register</a>
            </div>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
</body>
</html> 