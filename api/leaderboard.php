<?php
include_once 'db.php';
$kode = $_GET['kode'] ?? '';
if (!$kode) {
  echo json_encode([]);
  exit;
}
$stmt = $pdo->prepare("SELECT id FROM tb_quiz WHERE kode_quiz = ?");
$stmt->execute([$kode]);
$id_quiz = $stmt->fetchColumn();
if (!$id_quiz) {
  echo json_encode([]);
  exit;
}
$stmt = $pdo->prepare("SELECT nama, skor FROM tb_peserta WHERE id_quiz = ? ORDER BY skor DESC, waktu_join ASC");
$stmt->execute([$id_quiz]);
$peserta = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($peserta); 