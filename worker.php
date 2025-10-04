<?php
declare(strict_types=1);
require __DIR__.'/config.php';

global $pdo;

/* ------ Local mock processor (Imagick line-art) ------ */
function line_art_mock(string $inPath, string $outPath): void {
  $im = new Imagick($inPath);
  $im->setImageColorspace(Imagick::COLORSPACE_GRAY);
  $im->modulateImage(100, 0, 100); // desaturate
  $im->edgeImage(1);                // edges
  $range = Imagick::getQuantumRange();
  $threshold = 0.60 * ($range['quantumRangeLong'] ?? 65535);
  $im->thresholdImage((int)$threshold);
  $im->setImageFormat('png');
  $im->writeImage($outPath);
  $im->destroy();
}

/* ------ Gemini processor (replace endpoint as Google updates) ------ */
function gemini_generate_art(string $inPath, string $outPath, string $style): void {
  if (GEMINI_API_KEY === '') throw new RuntimeException('GEMINI_API_KEY missing.');

  $prompt = match (strtolower($style)) {
    'line-art' => 'Convert this photo into clean black-and-white line art suitable for laser engraving. No background, no gray shading, crisp outlines only.',
    'etching'  => 'Stylize this image as a vintage etching. High contrast, hatching, minimal tones.',
    'sketch'   => 'Make a clean pencil sketch with strong contours and no background.',
    default    => 'Convert this image into high-contrast line art for laser engraving.'
  };

  $bytes = file_get_contents($inPath);
  if ($bytes === false) throw new RuntimeException("Cannot read $inPath");
  $b64 = base64_encode($bytes);

  // Using 1.5 Flash (update to 2.x/flash-image if you prefer)
  $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=".urlencode(GEMINI_API_KEY);
  $payload = [
    'contents' => [[
      'parts' => [
        ['text' => $prompt],
        ['inline_data' => [
          'mime_type' => 'image/jpeg',
          'data' => $b64
        ]]
      ]
    ]]
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 120
  ]);
  $res = curl_exec($ch);
  if ($res === false) throw new RuntimeException('Gemini call failed: '.curl_error($ch));
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  $json = json_decode($res, true);
  if ($code >= 400 || !isset($json['candidates'][0]['content']['parts'])) {
    throw new RuntimeException("Gemini error: $res");
  }

  // Find first inline image in response
  $parts = $json['candidates'][0]['content']['parts'];
  $outData = null;
  foreach ($parts as $p) {
    if (isset($p['inline_data']['data'])) {
      $outData = base64_decode($p['inline_data']['data']);
      break;
    }
  }
  if (!$outData) throw new RuntimeException('Gemini: no image returned');

  file_put_contents($outPath, $outData);
}

/* ------ Main loop ------ */
$BATCH = 4;

$jobs = $pdo->query("
  SELECT j.id, j.job_uid, j.style
  FROM jobs j
  WHERE j.status IN ('processing')
  ORDER BY j.created_at ASC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

if (!$jobs) { echo "no jobs\n"; exit; }

foreach ($jobs as $job) {
  $dir = UPLOAD_DIR . "/{$job['job_uid']}";
  $q = $pdo->prepare("SELECT * FROM job_files WHERE job_id=? AND status='queued' LIMIT $BATCH");
  $q->execute([$job['id']]);
  $batch = $q->fetchAll(PDO::FETCH_ASSOC);

  if (!$batch) {
    $left = $pdo->prepare("SELECT COUNT(*) FROM job_files WHERE job_id=? AND status IN ('queued','working')");
    $left->execute([$job['id']]);
    if ((int)$left->fetchColumn() === 0) {
      $succ = $pdo->prepare("SELECT COUNT(*) FROM job_files WHERE job_id=? AND status='done'");
      $succ->execute([$job['id']]);
      $pdo->prepare("UPDATE jobs SET status=? WHERE id=?")
          ->execute([$succ->fetchColumn()>0 ? 'done' : 'error', $job['id']]);
    }
    continue;
  }

  foreach ($batch as $row) {
    $pdo->prepare("UPDATE job_files SET status='working' WHERE id=?")->execute([$row['id']]);

    $inPath  = "$dir/in/{$row['src_filename']}";
    $outName = pathinfo($row['src_filename'], PATHINFO_FILENAME) . "-art.png";
    $outPath = "$dir/out/$outName";
    @mkdir("$dir/out", 0775, true);

    try {
      if (PROCESSOR === 'GEMINI') {
        gemini_generate_art($inPath, $outPath, $job['style']);
      } else {
        line_art_mock($inPath, $outPath);
      }

      $pdo->prepare("UPDATE job_files SET status='done', out_filename=? WHERE id=?")
          ->execute([$outName, $row['id']]);

    } catch (Throwable $e) {
      $pdo->prepare("UPDATE job_files SET status='error', error=? WHERE id=?")
          ->execute([substr($e->getMessage(),0,1000), $row['id']]);
    }
  }
}

echo "processed\n";
