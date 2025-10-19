<?php
require_once 'config/database.php';
require_once 'classes/movie.php';

// Number of items per page
$itemsPerPage = 100;

// Get current page from URL parameter
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get filter parameters
$filters = [];
if (!empty($_GET['franchise'])) $filters['franchise'] = $_GET['franchise'];
if (!empty($_GET['sort'])) {$filters['sort'] = $_GET['sort'];} else {$filters['sort'] = '';}
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
if (!empty($_GET['page'])) $filters['page'] = $_GET['page'];

$movie = new Movie(new Database());
$franchises = $movie->getFranchises();
$result = $movie->getMovies($currentPage, $itemsPerPage, $filters);
$movies = $result['movies'];
$totalPages = $result['pages'];

include 'includes/header.php';
if (getenv('DEVMODE')):
?>
<div class="add-movie-container">
    <a href="pages/addmovie.php" class="add-movie-button">
        <span class="plus-icon">+</span>
        Add Movie
    </a>
    <a href="pages/getposter.php" class="add-movie-button">
        Get Poster
    </a>
    <a href="pages/srtcheck.php" class="add-movie-button">
        Check SRT
    </a>
</div>
<?php endif; ?>
<div class="total-movies">
    <?php $totalCount = $movie->getTotalMovies(); ?>
    <div>Total Movies: <?= number_format($totalCount); ?></div>
    <?php if (!empty($filters['franchise']) || !empty($filters['search'])): ?>
        <div>Filtered Results: <?= number_format($result['filtered_total']); ?></div>
    <?php endif; ?>
</div>
<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-group">
                <label for="franchise">Franchise:</label>
                <select name="franchise" id="franchise">
                    <option value="">Default</option>
                    <?php 
                    // First show Standalone (ID 1)
                    foreach ($franchises as $key => $franchise):
                        if ($franchise['FranchiseID'] == 1):
                    ?>
                        <option value="<?= htmlspecialchars($franchise['FranchiseID']); ?>"
                                <?= !empty($filters['franchise']) && $filters['franchise'] == $franchise['FranchiseID'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($franchise['Name']); ?>
                        </option>
                    <?php
                            unset($franchises[$key]);
                        endif;
                    endforeach;
                    
                    // Then show all other franchises
                    foreach ($franchises as $franchise):
                    ?>
                        <option value="<?= htmlspecialchars($franchise['FranchiseID']); ?>"
                                <?= !empty($filters['franchise']) && $filters['franchise'] == $franchise['FranchiseID'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($franchise['Name']); ?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort">
                    <option value="">Default</option>
                    <option value="rating_desc" <?= $filters['sort'] === 'rating_desc' ? 'selected' : ''; ?>>Rating High-Low</option>
                    <option value="rating_asc" <?= $filters['sort'] === 'rating_asc' ? 'selected' : ''; ?>>Rating Low-High</option>
                    <option value="year_asc" <?= $filters['sort'] === 'year_asc' ? 'selected' : ''; ?>>Year Old-New</option>
                    <option value="year_desc" <?= $filters['sort'] === 'year_desc' ? 'selected' : ''; ?>>Year New-Old</option>
                    <option value="length_desc" <?= $filters['sort'] === 'length_desc' ? 'selected' : ''; ?>>Length Long-Short</option>
                    <option value="length_asc" <?= $filters['sort'] === 'length_asc' ? 'selected' : ''; ?>>Length Short-Long</option>
                    <option value="title_asc" <?= $filters['sort'] === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="title_desc" <?= $filters['sort'] === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="search">Search:</label>
                <div class="search-container">
                    <input type="text" name="search" id="search" placeholder="Search titles or year..." value="<?= !empty($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
                    <button type="submit" class="search-button">Search</button>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="movie-list">
    <?php foreach($movies as $movie): ?>
        <div class="movie-item" id="<?= htmlspecialchars($movie->id); ?>">
            <img loading="lazy" 
                src="https://m.media-amazon.com/images/M/<?= htmlspecialchars($movie->image); ?>" 
                alt="<?= htmlspecialchars($movie->title); ?>"
                onerror="this.outerHTML='<div class=no-image>No image available</div>'">
            <div class="movie-item-content">
                <h3><?= htmlspecialchars($movie->title); ?></h3>
                <div class="movie-metadata">
                    <p class="movie-year"><?= htmlspecialchars($movie->year); ?></p>
                    <p class="movie-length"><?= empty($movie->length) ? 'No length' : htmlspecialchars($movie->formatLength()); ?></p>
                </div>
                <p class="movie-rating"><?= empty($movie->rating) ? 'No rating' : htmlspecialchars($movie->rating); ?></p>
                <div class="movie-actions">
                    <a href="https://www.imdb.com/title/<?= htmlspecialchars($movie->id); ?>" class="imdb-link" target="_blank">View on IMDB</a>
                    <?php
                    // Build query string with current filters
                    $queryParams = $filters;
                    $queryParams['id'] = $movie->id;
                    $filterQuery = http_build_query($queryParams);
                    if (getenv('DEVMODE')):
                    ?>
                    <a href="pages/editmovie.php?<?= htmlspecialchars($filterQuery); ?>" class="edit-button">Edit</a>
                    <?php
                    endif;
                    $moviePath = $movie->path;
                    if (!empty($moviePath)) {
                        $mediaDrive = Database::getMediaDrive();
                        if (!str_starts_with($moviePath, $mediaDrive)) {
                            $moviePath = $mediaDrive . ltrim($moviePath, '/');
                        }
                        if (file_exists($moviePath)): 
                            if ($movie->timeWatched > 0):
                    ?>
                            <div class="view-options">
                                <a href="pages/watch.php?<?= htmlspecialchars($filterQuery); ?>&start=beginning" class="view-button">Start from beginning</a>
                                <a href="pages/watch.php?<?= htmlspecialchars($filterQuery); ?>" class="view-button continue">Continue (<?= gmdate("H:i:s", $movie->timeWatched); ?>)</a>
                            </div>
                    <?php else: ?>
                            <a href="pages/watch.php?<?= htmlspecialchars($filterQuery); ?>" class="view-button">View</a>
                        <?php
                            endif;
                            if (!$movie->hasSubtitles() && getenv('DEVMODE')): 
                        ?>
                            <span title="No subtitles available" class="subtitle-warning"></span>
                    <?php endif; ?>
                    <?php else: ?>
                        <span title="Movie file not found" class="movie-warning"></span>
                    <?php
                    endif;
                    } 
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php
    // Create query string for pagination links
    $queryParams = $_GET;
    
    if ($currentPage > 1):
        $queryParams['page'] = $currentPage - 1;
        $prevLink = '?' . http_build_query($queryParams);
    ?>
        <a href="<?= htmlspecialchars($prevLink) ?>" class="pagination-button">&laquo; Previous</a>
    <?php
    endif;

    // Show page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $queryParams['page'] = $i;
        $pageLink = '?' . http_build_query($queryParams);
        
        if ($i == $currentPage) {
            echo "<span class=\"pagination-current\">$i</span>";
        } else {
            echo "<a href=\"" . htmlspecialchars($pageLink) . "\" class=\"pagination-button\">$i</a>";
        }
    }
    if ($currentPage < $totalPages):
        $queryParams['page'] = $currentPage + 1;
        $nextLink = '?' . http_build_query($queryParams);
    ?>
        <a href="<?= htmlspecialchars($nextLink) ?>" class="pagination-button">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>