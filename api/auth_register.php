<?php
require __DIR__.'/bootstrap.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
$pass  = trim($input['password'] ?? '');

if ($email === '' || $pass === '') json_out(['error'=>'missing fields'], 400);

$hash = password_hash($pass, PASSWORD_BCRYPT);

$st = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?,?)");
try {
  $st->execute([$email, $hash]);
} catch (PDOException $e) {
  json_out(['error'=>'email already exists'], 409);
}

json_out(['message'=>'registered']);
