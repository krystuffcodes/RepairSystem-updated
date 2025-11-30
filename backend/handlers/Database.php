<?php 
class Database {
    private $mysqli;

    public function __construct() {
        $config = include(__DIR__.'/../../database/database.php');

        $this->mysqli = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        if ($this->mysqli->connect_error) {
            throw new Exception("MySQLi Connection failed: " . $this->mysqli->connect_error);
        }
    }

    public function getConnection() {
        return $this->mysqli;
    }

    public function closeConnection() {
        $this->mysqli->close();
    }
}
?>