<?php
declare(strict_types=1);
require __DIR__ . '/../config/connection.php';   // provides $pdo (PDO MySQL)
require __DIR__ . '/../auth/jwt_helper.php';     // your JWT encode/decode

// folders (match your structure)
define('UPLOAD_DIR', realpath(__DIR__ . '/../../upload') ?: (__DIR__ . '/../../upload'));
define('RESULT_DIR', realpath(__DIR__ . '/../../result') ?: (__DIR__ . '/../../result'));
@mkdir(UPLOAD_DIR, 0775, true);
@mkdir(RESULT_DIR, 0775, true);

function json_out($data, int $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data); exit;
}

function require_auth(PDO $pdo){
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!preg_match('/Bearer\s+(.+)/i', $hdr, $m)) json_out(['error'=>'unauthorized'], 401);
  $token = trim($m[1]);
  $payload = jwt_decode($token);                   // implement in jwt_helper.php
  if (!$payload || empty($payload['uid'])) json_out(['error'=>'unauthorized'], 401);
  // verify user exists & active (optional)
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id=?");
  $stmt->execute([$payload['uid']]);
  if (!$stmt->fetch()) json_out(['error'=>'unauthorized'], 401);
  return (int)$payload['uid'];
}

function new_job_uid(): string {
  return 'jb_' . bin2hex(random_bytes(8));
}
