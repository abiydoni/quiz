<?php
include_once 'api/db.php';
session_start();
$host = $_SESSION['host'] ?? null;

$step = 1;
$kode_quiz = '';
$id_quiz = null;
$nama_quiz = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Buat quiz
    if (isset($_POST['buat_quiz'])) {
        $nama_quiz = trim($_POST['nama_quiz'] ?? '');
        if ($nama_quiz && $host) {
            $kode_quiz = strtoupper(substr(md5(uniqid()), 0, 6));
            $stmt = $pdo->prepare("INSERT INTO tb_quiz (kode_quiz, nama_quiz, host) VALUES (?, ?, ?)");
            $stmt->execute([$kode_quiz, $nama_quiz, $host]);
            $id_quiz = $pdo->lastInsertId();
            $step = 2;
        }
    }
    // Step 2: Tambah soal
    elseif (isset($_POST['tambah_soal'])) {
        $id_quiz = $_POST['id_quiz'];
        $kode_quiz = $_POST['kode_quiz'];
        $soal = trim($_POST['soal'] ?? '');
        $a = trim($_POST['jawaban_a'] ?? '');
        $b = trim($_POST['jawaban_b'] ?? '');
        $c = trim($_POST['jawaban_c'] ?? '');
        $d = trim($_POST['jawaban_d'] ?? '');
        $benar = $_POST['jawaban_benar'] ?? 'A';
        $durasi = intval($_POST['durasi'] ?? 20);
        $gambar_nama = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $gambar_nama = uniqid('soal_') . '.' . $ext;
                $tujuan = __DIR__ . '/assets/soal/' . $gambar_nama;
                if (!is_dir(__DIR__ . '/assets/soal/')) mkdir(__DIR__ . '/assets/soal/', 0777, true);
                move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan);
            }
        }
        if ($soal && $a && $b && $c && $d && in_array($benar, ['A','B','C','D']) && $durasi > 0) {
            $stmt = $pdo->prepare("INSERT INTO tb_soal (id_quiz, soal, jawaban_a, jawaban_b, jawaban_c, jawaban_d, jawaban_benar, durasi, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_quiz, $soal, $a, $b, $c, $d, $benar, $durasi, $gambar_nama]);
            $step = 2;
        }
        if (isset($_POST['selesai'])) {
            $step = 3;
        }
    }
}
if ($step === 2 && !$id_quiz) {
    $id_quiz = $_POST['id_quiz'] ?? null;
    $kode_quiz = $_POST['kode_quiz'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Quiz Baru</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-100 to-blue-200">
  <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8 fade-in">
    <?php if ($step === 1): ?>
      <h2 class="text-2xl font-bold text-indigo-700 mb-6 text-center flex items-center justify-center gap-2">
        <i class="fa-solid fa-plus-circle"></i> Buat Quiz Baru
      </h2>
      <form method="POST" class="space-y-6">
        <div>
          <label class="block font-semibold mb-2">Nama Quiz</label>
          <input type="text" name="nama_quiz" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-400" placeholder="Contoh: Kuis Matematika">
        </div>
        <button type="submit" name="buat_quiz" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-lg transition-all">
          <i class="fa-solid fa-arrow-right"></i> Lanjut Tambah Soal
        </button>
      </form>
    <?php elseif ($step === 2): ?>
      <h2 class="text-2xl font-bold text-indigo-700 mb-4 text-center flex items-center justify-center gap-2">
        <i class="fa-solid fa-list-ol"></i> Tambah Soal
      </h2>
      <form method="POST" enctype="multipart/form-data" class="space-y-4" id="formSoal">
        <input type="hidden" name="id_quiz" value="<?= htmlspecialchars($id_quiz) ?>">
        <input type="hidden" name="kode_quiz" value="<?= htmlspecialchars($kode_quiz) ?>">
        <div id="dropzone" class="dropzone flex flex-col items-center justify-center p-6 mb-2">
          <input type="file" name="gambar" id="gambarInput" accept="image/*" class="hidden">
          <img id="imgPreview" class="img-preview hidden" alt="Preview Gambar">
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
          <button type="submit" name="tambah_soal" class="flex-1 py-3 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg text-lg transition-all">
            <i class="fa-solid fa-plus"></i> Tambah Soal Lagi
          </button>
          <button type="submit" name="tambah_soal" value="1" name="selesai" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-lg transition-all">
            <i class="fa-solid fa-check"></i> Selesai
          </button>
          <a href="host.php?kode=<?= htmlspecialchars($kode_quiz) ?>" class="flex-1 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded-lg text-lg text-center transition-all">
            <i class="fa-solid fa-xmark"></i> Batal
          </a>
        </div>
      </form>
      <script>
        // SweetAlert notifikasi sukses tambah soal
        if (window.location.search.includes('tambah_soal')) {
          Swal.fire({
            icon: 'success',
            title: 'Soal berhasil ditambahkan!',
            showConfirmButton: false,
            timer: 1200
          });
        }
        // Drag & drop gambar modern
        const dropzone = document.getElementById('dropzone');
        const gambarInput = document.getElementById('gambarInput');
        const imgPreview = document.getElementById('imgPreview');
        const dropText = document.getElementById('dropText');
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
            };
            reader.readAsDataURL(gambarInput.files[0]);
          } else {
            imgPreview.classList.add('hidden');
            dropText.classList.remove('hidden');
          }
        }
      </script>
    <?php elseif ($step === 3): ?>
      <div class="text-center">
        <i class="fa-solid fa-circle-check text-5xl text-green-500 mb-4 animate-bounce"></i>
        <h2 class="text-2xl font-bold text-green-700 mb-2">Quiz Berhasil Dibuat!</h2>
        <div class="mb-4">
          <span class="font-semibold">Kode Quiz:</span>
          <span class="text-2xl font-mono bg-gray-100 px-4 py-2 rounded-lg border border-indigo-200 ml-2">
            <?= htmlspecialchars($kode_quiz) ?>
          </span>
        </div>
        <a href="host.php?kode=<?= htmlspecialchars($kode_quiz) ?>" class="inline-block mt-4 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-lg font-bold shadow transition-all">
          <i class="fa-solid fa-chalkboard-user"></i> Mulai Kontrol Quiz
        </a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html> 