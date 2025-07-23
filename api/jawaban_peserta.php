<?php
include 'db.php';
header('Content-Type: application/json');
$id_peserta = $_GET['id_peserta'] ?? '';
$id_soal = $_GET['id_soal'] ?? '';
if (!$id_peserta || !$id_soal) {
  echo json_encode(['jawaban' => null, 'benar' => 0, 'jawaban_benar' => null]);
  exit;
}
// Ambil jawaban peserta
$stmt = $pdo->prepare("SELECT jawaban, benar FROM tb_jawaban WHERE id_peserta = ? AND id_soal = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id_peserta, $id_soal]);
$row = $stmt->fetch();
$jawaban = $row ? $row['jawaban'] : null;
$benar = $row ? (int)$row['benar'] : 0;
// Ambil jawaban benar
$stmt = $pdo->prepare("SELECT jawaban_benar FROM tb_soal WHERE id = ?");
$stmt->execute([$id_soal]);
$jawaban_benar = $stmt->fetchColumn();
echo json_encode([
  'jawaban' => $jawaban,
  'benar' => $benar,
  'jawaban_benar' => $jawaban_benar
]); 