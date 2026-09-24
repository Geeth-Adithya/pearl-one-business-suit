<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    
    // 1. Check file size first
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit;
    }

    // 2. Server-side real image validation (not trusting browser MIME)
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        echo json_encode(['success' => false, 'message' => 'File is not a valid image.']);
        exit;
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($imageInfo['mime'], $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, WEBP, GIF allowed.']);
        exit;
    }

    // 3. Restrict file extension (prevent .php disguised uploads)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file extension.']);
        exit;
    }

    $filename = uniqid('prod_') . '.' . $ext;
    
    // Relative to the root
    $targetDir = dirname(__DIR__) . '/uploads/products/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => true, 'url' => 'uploads/products/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No image provided.']);
}
?>

