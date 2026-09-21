<?php
// admin/download_google_script.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Only admins and superadmins may download the script
requireAdmin();

$filePath = realpath(__DIR__ . '/../google_apps_script.js');
if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    echo 'Script file not found.';
    exit;
}

// Serve the file for download
header('Content-Type: application/javascript');
header('Content-Disposition: attachment; filename="google_apps_script.js"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;

?>