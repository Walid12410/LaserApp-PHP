<?php
require __DIR__.'/bootstrap.php';
require_auth();

$folder = trim($_GET['folder'] ?? 'All'); // not used for filtering in this simple version
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, min(100, (int)($_GET['limit'] ?? 24)));
$offset = ($page - 1) * $limit;

global $pdo;

$total = (int)$pdo->query("SELECT COUNT(*) FROM media")->fetchColumn();
$st = $pdo->prepare("SELECT id, filename, url, size, created_at
                     FROM media ORDER BY id DESC LIMIT :lim OFFSET :off");
$st->bindValue(':lim',  $limit, PDO::PARAM_INT);
$st->bindValue(':off',  $offset, PDO::PARAM_INT);
$st->execute();
$items = $st->fetchAll(PDO::FETCH_ASSOC);

json_out(['items'=>$items, 'total'=>$total]);
