<?php
include 'db.php';

$action = $_GET['action'] ?? '';

if ($action == 'status_presentasi') {
    $kode = $_GET['kode'] ?? '';
    $stmt = $pdo->prepare("SELECT q.id AS id_quiz, s.id_soal, s.waktu_mulai, s.mode,
        t.soal, t.jawaban_a, t.jawaban_b, t.jawaban_c, t.jawaban_d, t.jawaban_benar, t.durasi
        FROM tb_quiz q
        JOIN tb_status_quiz s ON s.id_quiz = q.id
        LEFT JOIN tb_soal t ON t.id = s.id_soal
        WHERE q.kode_quiz = ?
        ORDER BY s.id DESC LIMIT 1");
    $stmt->execute([$kode]);
    $data = $stmt->fetch();
    if ($data) {
        // Hitung total soal
        $stmt2 = $pdo->prepare("SELECT id FROM tb_soal WHERE id_quiz = ? ORDER BY id ASC");
        $stmt2->execute([$data['id_quiz']]);
        $soals = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $data['total_soal'] = count($soals);
        // Cari index soal aktif
        $data['current_index'] = null;
        if ($data['id_soal']) {
            $idx = array_search($data['id_soal'], $soals);
            if ($idx !== false) $data['current_index'] = $idx + 1;
        }
    }
    if ($data && $data['mode'] === 'waiting') {
        // Tambahkan info peserta
        $stmt2 = $pdo->prepare("SELECT nama FROM tb_peserta WHERE id_quiz = ? ORDER BY waktu_join ASC");
        $stmt2->execute([$data['id_quiz']]);
        $data['peserta'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    }
    echo json_encode($data ?: []);
    exit;
}

if ($action == 'daftar_peserta') {
    $kode = $_GET['kode'] ?? '';
    $stmt = $pdo->prepare("SELECT q.id FROM tb_quiz q WHERE q.kode_quiz = ?");
    $stmt->execute([$kode]);
    $id_quiz = $stmt->fetchColumn();
    $peserta = [];
    if ($id_quiz) {
        $stmt2 = $pdo->prepare("SELECT nama FROM tb_peserta WHERE id_quiz = ? ORDER BY waktu_join ASC");
        $stmt2->execute([$id_quiz]);
        $peserta = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    }
    echo json_encode($peserta);
    exit;
}

if ($action == 'soal_aktif') {
    $kode = $_GET['kode'] ?? '';
    $stmt = $pdo->prepare("SELECT q.id AS id_quiz, s.id_soal, s.waktu_mulai, t.soal, t.jawaban_a, t.jawaban_b, t.jawaban_c, t.jawaban_d, t.durasi FROM tb_quiz q JOIN tb_status_quiz s ON s.id_quiz = q.id JOIN tb_soal t ON t.id = s.id_soal WHERE q.kode_quiz = ? ORDER BY s.id DESC LIMIT 1");
    $stmt->execute([$kode]);
    $data = $stmt->fetch();
    echo json_encode($data ?: new stdClass());
    exit;
} 