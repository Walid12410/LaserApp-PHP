<?php
require __DIR__ . "/../config/header.php"; // Adjust path as needed
require __DIR__ . "/../config/connection.php"; // Adjust path as needed
require __DIR__ . "/jwt_helper.php"; // Adjust path as needed

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, email, first_name, last_name, role, password FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Prepare payload (exclude password)
    $payload = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ];

    // Create JWT token
    $token = jwt_encode($payload, 3600 * 24); // valid 1 day

    // Determine cookie settings for current environment
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $cookieOptions = [
        'expires' => time() + 3600 * 24,
        'path' => '/',
        'httponly' => true,
        'secure' => $isSecure,
        'samesite' => $isSecure ? 'None' : 'Lax',
    ];

    $cookieDomain = getenv('COOKIE_DOMAIN');
    if ($cookieDomain !== false && $cookieDomain !== '') {
        $cookieOptions['domain'] = $cookieDomain;
    }

    setcookie('token', $token, $cookieOptions);

    unset($user['password']);
    echo json_encode(['user' => $user]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to process login']);
}

?>
