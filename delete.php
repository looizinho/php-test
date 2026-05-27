<?php

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

$notesDir = __DIR__ . '/notes';

if (!isset($_POST['file'])) {
    http_response_code(400);
    die('Missing file parameter');
}

$filename = basename($_POST['file']);
$filePath = $notesDir . '/' . $filename;

// Security: prevent path traversal
$realPath = realpath($filePath);
$notesRealPath = realpath($notesDir);

if (!$realPath || !$notesRealPath || strpos($realPath, $notesRealPath) !== 0) {
    http_response_code(400);
    die('Invalid file path');
}

// Check file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Validate filename contains only safe characters
if (!preg_match('/^[a-z0-9-]+\.md$/', $filename)) {
    http_response_code(400);
    die('Invalid filename');
}

// Delete the file
if (unlink($filePath)) {
    header('Location: index.php');
    exit;
} else {
    http_response_code(500);
    die('Failed to delete file');
}
?>
