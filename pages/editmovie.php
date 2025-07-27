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

// Get filter parameters from URL
$filters = [
    'franchise' => $_GET['franchise'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'search' => $_GET['search'] ?? '',
    'page' => $_GET['page'] ?? ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie->id = $movieId;
    $movie->title = $_POST['title'];
    $movie->originalTitle = !empty($_POST['originalTitle']) ? $_POST['originalTitle'] : null;
    $movie->image = $_POST['image'];
    $movie->year = (int)$_POST['year'];
    $movie->length = !empty($_POST['length']) ? (int)$_POST['length'] : null;
    $movie->rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
    $movie->path = !empty($_POST['path']) ? $_POST['path'] : null;
    $movie->franchiseId = !empty($_POST['franchiseId']) ? (int)$_POST['franchiseId'] : null;
    
    if ($movie->editMovie()) {
        // Build query string for redirect
        $queryString = http_build_query($filters);
        header('Location: /index.php' . ($queryString ? '?' . $queryString : ''));
        exit;
    }
}

// Fetch the specific movie
$result = $movie->getMovie($movieId);
if (!$result) {
    header('Location: /index.php');
    exit;
}

// Get franchises for dropdown
$franchises = $movie->getFranchises();

// Get filter parameters from URL
$filters = [
    'franchise' => $_GET['franchise'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'search' => $_GET['search'] ?? '',
    'page' => $_GET['page'] ?? ''
];

// Build the query string for the back link
$queryString = http_build_query($filters);
$cancelLink = '/index.php' . ($queryString ? '?' . $queryString : '');

$pagetitle = 'Edit ' . htmlspecialchars($movie->title) . ' - DeMoLi';
include '../includes/header.php';
?>
<div class="edit-form">
    <h1>Edit Movie</h1>
    <form method="POST" class="movie-edit-form">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($movie->title) ?>" required>
        </div>
        <div class="form-group">
            <label for="originalTitle">Original Title:</label>
            <input type="text" id="originalTitle" name="originalTitle" value="<?= htmlspecialchars($movie->originalTitle ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="franchise">Franchise:</label>
            <select id="franchise" name="franchiseId">
                <?php
                // Put Standalone (ID 1) first
                foreach ($franchises as $key => $franchise):
                    if ($franchise['FranchiseID'] == 1):
                ?>
                <option value="1" <?= ($movie->franchiseId == 1 || !$movie->franchiseId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($franchise['Name']) ?>
                </option>
                <?php
                        unset($franchises[$key]);
                    endif;
                endforeach;
                // Then list all other franchises
                foreach ($franchises as $franchise):
                ?>
                <option value="<?= htmlspecialchars($franchise['FranchiseID']) ?>" <?= $movie->franchiseId == $franchise['FranchiseID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($franchise['Name']) ?>
                </option>
                <?php
                endforeach;
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="year">Year:</label>
            <input type="number" id="year" name="year" value="<?= htmlspecialchars($movie->year) ?>" required>
        </div>
        <div class="form-group">
            <label for="length">Length (minutes):</label>
            <input type="number" id="length" name="length" value="<?= htmlspecialchars($movie->length ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="rating">Rating:</label>
            <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="<?= htmlspecialchars($movie->rating ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="image">Image filename:</label>
            <input type="text" id="image" name="image" value="<?= htmlspecialchars($movie->image) ?>" required>
        </div>
        <div class="form-group">
            <label for="path">Video path:</label>
            <input type="text" id="path" name="path" value="<?= htmlspecialchars($movie->path ?? '') ?>">
        </div>
        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="<?= htmlspecialchars($cancelLink) ?>" class="cancel-button">Cancel</a>
        </div>
    </form>
</div>
<?php include '../includes/footer.php'; ?>