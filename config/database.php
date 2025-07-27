<?php
$file = __DIR__ . '/../.env';

$envVariables = parse_ini_file($file);
foreach ($envVariables as $key => $value) {
    putenv("$key=$value");
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private static $mediaDrive;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
        if (self::$mediaDrive === null) {
            self::$mediaDrive = getenv('MEDIA_DRIVE');
        }
    }

    public static function getMediaDrive() {
        if (self::$mediaDrive === null) {
            self::$mediaDrive = getenv('MEDIA_DRIVE');
        }
        return self::$mediaDrive;
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>