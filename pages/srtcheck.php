<?php
ini_set('max_execution_time', '300');

require_once '../config/database.php';
require_once '../classes/movie.php';

// Array of words to search for in SRT files
$searchWords = [
    // 'subtitle',
    // 'torrent',
    // 'yts',
    // 'mkv',
    // '720p',
    // '1080p',
    // '2160p',
    // 'www.',
    // '.com',
    // '.nl',
    // '.co.uk',
    // '.org',
    // 'yify',
    // 'srt',
    // 'SDH',
    // 'mp4',
    // '©',
    // '@',
    // 'Ã',
    // '{',
    // 'an8',
    // '/fad',
    // '’',
    // '‘',
    // '“',
    // '”',
    // '€',
    // '™',
    // '…',
    // 'œ',
    // 'ª',
    // '♪',
    // '—',
    // 'subcaptions',
    // '</font>',
    // '</b>',
    // 'Sync &',
    // 'Sync and',
    // 'subscene',
    // 'ENGLISH - US',
    // 'English - US - SDH',
    // 'English ESL',
    // 'BLURAY',
    // 'it was a pleasure sharing',
    // 'explosiveskull',
    // 'GoldenBeard',
    // 'Fidel33',
    // 'subs by',
    // 'subbed by',
    // 'subtitles by',
    // 'synchronization by',
    // 'synchronized by',
    // 'synced by',
    // 'ripped by',
    // 'encoded by',
    // 'Subs by',
    // 'sync by',
    // 'sub by',
    // 'ß',
    // '£',
    // '¿',
    // 'ø',
    // 'á',
    // 'à',
    // 'ä',
    // 'â',
    // 'ã',
    // 'å',
    // 'Ā',
    // 'ā',
    // 'đ',
    // 'é',
    // 'è',
    // 'ê',
    // 'ë',
    // 'ē',
    // 'í',
    // 'ì',
    // 'î',
    // 'ï',
    // 'ǐ',
    // 'ó',
    // 'ò',
    // 'ô',
    // 'ö',
    // 'ō',
    // 'ú',
    // 'ù',
    // 'û',
    // 'ü',
    // 'ū',
    // 'ç',
    // 'č',
    // 'ñ',
    // 'š',
    // 'ž',
    // 'ł',
    // '�',
    // "lt'll",
    // "Lt's",
    // '. . .',
    // 'Α',
    // 'Β',
    // 'Ε',
    // 'Η',
    // 'Ι',
    // 'Κ',
    // 'Μ',
    // 'Ν',
    // 'Ο',
    // 'Ρ',
    // 'Τ',
    // 'Χ',
    // 'Υ',
    // 'Ζ',
    // 'α',
    // 'ε',
    // 'ι',
    // 'κ',
    // 'ν',
    // 'ο',
    // 'ρ',
    // 'υ',
    // 'χ'
];

// Initialize database connection
$database = new Database();
$movie = new Movie($database->getConnection());

// Get all movies with paths
$query = "SELECT MovieID, Title, Path FROM movie WHERE Path IS NOT NULL AND Path != ''";
$stmt = $database->getConnection()->prepare($query);
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];

// Process each movie
$mediaDrive = Database::getMediaDrive();
foreach ($movies as $movie) {
    if (empty($movie['Path'])) continue;

    // Get full path to movie file
    $moviePath = $movie['Path'];
    if (!str_starts_with($moviePath, $mediaDrive)) {
        $moviePath = $mediaDrive . ltrim($moviePath, '/');
    }

    // Get SRT path by replacing .mp4 with .srt
    $srtPath = preg_replace('/\.mp4$/', '.srt', $moviePath);

    // Check if SRT exists
    if (!file_exists($srtPath)) {
        continue;
    }

    // Read SRT content
    $srtContent = file_get_contents($srtPath);
    if ($srtContent === false) {
        continue;
    }

    // Check if last line is empty
    $lines = explode("\n", $srtContent);
    // Remove any empty lines at the end
    while (count($lines) > 0 && trim(end($lines)) === '') {
        if (!isset($results[$movie['Title']])) {
            $results[$movie['Title']] = [
                'path' => $movie['Path'],
                'words' => [],
                'emptyLastLine' => true
            ];
        } else {
            $results[$movie['Title']]['emptyLastLine'] = true;
        }
        break;
    }

    // Search for each word
    foreach ($searchWords as $word) {
        if (stripos($srtContent, $word) !== false) {
            if (!isset($results[$movie['Title']])) {
                $results[$movie['Title']] = [
                    'path' => $movie['Path'],
                    'words' => []
                ];
            }
            $results[$movie['Title']]['words'][] = $word;
        }
    }
}

$pagetitle = 'SRT Check - DeMoLi';
include '../includes/header.php';
?>
<div class="results-container">
    <a href="/index.php" class="back-button">Back to List</a>
    <h1>SRT Check Results</h1>
    <?php if (empty($results)): ?>
        <p>No matches found in any SRT files.</p>
    <?php else: ?>
        <?php $srtcount = 1; ?>
        <?php foreach ($results as $title => $data): ?>
            <?php if ($srtcount > 100) break; ?>
            <div class="result-item">
                <h3><?= $srtcount . ' - ' . htmlspecialchars($title) ?></h3>
                <div class="movie-path"><?= $mediaDrive . htmlspecialchars(dirname($data['path'])) ?></div>
                <div class="words-found">
                    Words found: <?= htmlspecialchars(implode(', ', $data['words'])) ?>
                </div>
            </div>
            <?php $srtcount++; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>