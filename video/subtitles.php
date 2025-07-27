<?php
require_once '../config/database.php';

function convertSrtToVtt($srtPath) {
    if (!file_exists($srtPath)) {
        return false;
    }

    $content = file_get_contents($srtPath);
    
    // Convert line endings
    $content = str_replace("\r\n", "\n", $content);
    
    // Add WEBVTT header
    $content = "WEBVTT\n\n" . $content;
    
    // Convert timestamps (00:00:00,000 -> 00:00:00.000)
    $content = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $content);
    
    return $content;
}

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

// Change extension from .mp4 to .srt
$srtPath = preg_replace('/\.mp4$/', '.srt', $path);

if (!file_exists($srtPath)) {
    header('HTTP/1.0 404 Not Found');
    exit('Subtitles not found');
}

$vttContent = convertSrtToVtt($srtPath);
if ($vttContent === false) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error converting subtitles');
}

header('Content-Type: text/vtt');
echo $vttContent;
?>