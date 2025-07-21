<?php
include_once 'db.php';
$id_soal = $_GET['id_soal'] ?? null;
if (!$id_soal) {
  echo json_encode(["A"=>0,"B"=>0,"C"=>0,"D"=>0]);
  exit;
}
$stmt = $pdo->prepare("SELECT jawaban, COUNT(*) as jumlah FROM tb_jawaban WHERE id_soal = ? GROUP BY jawaban");
$stmt->execute([$id_soal]);
$data = ["A"=>0,"B"=>0,"C"=>0,"D"=>0];
foreach ($stmt->fetchAll() as $row) {
  $data[$row['jawaban']] = (int)$row['jumlah'];
}
header('Content-Type: application/json');
echo json_encode($data); 