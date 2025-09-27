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

if (!isset($_FILES['file'])) json_out(['error'=>'no file'], 400);
$caption  = trim($_POST['caption'] ?? '');
$dpi      = max(72, min(1200, (int)($_POST['dpi'] ?? 600)));
$strength = max(0,  min(100,  (int)($_POST['strength'] ?? 60)));
$invert   = (int)($_POST['invert'] ?? 0) === 1;

$origName = $_FILES['file']['name'];
$tmpPath  = $_FILES['file']['tmp_name'];

$jobDir = RESULT_DIR . "/$jobUid";
@mkdir($jobDir, 0775, true);

$saveOrig = UPLOAD_DIR . '/' . uniqid('up_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $origName);
move_uploaded_file($tmpPath, $saveOrig);

$pdo->prepare("INSERT INTO job_files (job_id, original_name, original_path, status) VALUES (?,?,?, 'processing')")
    ->execute([$jobId, $origName, $saveOrig]);
$fileId = (int)$pdo->lastInsertId();

try {
  $im = new Imagick();
  $im->readImage($saveOrig);
  $im->setImageResolution($dpi,$dpi);
  $im->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
  $im->setImageColorspace(Imagick::COLORSPACE_GRAY);
  $im->modulateImage(100,160,100);
  if (method_exists($im,'claheImage')) $im->claheImage(8,8,256,3);

  $im->thresholdImage(Imagick::getQuantum() * ($strength/100.0));
  if ($invert) $im->negateImage(false);

  if ($caption !== '') {
    $draw = new ImagickDraw();
    $draw->setFillColor('black');
    $draw->setFontSize(max(36, intval($dpi/12)));
    $m = $im->queryFontMetrics($draw, $caption);
    $padTop=60; $padBottom=80;
    $W=$im->getImageWidth(); $H=$im->getImageHeight()+$m['textHeight']+$padTop+$padBottom;
    $canvas=new Imagick(); $canvas->newImage($W,$H,'white'); $canvas->setImageFormat('png');
    $canvas->compositeImage($im, Imagick::COMPOSITE_DEFAULT, 0, 0);
    $x=($W-$m['textWidth'])/2; $y=$H-$padBottom;
    $canvas->annotateImage($draw,$x,$y,0,$caption);
    $im->clear(); $im->destroy(); $im=$canvas;
  }

  $base = pathinfo($origName, PATHINFO_FILENAME);
  $outPng = $jobDir . '/' . $base . '.png';
  $im->setImageFormat('png'); $im->stripImage(); $im->writeImage($outPng);

  $publicPng = "/result/$jobUid/" . basename($outPng);

  $pdo->prepare("UPDATE job_files SET result_png=?, status='done', finished_at=NOW() WHERE id=?")
      ->execute([$publicPng, $fileId]);

  json_out(['ok'=>true,'png'=>$publicPng]);

} catch (Throwable $e) {
  $pdo->prepare("UPDATE job_files SET status='failed', error_msg=? WHERE id=?")
      ->execute([substr($e->getMessage(),0,250), $fileId]);
  json_out(['error'=>'process failed'], 500);
}

?>