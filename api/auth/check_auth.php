<?php
require __DIR__ . "/../config/header.php"; // Adjust path as needed
require './auth.php';

// This will:
$user = check_auth(); // returns user array or sends 401 error + exit

// Now you have user info, e.g.:
echo json_encode([
    'message' => 'You are authenticated!',
    'user' => $user
]);

?>