<?php
// db.php
$host = 'localhost';
$db = 'laser-app';
$user = 'laser'; // Use your DB username
$pass = '?%RyHpgPxg?9'; // Use your DB password

$conn = new mysqli($host, $user, $pass, $db);

// Check if the connection is successful
if ($conn->connect_error) {
    http_response_code(500); // Internal server error
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
?>
