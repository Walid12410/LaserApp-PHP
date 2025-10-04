<?php
require __DIR__.'/bootstrap.php';
require_auth();

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $style = $_POST['style'] ?? 'line-art';
  if (empty($_FILES['images'])) json_out(['error'=>'no images uploaded'], 400);

  $uid = uid24();
  $dir = job_dir($uid);
  @mkdir("$dir/in", 0775, true);
  @mkdir("$dir/out", 0775, true);

  $pdo->prepare("INSERT INTO jobs (job_uid, status, style) VALUES (?,?,?)")
      ->execute([$uid, 'pending', $style]);
  $jobId = (int)$pdo->lastInsertId();

  $files = $_FILES['images'];
  $count = is_array($files['name']) ? count($files['name']) : 0;
  if ($count < 1 || $count > 100) json_out(['error'=>'upload between 1 and 100 images'], 400);

  for ($i=0; $i<$count; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
    $name = preg_replace('/[^A-Za-z0-9._-]/','_', $files['name'][$i]);
    $dest = "$dir/in/$name";
    move_uploaded_file($files['tmp_name'][$i], $dest);

    $pdo->prepare("INSERT INTO job_files (job_id, src_filename, status) VALUES (?,?, 'queued')")
        ->execute([$jobId, basename($dest)]);
  }

  $pdo->prepare("UPDATE jobs SET status='processing' WHERE id=?")->execute([$jobId]);
  json_out(['job_uid'=>$uid, 'status'=>'processing', 'count'=>$count], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $uid = $_GET['uid'] ?? '';
  if (!$uid) json_out(['error'=>'missing uid'], 400);

  $st = $pdo->prepare("SELECT * FROM jobs WHERE job_uid=?");
  $st->execute([$uid]);
  $job = $st->fetch(PDO::FETCH_ASSOC);
  if (!$job) json_out(['error'=>'not found'], 404);

  $st = $pdo->prepare("SELECT src_filename, out_filename, status, error FROM job_files WHERE job_id=? ORDER BY id");
  $st->execute([$job['id']]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $base = APP_BASE_URL . "/uploads/$uid";
  $files = array_map(function($r) use($base){
    return [
      'src'    => "$base/in/{$r['src_filename']}",
      'out'    => $r['out_filename'] ? "$base/out/{$r['out_filename']}" : null,
      'status' => $r['status'],
      'error'  => $r['error']
    ];
  }, $rows);

  json_out([
    'job'   => ['uid'=>$uid, 'status'=>$job['status'], 'style'=>$job['style'], 'message'=>$job['message']],
    'files' => $files
  ]);
}

json_out(['error'=>'method not allowed'], 405);
