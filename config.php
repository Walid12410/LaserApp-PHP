<?php
declare(strict_types=1);

function env(string $key, string $default=''): string {
  static $env;
  if (!$env) {
    $path = __DIR__.'/.env';
    $env = file_exists($path) ? parse_ini_file($path) : [];
  }
  return $env[$key] ?? $default;
}

$pdo = new PDO(
  env('DB_DSN'),
  env('DB_USER'),
  env('DB_PASS'),
  [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
);

define('APP_BASE_URL', rtrim(env('APP_BASE_URL', ''), '/'));
define('CORS_ALLOW_ORIGIN', env('CORS_ALLOW_ORIGIN', '*'));
define('AUTH_DEMO_TOKEN', env('AUTH_DEMO_TOKEN', 'demo-token-123'));
define('PROCESSOR', env('PROCESSOR', 'MOCK')); // MOCK or GEMINI
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));

define('UPLOAD_DIR', __DIR__.'/uploads');
define('MEDIA_DIR',  __DIR__.'/media');

@is_dir(UPLOAD_DIR) || @mkdir(UPLOAD_DIR, 0775, true);
@is_dir(MEDIA_DIR)  || @mkdir(MEDIA_DIR,  0775, true);
