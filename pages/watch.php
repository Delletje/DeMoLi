<?php
require_once '../config/database.php';
require_once '../classes/movie.php';

// Get movie ID from URL
$movieId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$movieId || !preg_match('/^tt\d+$/', $movieId)) {
    header('Location: /index.php');
    exit;
}

$database = new Database();
$movie = new Movie($database->getConnection());

// Fetch the specific movie
$result = $movie->getMovie($movieId);
if (!$result || !$movie->path) {
    header('Location: /index.php');
    exit;
}

// Get filter parameters from URL
$filters = [
    'franchise' => $_GET['franchise'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'search' => $_GET['search'] ?? '',
    'page' => $_GET['page'] ?? ''
];

// Build the query string for the back link
$queryString = http_build_query($filters);
$backLink = '/index.php' . ($queryString ? '?' . $queryString : '');

$pagetitle = htmlspecialchars($movie->title) . ' - DeMoLi';
include '../includes/header.php';
?>
<div class="video-container">
    <div class="title-bar">
        <a href="<?= htmlspecialchars($backLink) ?>" class="back-button">Back</a>
        <h3 class="movie-title"><?= htmlspecialchars($movie->title); ?></h3>
    </div>
    <video controls width="100%" controlsList="nodownload nopictureinpicture noplaybackrate" disablePictureInPicture>
        <source src="../video/video.php?path=<?= urlencode($movie->path) ?>" type="video/mp4">
        <track label="Subtitles" kind="subtitles" srclang="en" src="../video/subtitles.php?path=<?= urlencode($movie->path) ?>" default>
    </video>
</div>
<?php include '../includes/footer.php'; ?>