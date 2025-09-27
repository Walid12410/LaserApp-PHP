<?php

require __DIR__ . "/../config/header.php";

// Clear token cookie by expiring it immediately
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$cookieOptions = [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'secure' => $isSecure,
    'samesite' => $isSecure ? 'None' : 'Lax',
];

$cookieDomain = getenv('COOKIE_DOMAIN');
if ($cookieDomain !== false && $cookieDomain !== '') {
    $cookieOptions['domain'] = $cookieDomain;
}

setcookie('token', '', $cookieOptions);

echo json_encode(['success' => 'Logged out']);
?>
