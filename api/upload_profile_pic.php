<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['profile_pic'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    
    if (!in_array($fileExt, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed']);
        exit;
    }
    
    if ($fileError !== 0) {
        echo json_encode(['success' => false, 'message' => 'There was an error uploading the file']);
        exit;
    }
    
    if ($fileSize > 5000000) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileNameNew = 'profile_' . $user_id . '_' . time() . '.' . $fileExt;
    $fileDestination = $uploadDir . $fileNameNew;
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $fileDestination)) {
        // Update database with new profile picture
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->bind_param("si", $fileNameNew, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'filename' => $fileNameNew]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 