<?php
// database.php - Database configuration and connection
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'gwa_calculator';
    private $connection;

    public function __construct() {
        $this->connect();
        $this->createTables();
    }

    private function connect() {
        try {
            // First try to connect without database to create it if it doesn't exist
            $this->connection = new PDO(
                "mysql:host={$this->host}",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Create database if it doesn't exist
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS {$this->database}");
            
            // Connect to the specific database
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    private function createTables() {
        // Create users table
        $usersTable = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Create semesters table (updated with user_id)
        $semesterTable = "
            CREATE TABLE IF NOT EXISTS semesters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                semester_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";

        // Create subjects table
        $subjectTable = "
            CREATE TABLE IF NOT EXISTS subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                semester_id INT,
                subject_name VARCHAR(255) NOT NULL,
                grade DECIMAL(3,2) NOT NULL,
                units DECIMAL(3,1) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
            )
        ";

        $this->connection->exec($usersTable);
        $this->connection->exec($semesterTable);
        $this->connection->exec($subjectTable);
    }

    public function getConnection() {
        return $this->connection;
    }
}