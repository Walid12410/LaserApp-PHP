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
  // Temporary bypass of JWT auth to ease testing flows.
  // TODO: restore the JWT validation logic when auth is needed again.
  $fallbackId = getenv('TEST_USER_ID');
  if ($fallbackId === false || !ctype_digit($fallbackId)) {
    $fallbackId = '1';
  }
  return (int)$fallbackId;
}

function new_job_uid(): string {
  return 'jb_' . bin2hex(random_bytes(8));
}
