<?php
require_once '../config/database.php';
require_once '../classes/movie.php';

// Handle time update AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $movieId = $_POST['movieId'] ?? null;
    $timeWatched = $_POST['timeWatched'] ?? null;

    if ($movieId && is_numeric($timeWatched)) {
        $database = new Database();
        $movie = new Movie($database->getConnection());
        
        if ($movie->getMovie($movieId)) {
            $success = $movie->updateTimeWatched((int)$timeWatched);
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }
    }
    header('HTTP/1.1 400 Bad Request');
    exit;
}

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
$filters = [];
if (!empty($_GET['franchise'])) $filters['franchise'] = $_GET['franchise'];
if (!empty($_GET['sort'])) $filters['sort'] = $_GET['sort'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
if (!empty($_GET['page'])) $filters['page'] = $_GET['page'];

// Build the query string for the back link
$queryString = !empty($filters) ? http_build_query($filters) : '';
$backLink = '/index.php' . ($queryString ? '?' . $queryString : '') . '#' . $movieId;

$pagetitle = htmlspecialchars($movie->title) . ' - DeMoLi';
include '../includes/header.php';
?>
<div class="video-container">
    <div class="title-bar">
        <a href="<?= htmlspecialchars($backLink) ?>" class="back-button">Back</a>
        <h3 class="movie-title"><?= htmlspecialchars($movie->title); ?></h3>
    </div>
    <video controls width="100%" id="moviePlayer" controlsList="nodownload nopictureinpicture noplaybackrate" disablePictureInPicture>
        <source src="../video/video.php?path=<?= urlencode($movie->path) ?>" type="video/mp4">
        <track label="Subtitles" kind="subtitles" srclang="en" src="../video/subtitles.php?path=<?= urlencode($movie->path) ?>" default>
    </video>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('moviePlayer');
            const backButton = document.querySelector('.back-button');
            
            // Start from saved time if available, or from start parameter if provided
            const startFrom = <?= isset($_GET['start']) && $_GET['start'] === 'beginning' ? 0 : ($movie->timeWatched ?? 0) ?>;
            if (startFrom > 0) {
                video.currentTime = startFrom;
            }

            // Handle back button click
            backButton.addEventListener('click', function(e) {
                e.preventDefault();
                const currentTime = Math.floor(video.currentTime);
                
                // Send time update via XHR
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('movieId', '<?= $movieId ?>');
                formData.append('timeWatched', currentTime);
                
                xhr.open('POST', window.location.href, false); // Synchronous request to same page
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
                
                // Navigate back after the update
                window.location.href = backButton.href;
            });

            // Update time watched when leaving the page (except when using back button)
            window.addEventListener('beforeunload', function(e) {
                if (e.currentTarget.performance.navigation.type !== 2) { // Not a back button navigation
                    const currentTime = Math.floor(video.currentTime);
                    const formData = new FormData();
                    formData.append('movieId', '<?= $movieId ?>');
                    formData.append('timeWatched', currentTime);
                    navigator.sendBeacon(window.location.href, formData);
                }
            });
        });
    </script>
</div>
<?php include '../includes/footer.php'; ?>