<?php
class Movie {
    private $conn;
    private $tab;
    public $table_name = "movie";
    public $id;
    public $title;
    public $originalTitle;
    public $image;
    public $year;
    public $length;
    public $rating;
    public $path;
    public $franchiseId;

    public function __construct($db) {
        if ($db instanceof PDO) {
            $this->conn = $db;
        } elseif ($db instanceof Database) {
            $this->conn = $db->getConnection();
        }
    }
    
    public function formatLength() {
        $hours = floor($this->length / 60);
        $minutes = $this->length % 60;
        if ($hours > 0) {
            return $hours . "h " . $minutes . "m";
        }
        return $minutes . "m";
    }

    public function getMovie($id) {
        try {
            $query = "SELECT MovieID, Title, OriginalTitle, Image, Year, Length, Rating, Path, FranchiseID 
                     FROM " . $this->table_name . " 
                     WHERE MovieID = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->id = $row['MovieID'];
                $this->title = $row['Title'];
                $this->originalTitle = $row['OriginalTitle'];
                $this->image = $row['Image'];
                $this->year = $row['Year'];
                $this->length = $row['Length'];
                $this->rating = $row['Rating'];
                $this->path = $row['Path'];
                $this->franchiseId = $row['FranchiseID'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    public function getMovies($page = 1, $itemsPerPage = 5, $filters = null) {
        try {
            $whereConditions = [];
            $params = [];

            if ($filters) {
                if (!empty($filters['franchise'])) {
                    $whereConditions[] = "FranchiseID = :franchise";
                    $params[':franchise'] = $filters['franchise'];
                }

                if (!empty($filters['search'])) {
                    $searchTerm = '%' . $filters['search'] . '%';
                    $whereConditions[] = "(Title LIKE :search OR OriginalTitle LIKE :search OR Year LIKE :search)";
                    $params[':search'] = $searchTerm;
                }
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total count first with filters
            $countQuery = "SELECT COUNT(*) FROM Movie " . $whereClause;
            $stmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalItems = $stmt->fetchColumn();
            
            // Calculate offset
            $offset = ($page - 1) * $itemsPerPage;

            // Determine ORDER BY clause based on sort option
            $orderBy = $this->getSortClause($filters['sort'] ?? null);

            $query = "SELECT MovieID, Title, OriginalTitle, Image, Year, Length, Rating, Path 
                     FROM Movie 
                     $whereClause
                     $orderBy 
                     LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $movies = [];
            foreach ($result as $row) {
                $movie = new Movie($this->conn);
                $movie->id = $row['MovieID'];
                $movie->title = $row['Title'];
                $movie->image = $row['Image'];
                $movie->year = $row['Year'];
                $movie->length = $row['Length'];
                $movie->rating = $row['Rating'];
                $movie->path = $row['Path'];
                $movies[] = $movie;
            }
            
            return [
                'movies' => $movies,
                'total' => $totalItems,
                'pages' => ceil($totalItems / $itemsPerPage),
                'current_page' => $page,
                'filtered_total' => $totalItems
            ];
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [
                'movies' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => 1,
                'filtered_total' => 0
            ];
        }
    }    private function getSortClause($sort) {
        // If no specific sort is selected but a franchise filter is active
        if (empty($sort) && !empty($_GET['franchise'])) {
            return 'ORDER BY Year ASC, Title ASC';
        }

        switch ($sort) {
            case 'title_asc':
                return 'ORDER BY Title ASC';
            case 'title_desc':
                return 'ORDER BY Title DESC';
            case 'year_desc':
                return 'ORDER BY Year DESC';
            case 'year_asc':
                return 'ORDER BY Year ASC';
            case 'length_desc':
                return 'ORDER BY Length DESC';
            case 'length_asc':
                return 'ORDER BY Length ASC';
            case 'rating_desc':
                return 'ORDER BY Rating DESC';
            case 'rating_asc':
                return 'ORDER BY Rating ASC';
            default:
                return 'ORDER BY Rating DESC, Year DESC, Title ASC';
        }
    }

    public function editMovie() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET Title = ?, OriginalTitle = ?, Image = ?, Year = ?, Length = ?, Rating = ?, Path = ?, FranchiseID = ?
                     WHERE MovieID = ?";
            
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute([
                $this->title,
                $this->originalTitle,
                $this->image,
                $this->year,
                $this->length,
                $this->rating,
                $this->path,
                $this->franchiseId,
                $this->id
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function addMovie() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (MovieID, Title, OriginalTitle, Image, Year, Length, Rating, Path, FranchiseID)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute([
                $this->id,
                $this->title,
                $this->originalTitle,
                $this->image,
                $this->year,
                $this->length,
                $this->rating,
                $this->path,
                $this->franchiseId
            ]);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function getFranchises() {
        try {
            $query = "SELECT FranchiseID, Name FROM franchise ORDER BY Name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }

    public function getTotalMovies() {
        try {
            $query = "SELECT COUNT(*) FROM Movie";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            return 0;
        }
    }

    public function hasSubtitles() {
        if (empty($this->path)) {
            return false;
        }
        $mediaDrive = Database::getMediaDrive();
        $fullPath = str_starts_with($this->path, $mediaDrive) 
            ? $this->path 
            : $mediaDrive . ltrim($this->path, '/');
        $srtPath = preg_replace('/\.mp4$/', '.srt', $fullPath);
        return file_exists($srtPath);
    }
}
?>