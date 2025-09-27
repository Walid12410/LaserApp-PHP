<?php
require __DIR__.'/config.php';
$userId = require_auth($pdo);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$opts = $in['options'] ?? null;   // ['dpi'=>600,'strength'=>60,'invert'=>0,'defaultCaption'=>'']
$uid  = new_job_uid();

$stmt = $pdo->prepare("INSERT INTO jobs (job_uid,user_id,status,options_json) VALUES (?,?, 'queued', ?)");
$stmt->execute([$uid, $userId, $opts ? json_encode($opts) : null]);

json_out(['ok'=>true,'jobId'=>$uid]);

?>