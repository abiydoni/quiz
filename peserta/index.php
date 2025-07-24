<?php
include_once '../api/db.php';

// Reset session jika ada ?reset=1
if (isset($_GET['reset'])) {
    session_start();
    session_unset();
    session_destroy();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    // Setelah reset, tampilkan form join seperti biasa
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    echo '<!-- SESSION_ID: ' . htmlspecialchars(session_id()) . ' -->';
    $kode = strtoupper(trim($_POST['kode_quiz'] ?? ''));
    $nama = trim($_POST['nama'] ?? '');

    // Cek kode valid?
    $stmt = $pdo->prepare("SELECT * FROM tb_quiz WHERE kode_quiz = ?");
    $stmt->execute([$kode]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $error = "Kode quiz tidak ditemukan.";
    } elseif (empty($nama)) {
        $error = "Nama tidak boleh kosong.";
    } else {
        // Cek nama sudah digunakan di quiz ini?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE id_quiz = ? AND nama = ?");
        $stmt->execute([$quiz['id'], $nama]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Nama sudah digunakan oleh peserta lain pada quiz ini. Silakan pilih nama lain.";
        } else {
        // Masukkan peserta ke tb_peserta
        $stmt = $pdo->prepare("INSERT INTO tb_peserta (id_quiz, nama) VALUES (?, ?)");
        $stmt->execute([$quiz['id'], $nama]);
        $id_peserta = $pdo->lastInsertId();

        // Simpan ke session
        session_start();
        $_SESSION['id_peserta'] = $id_peserta;
        $_SESSION['kode_quiz'] = $kode;

        // Redirect ke play.php
        header("Location: ../play.php");
        exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gabung Quiz</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-green-100 to-blue-200 min-h-screen flex items-center justify-center">
  <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-2xl fade-in">
    <h1 class="text-2xl font-bold text-center text-green-700 mb-6 flex items-center justify-center gap-2">
      <i class="fa-solid fa-users"></i> Gabung Quiz
    </h1>
    <form method="POST" class="space-y-6">
      <div>
        <label class="block font-semibold mb-2">Kode Quiz</label>
        <input type="text" name="kode_quiz" required maxlength="10" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-400 text-lg uppercase" placeholder="Contoh: ABC123" value="<?= htmlspecialchars($_POST['kode_quiz'] ?? '') ?>">
      </div>
      <div>
        <label class="block font-semibold mb-2">Nama Kamu</label>
        <input type="text" name="nama" required maxlength="100" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-400 text-lg" placeholder="Tulis namamu..." value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
      </div>
      <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-lg transition-all">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
      </button>
    </form>
  </div>
  <?php if ($error): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal Join',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#16a34a'
      });
    </script>
  <?php endif; ?>
</body>
</html> 