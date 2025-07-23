<?php
include 'db.php';

$action = $_GET['action'] ?? '';

if ($action == 'status_presentasi') {
    $kode = $_GET['kode'] ?? '';
    $stmt = $pdo->prepare("SELECT q.id AS id_quiz, s.id_soal, s.waktu_mulai, s.mode,
        t.soal, t.jawaban_a, t.jawaban_b, t.jawaban_c, t.jawaban_d, t.jawaban_benar, t.durasi, t.gambar
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
    echo json_encode($data ?: new stdClass());
    exit;
}

if ($action == 'jawab') {
    // Ambil data POST
    $id_peserta = $_POST['id_peserta'] ?? '';
    $id_soal = $_POST['id_soal'] ?? '';
    $jawaban = $_POST['jawaban'] ?? '';
    if (!$id_peserta || !$id_soal || !$jawaban) {
        echo json_encode(['status' => 'error', 'msg' => 'Data tidak lengkap']);
        exit;
    }
    // Cek jawaban benar/salah
    $stmt = $pdo->prepare("SELECT jawaban_benar FROM tb_soal WHERE id = ?");
    $stmt->execute([$id_soal]);
    $jawaban_benar = $stmt->fetchColumn();
    $benar = ($jawaban_benar && strtoupper($jawaban) == strtoupper($jawaban_benar)) ? 1 : 0;
    // Cek apakah sudah pernah menjawab
    $stmt = $pdo->prepare("SELECT id FROM tb_jawaban WHERE id_peserta = ? AND id_soal = ?");
    $stmt->execute([$id_peserta, $id_soal]);
    $id_jawaban = $stmt->fetchColumn();
    if ($id_jawaban) {
        // Update jawaban
        $stmt = $pdo->prepare("UPDATE tb_jawaban SET jawaban = ?, benar = ?, waktu_jawab = NOW() WHERE id = ?");
        $stmt->execute([$jawaban, $benar, $id_jawaban]);
    } else {
        // Insert jawaban baru
        $stmt = $pdo->prepare("INSERT INTO tb_jawaban (id_peserta, id_soal, jawaban, benar, waktu_jawab) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$id_peserta, $id_soal, $jawaban, $benar]);
    }
    echo json_encode([
        'status' => 'ok',
        'benar' => $benar,
        'jawaban_benar' => $jawaban_benar
    ]);
    exit;
} 