<?php
ini_set('max_execution_time', '300');

require_once '../config/database.php';
require_once '../classes/movie.php';

// Array of words to search for in SRT files
$searchWords = [
    'subtitle',//gehad
    // 'torrent',//gehad
    // 'yts',//gehad
    // 'mkv',//gehad
    // '720p',//gehad
    // '1080p',//gehad
    // '2160p',//gehad
    // 'www.',//gehad
    // '.com',//gehad
    // '.nl',//gehad
    // '.co.uk',//gehad
    // '.org',//gehad
    // 'yify',//gehad
    // 'srt',//gehad
    // 'SDH',//gehad
    // 'mp4',//gehad
    // '©',//gehad
    // '@',//gehad
    // 'Ã',//gehad
    // '{',//gehad
    // 'an8',//gehad
    // '/fad',//gehad
    // '°',//gehad
    // '¡',//gehad
    // '±',//gehad
    // '知',//gehad
    // '´',//gehad
    // '—',//gehad
    // 'I"m',//gehad
    // 'they"re',//gehad
    // 'it"s',//gehad
    // 'i"ll',//gehad
    // 'i"ve',//gehad
    // 'can"t',//gehad
    // 'you"re',//gehad
    // 'don"t',//gehad
    // 'we"re',//gehad
    // 'how"s',//gehad
    // 'he"s',//gehad
    // 'she"s',//gehad
    // 'that"s',//gehad
    // 'what"s',//gehad
    // 'who"s',//gehad
    // 'there"s',//gehad
    // 'where"s',//gehad
    // 'when"s',//gehad
    // 'why"s',//gehad
    // 'you"ll',//gehad
    // 'you"ve',//gehad
    // 'doesn"t',//gehad
    // 'wasn"t',//gehad
    // 'weren"t',//gehad
    // 'hasn"t',//gehad
    // 'haven"t',//gehad
    // 'hadn"t',//gehad
    // 'won"t',//gehad

    // 'Ieave',
    // 'foIks',
    // "couIdn't",
    // 'rightfuIIy',
    // 'pIease',
    // "shouId've",
    // 'scaIIop',
    // 'settIe',
    // 'goofbaII',
    // ' aIso ',
    // "l'm",
    // "l've",
    // " lt ",
    // " lf ",
    // "lt'll",
    // " lt's ",
    // "l'll",
    // " aII ",
    // "whoIe",
    // "feII",

    // 'totaIIy',
    // 'reaIIy',
    // ' caII',
    // ' weII',
    // ' biII',
    // 'miIIion',
    // 'kiII',
    // 'stiII',
    // 'uItimate',
    // ' Iet',
    // 'Iike',
    // 'Iive',
    // 'Iaugh',
    // ' Iong',
    // 'Iist',
    // 'IocaI',
    // 'Iand',
    // 'Ieft',
    // ' Iast',
    // 'Iine',
    // 'Iarge',
    // 'Iook',
    // 'Iove',
    // 'Iose',
    // 'Iight',
    // ' Iow',
    // 'Iogic',
    // 'Ioad',
    // 'Iuck',
    // 'Iearned',
    // ' Iate',
    // 'Iacked',
    // 'IoseIy',
    // 'faII',
    // 'caIIed',
    // 'caIIing',
    // 'wiII',
    // 'tlme',
    // 'tlck',
    // " l'd",
    // " l'dve",
    // " l'd've",
    // " l don't",
    // ' l do',
    // ' l can',
    // ' l guess',
    // ' l know',
    // ' l hope',
    // ' l wish',

    // '</font>',//gehad
    // '</b>',//gehad
    // 'Sync &',//gehad
    // 'Sync and',//gehad
    // 'subscene',//gehad
    // 'ENGLISH - US',//gehad
    // 'English - US - SDH',//gehad
    // 'English ESL',//gehad
    // 'BLURAY',//gehad
    // 'it was a pleasure sharing',//gehad
    // 'thanks for watching',//gehad
    // 'thank you for watching',//gehad
    // 'explosiveskull',//gehad
    // 'GoldenBeard',//gehad
    // 'Fidel33',//gehad
    // 'subs by',//gehad
    // 'subbed by',//gehad
    // 'subtitles by',//gehad
    // 'synchronization by',//gehad
    // 'synchronized by',//gehad
    // 'synced by',//gehad
    // 'ripped by',//gehad
    // 'encoded by',//gehad
    // 'IMPROVED BY',//gehad
    // 'Direct Download',//gehad
    // 'Movies and TV Shows',//gehad
    // 'Subs by',//gehad
    // 'sync by',//gehad
    // 'sub by',//gehad
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
foreach ($movies as $movie) {
    if (empty($movie['Path'])) continue;

    // Get full path to movie file
    $moviePath = $movie['Path'];
    $mediaDrive = Database::getMediaDrive();
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
            <?php if ($srtcount > 275) break; ?>
            <div class="result-item">
                <h3><?= $srtcount . ' - ' . htmlspecialchars($title) ?></h3>
                <div class="movie-path"><?= htmlspecialchars($data['path']) ?></div>
                <div class="words-found">
                    Words found: <?= htmlspecialchars(implode(', ', $data['words'])) ?>
                </div>
            </div>
            <?php $srtcount++; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>