<?php
echo "<h1>🔍 Simple Upload Test</h1>";

// 1. Check uploads directory
echo "<h2>1. Uploads Directory Check</h2>";
$uploadDir = 'uploads/';
if (file_exists($uploadDir)) {
    echo "<p style='color: green;'>✅ Uploads directory exists</p>";
    if (is_writable($uploadDir)) {
        echo "<p style='color: green;'>✅ Uploads directory is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ Uploads directory is NOT writable</p>";
        echo "<p>Current permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Uploads directory does not exist</p>";
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color: green;'>✅ Created uploads directory</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create uploads directory</p>";
    }
}

// 2. Check PHP upload settings
echo "<h2>2. PHP Upload Settings</h2>";
echo "<ul>";
echo "<li><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? '✅ Enabled' : '❌ Disabled') . "</li>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</li>";
echo "<li><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "</ul>";

// 3. Test file upload
echo "<h2>3. Test File Upload</h2>";
if (isset($_POST['test_upload'])) {
    echo "<h3>Upload Test Results:</h3>";
    
    if (!isset($_FILES['test_file'])) {
        echo "<p style='color: red;'>❌ No file uploaded</p>";
    } else {
        $file = $_FILES['test_file'];
        echo "<ul>";
        echo "<li><strong>File name:</strong> " . $file['name'] . "</li>";
        echo "<li><strong>File size:</strong> " . $file['size'] . " bytes</li>";
        echo "<li><strong>File type:</strong> " . $file['type'] . "</li>";
        echo "<li><strong>Upload error:</strong> " . $file['error'] . "</li>";
        echo "<li><strong>Temporary file:</strong> " . $file['tmp_name'] . "</li>";
        echo "</ul>";
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo "<p style='color: green;'>✅ File uploaded successfully to temporary location</p>";
            
            // Test moving the file
            $testFileName = 'test_' . time() . '_' . $file['name'];
            $testFilePath = $uploadDir . $testFileName;
            
            if (move_uploaded_file($file['tmp_name'], $testFilePath)) {
                echo "<p style='color: green;'>✅ File moved successfully to: $testFilePath</p>";
                echo "<p><strong>File size on disk:</strong> " . filesize($testFilePath) . " bytes</p>";
                
                // Show the uploaded image
                if (strpos($file['type'], 'image/') === 0) {
                    echo "<p><strong>Uploaded Image:</strong></p>";
                    echo "<img src='$testFilePath' style='max-width: 200px; border: 2px solid #ccc;'>";
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to move uploaded file</p>";
                $error = error_get_last();
                if ($error) {
                    echo "<p>Error details: " . $error['message'] . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ File upload error: " . $file['error'] . "</p>";
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    echo "<p>File exceeds upload_max_filesize</p>";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    echo "<p>File exceeds MAX_FILE_SIZE</p>";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    echo "<p>File was only partially uploaded</p>";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo "<p>No file was uploaded</p>";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo "<p>Missing temporary folder</p>";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo "<p>Failed to write file to disk</p>";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo "<p>File upload stopped by extension</p>";
                    break;
            }
        }
    }
}

// 4. Test form
echo "<h2>4. Manual Upload Test</h2>";
echo "<form method='POST' enctype='multipart/form-data' style='background: #f0f0f0; padding: 20px; border-radius: 10px;'>";
echo "<p><strong>Select a test image:</strong></p>";
echo "<input type='file' name='test_file' accept='image/*' required>";
echo "<p><input type='submit' name='test_upload' value='Test Upload' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'></p>";
echo "</form>";

// 5. Check existing files in uploads directory
echo "<h2>5. Files in Uploads Directory</h2>";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    if (count($files) > 2) { // More than . and ..
        echo "<p>Found " . (count($files) - 2) . " files:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $uploadDir . $file;
                $fileSize = filesize($filePath);
                echo "<li>$file ($fileSize bytes)";
                if (strpos($file, '.jpg') !== false || strpos($file, '.png') !== false || strpos($file, '.gif') !== false) {
                    echo " - <img src='$filePath' style='width: 50px; height: 50px; border-radius: 50%;'>";
                }
                echo "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No files found in uploads directory</p>";
    }
}

// 6. Check for common issues
echo "<h2>6. Common Issues Check</h2>";
echo "<ul>";
echo "<li><strong>XAMPP Running:</strong> " . (function_exists('mysqli_connect') ? '✅ PHP MySQL extension available' : '❌ PHP MySQL extension missing') . "</li>";
echo "<li><strong>Session Working:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Session active' : '❌ Session not active') . "</li>";
echo "<li><strong>Uploads Directory Permissions:</strong> " . (is_writable($uploadDir) ? '✅ Writable' : '❌ Not writable') . "</li>";
echo "<li><strong>Current Working Directory:</strong> " . getcwd() . "</li>";
echo "<li><strong>Uploads Directory Path:</strong> " . realpath($uploadDir) . "</li>";
echo "</ul>";
?> 