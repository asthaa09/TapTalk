<?php
session_start();
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'No user found with that email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TapTalk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-toggle">
                <a href="login.php" class="active">Login</a>
                <a href="register.php">Register</a>
            </div>
            <form method="POST">
                <input type="email" name="email" placeholder="Email or Phone Number" required>
                <input type="password" name="password" placeholder="Password" required>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html> 