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
        // Hapus semua data jawaban dan peserta untuk quiz ini
        $stmt = $pdo->prepare("DELETE FROM tb_jawaban WHERE id_peserta IN (SELECT id FROM tb_peserta WHERE id_quiz = ?)");
        $stmt->execute([$quiz['id']]);
        $stmt = $pdo->prepare("DELETE FROM tb_peserta WHERE id_quiz = ?");
        $stmt->execute([$quiz['id']]);
        // Set status ke waiting (lobby)
        $stmt = $pdo->prepare("INSERT INTO tb_status_quiz (id_quiz, id_soal, waktu_mulai, mode) VALUES (?, NULL, NOW(), 'waiting')");
        $stmt->execute([$quiz['id']]);
        // HAPUS kode otomatis mulai soal pertama
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
        $found = false;
        foreach ($soals as $i => $s) {
            if ($s['id'] == $soal_aktif) {
                $found = true;
                $next = $soals[$i+1] ?? null;
                break;
            }
        }
        // Fallback: jika $soal_aktif tidak ditemukan, ambil soal pertama yang id-nya lebih besar
        if (!$found && $soal_aktif) {
            foreach ($soals as $s) {
                if ($s['id'] > $soal_aktif) {
                    $next = $s;
                    break;
                }
            }
        }
        // Debug log
        error_log('soal_aktif: ' . $soal_aktif);
        error_log('soals: ' . json_encode(array_column($soals, 'id')));
        error_log('next: ' . ($next ? $next['id'] : 'null'));
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
  <div class="max-w-full mx-auto">
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
      <form id="form-mulai-quiz" method="post" class="inline">
        <input type="hidden" name="kontrol_presentasi" value="1">
        <input type="hidden" name="aksi" value="mulai_quiz">
        <button type="submit" id="btn-mulai-quiz" class="inline-flex items-center gap-2 px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-semibold shadow transition-all">
          <i class="fa-solid fa-play"></i> Mulai Quiz (Lobby)
        </button>
      </form>
      <a href="preview.php?kode=<?= htmlspecialchars($kode) ?>" target="_blank" id="btn-tampil-layar" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow transition-all">
        <i class="fa-solid fa-desktop"></i> Tampilkan di Layar
      </a>
      <a href="index.php" class="text-orange-600 hover:underline ml-auto">&larr; Kembali ke Beranda</a>
    </div>
    <div class="mb-6 flex justify-between items-center">
      <h2 class="text-xl font-bold text-orange-700 flex items-center gap-2"><i class="fa-solid fa-list-ol"></i> Daftar Soal</h2>
      <a href="tambah_soal.php?id_quiz=<?= $quiz['id'] ?>&kode=<?= htmlspecialchars($kode) ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow transition-all flex items-center gap-2"><i class="fa-solid fa-plus"></i> Tambah Soal</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded-xl shadow text-sm">
        <thead>
          <tr class="bg-orange-100 text-orange-800">
            <th class="px-3 py-2 border">No Soal</th>
            <th class="px-3 py-2 border">Gambar</th>
            <th class="px-3 py-2 border">Pertanyaan</th>
            <th class="px-3 py-2 border">Pilihan Jawaban</th>
            <th class="px-3 py-2 border">Jawaban</th>
            <th class="px-3 py-2 border">Durasi</th>
            <th class="px-3 py-2 border">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($soals as $index => $soal): ?>
          <tr class="<?php if ($soal['id'] == $soal_aktif) echo 'bg-orange-50'; ?>">
            <td class="px-3 py-2 border text-center font-bold">Soal <?= $index + 1 ?></td>
            <td class="px-3 py-2 border text-center">
              <?php if (!empty($soal['gambar'])): ?>
                <img src="assets/soal/<?= htmlspecialchars($soal['gambar']) ?>" alt="Gambar Soal" class="max-h-20 max-w-[80px] rounded shadow mx-auto">
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td class="px-3 py-2 border"><?= htmlspecialchars($soal['soal']) ?></td>
            <td class="px-3 py-2 border">
              <ul class="list-none p-0 m-0">
                <li><b>A.</b> <?= htmlspecialchars($soal['jawaban_a']) ?></li>
                <li><b>B.</b> <?= htmlspecialchars($soal['jawaban_b']) ?></li>
                <li><b>C.</b> <?= htmlspecialchars($soal['jawaban_c']) ?></li>
                <li><b>D.</b> <?= htmlspecialchars($soal['jawaban_d']) ?></li>
              </ul>
            </td>
            <td class="px-3 py-2 border text-center font-bold text-green-700">
              <?php
                $benar = $soal['jawaban_benar'] ?? '';
                if ($benar) {
                  $teks = $soal['jawaban_' . strtolower($benar)] ?? '-';
                  echo "$benar. $teks";
                } else {
                  echo '-';
                }
              ?>
            </td>
            <td class="px-3 py-2 border text-center"><?= (int)$soal['durasi'] ?> detik</td>
            <td class="px-3 py-2 border text-center">
              <a href="edit_soal.php?id=<?= $soal['id'] ?>&kode=<?= htmlspecialchars($kode) ?>" class="px-2 py-1 bg-yellow-400 hover:bg-yellow-500 text-white rounded text-xs font-bold shadow transition-all" title="Edit Soal"><i class="fa-solid fa-pen-to-square"></i></a>
              <button onclick="return hapusSoal(<?= $soal['id'] ?>, '<?= addslashes($soal['soal']) ?>')" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold shadow transition-all" title="Hapus Soal"><i class="fa-solid fa-trash"></i></button>
              <?php if ((!$status || ($mode === 'waiting' && !$soal_aktif))): ?>
                <form method="post" class="inline">
                  <input type="hidden" name="kontrol_presentasi" value="1">
                  <input type="hidden" name="aksi" value="tampilkan_soal">
                  <input type="hidden" name="id_soal" value="<?= $soal['id'] ?>">
                  <input type="hidden" name="durasi" value="<?= (int)$soal['durasi'] ?>">
                  <button class="ml-1 px-2 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded text-xs font-bold shadow transition-all" onclick="return confirmTampilkanSoal(event, <?= $index + 1 ?>)"><i class="fa-solid fa-play"></i></button>
                </form>
              <?php endif; ?>
              <?php if ($soal['id'] == $soal_aktif): ?>
                <span class="ml-1 px-2 py-1 bg-orange-500 text-white rounded text-xs font-bold animate-pulse"><i class="fa-solid fa-bolt"></i> Soal Aktif</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var btnTampilLayar = document.getElementById('btn-tampil-layar');
      if (btnTampilLayar) {
        btnTampilLayar.addEventListener('click', function(e) {
          e.preventDefault();
          var url = btnTampilLayar.getAttribute('href');
          var win = window.open(url, '_blank');
          if (win) {
            win.focus();
            win.onload = function() {
              if (win.document.documentElement.requestFullscreen) {
                win.document.documentElement.requestFullscreen();
              }
            };
          }
        });
      }
    });
  </script>
</body>
</html> 