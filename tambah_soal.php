<?php
include_once 'api/db.php';
$id_quiz = $_GET['id_quiz'] ?? null;
$kode = $_GET['kode'] ?? '';
if (!$id_quiz) die('Quiz tidak ditemukan.');

// Ambil info quiz
$stmt = $pdo->prepare("SELECT nama_quiz FROM tb_quiz WHERE id = ?");
$stmt->execute([$id_quiz]);
$quiz = $stmt->fetch();
if (!$quiz) die('Quiz tidak ditemukan.');

$upload_error = '';
$gambar_nama = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soal = trim($_POST['soal'] ?? '');
    $a = trim($_POST['jawaban_a'] ?? '');
    $b = trim($_POST['jawaban_b'] ?? '');
    $c = trim($_POST['jawaban_c'] ?? '');
    $d = trim($_POST['jawaban_d'] ?? '');
    $benar = $_POST['jawaban_benar'] ?? 'A';
    $durasi = intval($_POST['durasi'] ?? 20);
    // Handle upload gambar
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $gambar_nama = uniqid('soal_') . '.' . $ext;
            $tujuan = __DIR__ . '/assets/soal/' . $gambar_nama;
            if (!is_dir(__DIR__ . '/assets/soal/')) mkdir(__DIR__ . '/assets/soal/', 0777, true);
            move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan);
        } else {
            $upload_error = 'Format gambar tidak didukung.';
        }
    }
    if ($soal && $a && $b && $c && $d && in_array($benar, ['A','B','C','D']) && $durasi > 0 && !$upload_error) {
        $stmt = $pdo->prepare("INSERT INTO tb_soal (id_quiz, soal, jawaban_a, jawaban_b, jawaban_c, jawaban_d, jawaban_benar, durasi, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_quiz, $soal, $a, $b, $c, $d, $benar, $durasi, $gambar_nama]);
        header("Location: host.php?kode=" . urlencode($kode));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Soal - <?= htmlspecialchars($quiz['nama_quiz']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    .dropzone {
      border: 2px dashed #6366f1;
      background: #f1f5f9;
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .dropzone.dragover { border-color: #22d3ee; background: #e0f2fe; }
    .img-preview { max-height: 200px; margin: 0 auto 1rem auto; display: block; border-radius: 0.5rem; box-shadow: 0 2px 8px #0001; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-100 to-blue-200">
  <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8 fade-in">
    <h2 class="text-2xl font-bold text-indigo-700 mb-6 text-center flex items-center justify-center gap-2">
      <i class="fa-solid fa-plus-circle"></i> Tambah Soal ke Quiz: <?= htmlspecialchars($quiz['nama_quiz']) ?>
    </h2>
    <?php if ($upload_error): ?>
      <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">Gagal upload gambar: <?= htmlspecialchars($upload_error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-4" id="formSoal">
      <div id="dropzone" class="dropzone flex flex-col items-center justify-center p-6 mb-2">
        <input type="file" name="gambar" id="gambarInput" accept="image/*" class="hidden">
        <img id="imgPreview" class="img-preview hidden max-h-32 max-w-xs" alt="Preview Gambar">
        <button type="button" id="btnHapusGambar" class="hidden mt-2 px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm flex items-center gap-2"><i class="fa-solid fa-trash"></i> Hapus Gambar</button>
        <div id="dropText" class="text-indigo-600 text-lg font-semibold flex flex-col items-center gap-2">
          <i class="fa-solid fa-image text-3xl"></i>
          Klik atau drag gambar ke sini untuk upload (opsional)
        </div>
      </div>
      <div>
        <label class="block font-semibold mb-1">Soal</label>
        <textarea name="soal" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-400" placeholder="Tulis soal di sini..."></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold mb-1">Pilihan A</label>
          <input type="text" name="jawaban_a" required class="w-full p-2 border rounded-lg">
        </div>
        <div>
          <label class="block font-semibold mb-1">Pilihan B</label>
          <input type="text" name="jawaban_b" required class="w-full p-2 border rounded-lg">
        </div>
        <div>
          <label class="block font-semibold mb-1">Pilihan C</label>
          <input type="text" name="jawaban_c" required class="w-full p-2 border rounded-lg">
        </div>
        <div>
          <label class="block font-semibold mb-1">Pilihan D</label>
          <input type="text" name="jawaban_d" required class="w-full p-2 border rounded-lg">
        </div>
      </div>
      <div class="flex gap-4 items-center">
        <label class="font-semibold">Jawaban Benar:</label>
        <select name="jawaban_benar" class="p-2 border rounded-lg">
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
          <option value="D">D</option>
        </select>
        <label class="font-semibold ml-4">Durasi (detik):</label>
        <input type="number" name="durasi" min="5" max="120" value="20" class="w-20 p-2 border rounded-lg">
      </div>
      <div class="flex gap-4 mt-4">
        <button type="submit" class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-lg transition-all">
          <i class="fa-solid fa-check"></i> Simpan Soal
        </button>
        <a href="host.php?kode=<?= htmlspecialchars($kode) ?>" class="flex-1 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded-lg text-lg text-center transition-all">
          <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
      </div>
    </form>
  </div>
  <script>
    const dropzone = document.getElementById('dropzone');
    const gambarInput = document.getElementById('gambarInput');
    const imgPreview = document.getElementById('imgPreview');
    const dropText = document.getElementById('dropText');
    const btnHapusGambar = document.getElementById('btnHapusGambar');

    dropzone.addEventListener('click', () => gambarInput.click());
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', e => { e.preventDefault(); dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', e => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        gambarInput.files = e.dataTransfer.files;
        showPreview();
      }
    });
    gambarInput.addEventListener('change', showPreview);
    function showPreview() {
      if (gambarInput.files && gambarInput.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          imgPreview.src = e.target.result;
          imgPreview.classList.remove('hidden');
          dropText.classList.add('hidden');
          btnHapusGambar.classList.remove('hidden');
        };
        reader.readAsDataURL(gambarInput.files[0]);
      } else {
        imgPreview.classList.add('hidden');
        dropText.classList.remove('hidden');
        btnHapusGambar.classList.add('hidden');
      }
    }
    btnHapusGambar.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      gambarInput.value = '';
      imgPreview.src = '';
      imgPreview.classList.add('hidden');
      dropText.classList.remove('hidden');
      btnHapusGambar.classList.add('hidden');
    });
  </script>
</body>
</html> 