<?php
if(session_status() === PHP_SESSION_NONE){
   session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "agritech";

try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $database . ";charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>