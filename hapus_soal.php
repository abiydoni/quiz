<?php
include_once 'api/db.php';
$id = $_GET['id'] ?? null;
$kode = $_GET['kode'] ?? '';
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM tb_soal WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: host.php?kode=' . urlencode($kode));
exit; 