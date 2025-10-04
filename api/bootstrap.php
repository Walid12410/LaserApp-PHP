<?php
declare(strict_types=1);
require __DIR__.'/../config.php';

/* ---- CORS ---- */
header('Access-Control-Allow-Origin: '.CORS_ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

/* ---- helpers ---- */
function json_out($data, int $code=200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_SLASHES);
  exit;
}

function uid24(): string {
  return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
}

function require_auth(): array {
  global $pdo;

  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!str_starts_with($h, 'Bearer ')) json_out(['error'=>'unauthorized'], 401);
  $token = substr($h, 7);

  $st = $pdo->prepare("SELECT id, email FROM users WHERE token=? LIMIT 1");
  $st->execute([$token]);
  $user = $st->fetch(PDO::FETCH_ASSOC);

  if (!$user) json_out(['error'=>'invalid token'], 401);

  return $user; // so you can access user data in APIs if needed
}


function job_dir(string $uid): string {
  return UPLOAD_DIR . "/$uid";
}
