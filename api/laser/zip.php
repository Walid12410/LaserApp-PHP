<?php
require __DIR__.'/config.php';
$userId = require_auth($pdo);

$jobUid = preg_replace('/[^a-zA-Z0-9_]/','', $_POST['job'] ?? '');
if (!$jobUid) json_out(['error'=>'missing job'], 400);

$job = $pdo->prepare("SELECT id FROM jobs WHERE job_uid=? AND user_id=?");
$job->execute([$jobUid, $userId]);
$row = $job->fetch();
if (!$row) json_out(['error'=>'job not found'], 404);
$jobId = (int)$row['id'];

$jobDir = RESULT_DIR . "/$jobUid";
$zipPath = RESULT_DIR . "/$jobUid.zip";

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)
  json_out(['error'=>'zip open'], 500);

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jobDir));
foreach ($rii as $f) {
  if ($f->isDir()) continue;
  $zip->addFile($f->getPathname(), basename($f->getPathname()));
}
$zip->close();

$pdo->prepare("UPDATE jobs SET status='done', finished_at=NOW() WHERE id=?")->execute([$jobId]);

json_out(['ok'=>true,'zip'=>"/result/$jobUid.zip"]);

?>