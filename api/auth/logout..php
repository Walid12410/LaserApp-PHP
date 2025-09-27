<?php

require __DIR__ . "/../config/header.php"; // Adjust path as needed

// Clear token cookie by setting expiry to past
// setcookie('token', '', [
//     'expires' => time() - 3600,
//     'path' => '/',
//     'domain' => '', // ✅ must match the cookie domain used in login
//     'secure' => true,                  // ✅ enforce HTTPS
//     'httponly' => true,
//     'samesite' => 'None'               // ✅ match login's SameSite policy
// ]);

// dev cookies
setcookie('token', $token, [
    'expires' => time() + 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

echo json_encode(['success' => 'Logged out']);
?>
