<?php
require_once '../config/database.php';

if (!isset($_GET['path'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('No path specified');
}

$path = urldecode($_GET['path']);

$mediaDrive = Database::getMediaDrive();
if (!str_starts_with($path, $mediaDrive)) {
    $path = $mediaDrive . ltrim($path, '/');
}

// Basic security check
if (strpos($path, '..') !== false) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Verify file exists and is an MP4
if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'mp4') {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

$fileSize = filesize($path);
$offset = 0;
$length = $fileSize;

// Handle range requests for video seeking
if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $offset = intval($matches[1]);
        
        if (!empty($matches[2])) {
            $length = intval($matches[2]) - $offset + 1;
        } else {
            $length = $fileSize - $offset;
        }
    }
}

// Send appropriate headers
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    header('HTTP/1.1 206 Partial Content');
    header(sprintf('Content-Range: bytes %d-%d/%d', $offset, $offset + $length - 1, $fileSize));
} else {
    header('Content-Length: ' . $fileSize);
}

// Open file and seek to offset
$handle = fopen($path, 'rb');
fseek($handle, $offset);

// Stream the video
$remaining = $length;
while (!feof($handle) && $remaining > 0) {
    $readSize = min(1024 * 16, $remaining);
    echo fread($handle, $readSize);
    $remaining -= $readSize;
    flush();
}

fclose($handle);
?>