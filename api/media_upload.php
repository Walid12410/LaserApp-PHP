<?php
require __DIR__.'/bootstrap.php';
require_auth();

if (empty($_FILES['files'])) json_out(['error'=>'no files'], 400);

$files = $_FILES['files'];
$count = is_array($files['name']) ? count($files['name']) : 0;
if ($count < 1) json_out(['error'=>'no files'], 400);

global $pdo;
$uploaded = 0;

for ($i=0; $i<$count; $i++) {
  if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $files['name'][$i]);
  $tmp  = $files['tmp_name'][$i];
  $dest = MEDIA_DIR . "/$name";

  if (!move_uploaded_file($tmp, $dest)) continue;

  $url  = APP_BASE_URL . "/media/" . rawurlencode($name);
  $size = filesize($dest) ?: 0;

  $st = $pdo->prepare("INSERT INTO media (filename, url, size) VALUES (?,?,?)");
  $st->execute([$name, $url, $size]);
  $uploaded++;
}

json_out(['uploaded'=>$uploaded]);
