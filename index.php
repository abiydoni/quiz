<?php
session_start();
include_once 'api/db.php';

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['host']);
    header('Location: index.php');
    exit;
}

// Handle login host
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_host'])) {
    $host = trim($_POST['host'] ?? '');
    if ($host) {
        $_SESSION['host'] = $host;
        header('Location: index.php');
        exit;
    }
}

$host = $_SESSION['host'] ?? null;

// Handle hapus quiz
if (isset($_GET['hapus']) && $host) {
    $id = intval($_GET['hapus']);
    $stmt = $pdo->prepare("DELETE FROM tb_quiz WHERE id = ? AND host = ?");
    $stmt->execute([$id, $host]);
    header('Location: index.php');
    exit;
}

// Ambil daftar quiz milik host
$daftar_quiz = [];
if ($host) {
    $stmt = $pdo->prepare("SELECT * FROM tb_quiz WHERE host = ? ORDER BY waktu_buat DESC");
    $stmt->execute([$host]);
    $daftar_quiz = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QUIZ appsBee - Landing Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background: linear-gradient(135deg, #f8fafc 0%, #c7d2fe 100%); }
    .fade-in { animation: fadeIn 1s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px);} to { opacity: 1; transform: none; } }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center">
  <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-10 fade-in">
    <div class="text-center mb-8">
      <i class="fa-solid fa-graduation-cap text-5xl text-indigo-600 animate-bounce"></i>
      <h1 class="text-4xl font-extrabold text-indigo-700 mt-4 mb-2 tracking-tight">QUIZ appsBee</h1>
      <p class="text-gray-500 text-lg">Kuis interaktif seru, modern, dan penuh aksi!</p>
    </div>
    <?php if (!$host): ?>
      <form method="POST" class="max-w-md mx-auto mb-8 bg-indigo-50 p-6 rounded-xl shadow flex flex-col gap-4">
        <h2 class="text-xl font-bold text-indigo-700 mb-2 flex items-center gap-2"><i class="fa-solid fa-user"></i> Login Host</h2>
        <input type="text" name="host" required maxlength="100" class="p-3 border rounded-lg focus:ring-2 focus:ring-indigo-400 text-lg" placeholder="Nama atau Email Host">
        <button type="submit" name="login_host" class="py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-lg transition-all">
          <i class="fa-solid fa-right-to-bracket"></i> Login
        </button>
      </form>
    <?php else: ?>
      <div class="flex flex-wrap gap-4 items-center mb-6">
        <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg font-mono text-lg shadow">
          <i class="fa-solid fa-user"></i> <?= htmlspecialchars($host) ?>
        </span>
        <a href="?logout=1" class="ml-auto text-red-600 hover:underline"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
      </div>
      <div class="mb-8">
        <a href="buat_quiz.php" class="group flex items-center justify-center gap-3 py-4 px-6 bg-indigo-600 text-white text-xl font-semibold rounded-xl shadow-lg hover:bg-indigo-700 transition-all duration-200 transform hover:scale-105 focus:outline-none mb-4">
          <i class="fa-solid fa-plus-circle text-2xl group-hover:animate-spin"></i>
          Buat Quiz Baru
        </a>
        <a href="join.php" class="group flex items-center justify-center gap-3 py-4 px-6 bg-green-500 text-white text-xl font-semibold rounded-xl shadow-lg hover:bg-green-600 transition-all duration-200 transform hover:scale-105 focus:outline-none mb-4">
          <i class="fa-solid fa-users text-2xl group-hover:animate-pulse"></i>
          Gabung Quiz
        </a>
      </div>
      <h2 class="text-2xl font-bold text-indigo-700 mb-4 flex items-center gap-2"><i class="fa-solid fa-list"></i> Daftar Quiz Saya</h2>
      <?php if (count($daftar_quiz) === 0): ?>
        <div class="text-gray-500 text-center mb-6">Belum ada quiz yang Anda buat.</div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse mb-6">
            <thead>
              <tr class="bg-indigo-100">
                <th class="p-2">Nama Quiz</th>
                <th class="p-2">Kode</th>
                <th class="p-2">Tanggal</th>
                <th class="p-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($daftar_quiz as $q): ?>
                <tr class="border-b hover:bg-indigo-50">
                  <td class="p-2 font-semibold text-indigo-700"><?= htmlspecialchars($q['nama_quiz']) ?></td>
                  <td class="p-2 font-mono text-lg"><?= htmlspecialchars($q['kode_quiz']) ?></td>
                  <td class="p-2 text-gray-500"><?= date('d M Y H:i', strtotime($q['waktu_buat'])) ?></td>
                  <td class="p-2 flex gap-2">
                    <a href="host.php?kode=<?= htmlspecialchars($q['kode_quiz']) ?>" class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-bold shadow transition-all" title="Kontrol Quiz"><i class="fa-solid fa-chalkboard-user"></i></a>
                    <a href="leaderboard.php?kode=<?= htmlspecialchars($q['kode_quiz']) ?>" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold shadow transition-all" title="Leaderboard"><i class="fa-solid fa-trophy"></i></a>
                    <a href="#" onclick="return hapusQuiz(<?= $q['id'] ?>, '<?= addslashes($q['nama_quiz']) ?>')" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold shadow transition-all" title="Hapus Quiz"><i class="fa-solid fa-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="mt-10 text-center text-gray-400 text-sm">
      <span>Powered by <b>PHP</b>, <b>Tailwind</b>, <b>Font Awesome</b>, <b>SweetAlert2</b></span>
    </div>
  </div>
  <script>
    function hapusQuiz(id, nama) {
      Swal.fire({
        title: `Hapus quiz?`,
        text: `Quiz '${nama}' akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Ya, hapus!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location = 'index.php?hapus=' + id;
        }
      });
      return false;
    }
  </script>
</body>
</html> 