<?php
// Create a simple text-based default profile picture
$svg_content = '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#4A90E2"/>
    <text x="100" y="110" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">TapTalk</text>
</svg>';

// Create uploads/images directory if it doesn't exist
if (!file_exists('uploads/images/')) {
    mkdir('uploads/images/', 0777, true);
}

// Save SVG file
file_put_contents('uploads/images/default.svg', $svg_content);

echo "Default profile picture (SVG) created successfully!";
?> 