<?php
class Connect {
    private $u = "root";       // DB username
    private $p = "";           // DB password
    private $d = "hotel";      // Database name
    private $s = "localhost";  // Server
    protected $db_handle;

    public function __construct() {
        $this->db_handle = mysqli_connect($this->s, $this->u, $this->p, $this->d);
        if (!$this->db_handle) {
            die("Database connection failed: " . mysqli_connect_error());
        }
    }

    // Getter method to safely get the DB connection
    public function getConnection() {
        return $this->db_handle;
    }

    // ✅ Added for compatibility with searchroom1.php
    public function getDbHandle() {
        return $this->db_handle;
    }
}
?>
