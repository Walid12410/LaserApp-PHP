<?php
require '../config/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$first_name = $conn->real_escape_string($data['first_name'] ?? '');
$last_name = $conn->real_escape_string($data['last_name'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';
$phone_number = $conn->real_escape_string($data['phone_number'] ?? '');
$role = 'admin'; // default role

if (!$email || !$password || !$first_name || !$last_name) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Check if email exists
$result = $conn->query("SELECT id FROM users WHERE email='$email'");
if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already registered']);
    exit;
}

// Hash password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $first_name, $last_name, $email, $hashed_password, $phone_number, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => 'User registered']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to register user']);
}
$stmt->close();
$conn->close();
?>
