<?php
require __DIR__ . '/../config/header.php';
require __DIR__ . '/../config/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$phone_number = trim($data['phone_number'] ?? '');
$role = 'admin'; // default role

if ($email === '' || $password === '' || $first_name === '' || $last_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Check if email exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, phone_number, role) VALUES (:first_name, :last_name, :email, :password, :phone_number, :role)');
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':password' => $hashed_password,
        ':phone_number' => $phone_number,
        ':role' => $role,
    ]);

    echo json_encode(['success' => 'User registered']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to register user']);
}
?>
