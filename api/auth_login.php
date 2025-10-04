<?php
require __DIR__.'/bootstrap.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
$pass  = trim($input['password'] ?? '');
if ($email === '' || $pass === '') json_out(['error'=>'missing email or password'], 400);

$st = $pdo->prepare("SELECT id, password_hash FROM users WHERE email=? LIMIT 1");
$st->execute([$email]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($pass, $user['password_hash'])) {
  json_out(['error'=>'invalid credentials'], 401);
}

$token = bin2hex(random_bytes(32)); // unique token for this user
$pdo->prepare("UPDATE users SET token=? WHERE id=?")->execute([$token, $user['id']]);

json_out(['token' => $token]);
