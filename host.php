<?php
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once 'api/db.php';
$kode = $_GET['kode'] ?? '';
if (!$kode) die('Kode quiz tidak ditemukan.');

// Ambil quiz dan soalnya
$stmt = $pdo->prepare("SELECT id, nama_quiz FROM tb_quiz WHERE kode_quiz = ?");
$stmt->execute([$kode]);
$quiz = $stmt->fetch();
if (!$quiz) die('Quiz tidak ditemukan.');

$stmt = $pdo->prepare("SELECT * FROM tb_soal WHERE id_quiz = ?");
$stmt->execute([$quiz['id']]);
$soals = $stmt->fetchAll();

// Cek status presentasi
$stmt = $pdo->prepare("SELECT * FROM tb_status_quiz WHERE id_quiz = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$quiz['id']]);
$status = $stmt->fetch();
$soal_aktif = $status['id_soal'] ?? null;
$mode = $status['mode'] ?? null;

// Handle kontrol presentasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kontrol_presentasi'])) {
    $aksi = $_POST['aksi'] ?? '';
    $id_soal = $_POST['id_soal'] ?? null;
    $durasi = $_POST['durasi'] ?? 20;
    if ($aksi === 'mulai_quiz') {
        // Hapus status quiz lama
        $stmt = $pdo->prepare("DELETE FROM tb_status_quiz WHERE id_quiz = ?");
        $stmt->execute([$quiz['id']]);
        // Set status ke waiting (lobby)
        $stmt = $pdo->prepare("INSERT INTO tb_status_quiz (id_quiz, id_soal, waktu_mulai, mode) VALUES (?, NULL, NOW(), 'waiting')");
        $stmt->execute([$quiz['id']]);
        file_put_contents('debug.txt', 'POST: '.json_encode($_POST).PHP_EOL, FILE_APPEND);
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'tampilkan_soal' && $id_soal) {
        $waktu_mulai = date('c'); // ISO 8601
        $stmt = $pdo->prepare("INSERT INTO tb_status_quiz (id_quiz, id_soal, waktu_mulai, mode) VALUES (?, ?, ?, 'soal')");
        $stmt->execute([$quiz['id'], $id_soal, $waktu_mulai]);
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'ke_jawaban' && $status) {
        $stmt = $pdo->prepare("UPDATE tb_status_quiz SET mode = 'jawaban' WHERE id = ?");
        $stmt->execute([$status['id']]);
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'ke_grafik' && $status) {
        $stmt = $pdo->prepare("UPDATE tb_status_quiz SET mode = 'grafik' WHERE id = ?");
        $stmt->execute([$status['id']]);
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'ke_ranking' && $status) {
        $stmt = $pdo->prepare("UPDATE tb_status_quiz SET mode = 'ranking' WHERE id = ?");
        $stmt->execute([$status['id']]);
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'soal_berikutnya') {
        // Cari soal berikutnya
        $next = false;
        foreach ($soals as $i => $s) {
            if ($s['id'] == $soal_aktif) {
                $next = $soals[$i+1] ?? null;
                break;
            }
        }
        if ($next) {
            $waktu_mulai = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO tb_status_quiz (id_quiz, id_soal, waktu_mulai, mode) VALUES (?, ?, ?, 'soal')");
            $stmt->execute([$quiz['id'], $next['id'], $waktu_mulai]);
        }
        header("Location: host.php?kode=$kode");
        exit;
    } elseif ($aksi === 'podium' && $status) {
        $stmt = $pdo->prepare("UPDATE tb_status_quiz SET mode = 'podium' WHERE id = ?");
        $stmt->execute([$status['id']]);
        header("Location: host.php?kode=$kode");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kontrol Quiz - <?= htmlspecialchars($quiz['nama_quiz']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-orange-50 min-h-screen p-6">
  <div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold text-orange-700 mb-6 flex items-center gap-3">
      <i class="fa-solid fa-chalkboard-user"></i> Kontrol Quiz: <?= htmlspecialchars($quiz['nama_quiz']) ?>
    </h1>
    <div class="mb-8 flex flex-wrap gap-4 items-center">
      <span class="bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-mono text-lg shadow">
        Kode Quiz: <b><?= htmlspecialchars($kode) ?></b>
      </span>
      <a href="leaderboard.php?kode=<?= htmlspecialchars($kode) ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold shadow transition-all">
        <i class="fa-solid fa-trophy"></i> Leaderboard
      </a>
      <a href="preview.php?kode=<?= htmlspecialchars($kode) ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow transition-all">
        <i class="fa-solid fa-desktop"></i> Tampilkan di Layar
      </a>
      <a href="index.php" class="text-orange-600 hover:underline ml-auto">&larr; Kembali ke Beranda</a>
    </div>
    <form method="post" class="mb-4 flex justify-center">
      <input type="hidden" name="kontrol_presentasi" value="1">
      <input type="hidden" name="aksi" value="mulai_quiz">
      <button class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-lg font-bold shadow transition-all">
        <i class="fa-solid fa-play"></i> Mulai Quiz (Lobby)
      </button>
    </form>
    <div class="mb-6 flex justify-between items-center">
      <h2 class="text-xl font-bold text-orange-700 flex items-center gap-2"><i class="fa-solid fa-list-ol"></i> Daftar Soal</h2>
      <a href="tambah_soal.php?id_quiz=<?= $quiz['id'] ?>&kode=<?= htmlspecialchars($kode) ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow transition-all flex items-center gap-2"><i class="fa-solid fa-plus"></i> Tambah Soal</a>
    </div>
    <div class="grid gap-6">
      <?php foreach ($soals as $index => $soal): ?>
        <div class="p-6 border rounded-xl bg-white shadow flex flex-col gap-2 <?php if ($soal['id'] == $soal_aktif) echo 'border-4 border-orange-500'; else echo 'border-gray-200'; ?>">
          <div class="flex items-center gap-3 mb-2">
            <span class="text-lg font-bold text-orange-700">Soal <?= $index + 1 ?>:</span>
            <?php if ($soal['id'] == $soal_aktif): ?>
              <span class="px-3 py-1 bg-orange-500 text-white rounded-full text-xs font-bold animate-pulse"><i class="fa-solid fa-bolt"></i> Soal Aktif</span>
            <?php endif; ?>
            <a href="edit_soal.php?id=<?= $soal['id'] ?>&kode=<?= htmlspecialchars($kode) ?>" class="ml-auto px-3 py-1 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-sm font-bold shadow transition-all" title="Edit Soal"><i class="fa-solid fa-pen-to-square"></i></a>
            <button onclick="return hapusSoal(<?= $soal['id'] ?>, '<?= addslashes($soal['soal']) ?>')" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold shadow transition-all" title="Hapus Soal"><i class="fa-solid fa-trash"></i></button>
          </div>
          <?php if (!empty($soal['gambar'])): ?>
            <img src="assets/soal/<?= htmlspecialchars($soal['gambar']) ?>" alt="Gambar Soal" class="mb-3 max-h-56 rounded-lg mx-auto shadow">
          <?php endif; ?>
          <p class="mb-2 text-lg text-gray-800"><?= htmlspecialchars($soal['soal']) ?></p>
          <ul class="list-disc ml-6 text-base mb-2 text-gray-700">
            <li><b>A.</b> <?= htmlspecialchars($soal['jawaban_a']) ?></li>
            <li><b>B.</b> <?= htmlspecialchars($soal['jawaban_b']) ?></li>
            <li><b>C.</b> <?= htmlspecialchars($soal['jawaban_c']) ?></li>
            <li><b>D.</b> <?= htmlspecialchars($soal['jawaban_d']) ?></li>
          </ul>
          <div class="flex gap-4 items-center mt-2">
            <span class="text-sm text-gray-500"><i class="fa-solid fa-clock"></i> Durasi: <?= (int)$soal['durasi'] ?> detik</span>
            <?php if ((!$status || ($mode === 'waiting' && !$soal_aktif))): ?>
              <form method="post" class="inline">
                <input type="hidden" name="kontrol_presentasi" value="1">
                <input type="hidden" name="aksi" value="tampilkan_soal">
                <input type="hidden" name="id_soal" value="<?= $soal['id'] ?>">
                <input type="hidden" name="durasi" value="<?= (int)$soal['durasi'] ?>">
                <button class="ml-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold shadow transition-all" onclick="return confirmTampilkanSoal(event, <?= $index + 1 ?>)">
                  <i class="fa-solid fa-play"></i> Mulai Soal Ini
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($status && $mode !== 'waiting'): ?>
      <div class="mt-8 flex flex-wrap gap-4 items-center justify-center">
        <?php if ($mode === 'soal'): ?>
          <form method="post"><input type="hidden" name="kontrol_presentasi" value="1"><input type="hidden" name="aksi" value="ke_jawaban"><button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-lg font-bold shadow transition-all"><i class="fa-solid fa-circle-check"></i> Tampilkan Jawaban</button></form>
        <?php elseif ($mode === 'jawaban'): ?>
          <form method="post"><input type="hidden" name="kontrol_presentasi" value="1"><input type="hidden" name="aksi" value="ke_grafik"><button class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-lg font-bold shadow transition-all"><i class="fa-solid fa-chart-column"></i> Tampilkan Grafik</button></form>
        <?php elseif ($mode === 'grafik'): ?>
          <form method="post"><input type="hidden" name="kontrol_presentasi" value="1"><input type="hidden" name="aksi" value="ke_ranking"><button class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-lg font-bold shadow transition-all"><i class="fa-solid fa-trophy"></i> Tampilkan Ranking</button></form>
        <?php elseif ($mode === 'ranking'): ?>
          <form method="post"><input type="hidden" name="kontrol_presentasi" value="1"><input type="hidden" name="aksi" value="soal_berikutnya"><button class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-lg font-bold shadow transition-all"><i class="fa-solid fa-forward"></i> Mulai Soal Berikutnya</button></form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <script>
    function confirmTampilkanSoal(e, nomor) {
      e.preventDefault();
      Swal.fire({
        title: `Mulai Soal #${nomor}?`,
        text: 'Peserta akan langsung melihat soal ini dan timer dimulai!',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Ya, mulai!'
      }).then((result) => {
        if (result.isConfirmed) {
          e.target.closest('form').submit();
        }
      });
      return false;
    }

    function hapusSoal(id, soal) {
      Swal.fire({
        title: `Hapus soal?`,
        text: soal.length > 40 ? soal.substring(0,40)+'...' : soal,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Ya, hapus!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location = 'hapus_soal.php?id=' + id + '&kode=<?= $kode ?>';
        }
      });
      return false;
    }
  </script>
</body>
</html> 