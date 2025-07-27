<?php
$pagetitle = 'Poster Extractor - DeMoLi';
include '../includes/header.php';
?>
<div class="form-container">
    <a href="/index.php" class="back-button">Back to List</a>
    <form method="POST">
        <input type="text" name="imdb_url" placeholder="Enter IMDb mediaviewer URL" required>
        <input type="submit" value="Extract Image Name">
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $url = $_POST['imdb_url'];
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIE, 'uu=BCYs_c7OxvRSIT_dXgpOBOXQQPPiVhvRprZc4WO-2Aq7EyGJ2KpM6u7l4lFZV4WHMWLLwwLvd9Xv%0D%0A2QrOHV3nOMMx1GzUCZrOlGNKIHKxkFG-kBYS7mrn8ESbLDPGsoYJcabJgDp0WlA%3D%0D%0A');
    
    // Execute cURL session
    $html = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo '<div class="result">cURL Error: ' . curl_error($ch) . '</div>';
    }
    
    // Close cURL session
    curl_close($ch);

    if ($html) {
        // Extract the rm number from URL
        if (preg_match('/rm(\d+)/', $url, $matches)) {
            $rm_number = $matches[1];
            $search_id = 'rm' . $rm_number . '-curr';
            
            // Create a div with the HTML content
            echo '<div id="temp-container" style="display:none;">' . $html . '</div>';
            ?>
            <script>
            const container = document.getElementById("temp-container");
            const searchId = <?php echo json_encode($search_id); ?>;
            const img = container.querySelector(`img[data-image-id="${searchId}"]`);
            
            if (img && img.src) {
                const filename = img.src.split("/").pop();
                document.write(`
                    <div class="result">
                        <h3>Extracted Image Filename:</h3>
                        <p>${filename}</p>
                    </div>
                `);
            } else {
                document.write(`
                    <div class="result">
                        Could not find image with ID: ${searchId}
                    </div>
                `);
            }
            
            container.remove();
            </script>
            <?php
        } else {
            echo '<div class="result">Could not extract rm number from URL</div>';
        }
    } else {
        echo '<div class="result">Failed to fetch the IMDb page</div>';
    }
}
?>
</div>
<?php include '../includes/footer.php'; ?>