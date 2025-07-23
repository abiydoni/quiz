<?php
session_start();
include_once 'api/db.php';

if (!isset($_SESSION['id_peserta'], $_SESSION['kode_quiz'])) {
  header("Location: join.php");
  exit;
}

$id_peserta = $_SESSION['id_peserta'];
$kode_quiz = $_SESSION['kode_quiz'];

// Ambil nama peserta
$stmt = $pdo->prepare("SELECT nama FROM tb_peserta WHERE id = ?");
$stmt->execute([$id_peserta]);
$peserta = $stmt->fetch();
$nama_peserta = $peserta ? $peserta['nama'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Sedang Berlangsung</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .timer-anim { animation: pulse 1s infinite; }
    @keyframes pulse { 0% { color: #f59e42; } 50% { color: #ef4444; } 100% { color: #f59e42; } }
  </style>
</head>
<body class="bg-gradient-to-br from-purple-100 to-blue-200 min-h-screen flex items-center justify-center">
  <div class="w-full max-w-xl bg-white/80 p-4 sm:p-8 rounded-3xl shadow-2xl fade-in text-center backdrop-blur-md animate-fadein-card">
    <h2 class="text-lg sm:text-xl font-semibold mb-2 animate-fadein-top">Selamat datang, <span class="text-indigo-700 font-bold"><?= htmlspecialchars($nama_peserta) ?></span></h2>
    <div id="soal-info" class="text-xs sm:text-sm font-semibold text-gray-500 mb-2 animate-fadein-top"></div>
    <h1 id="soal" class="text-xl sm:text-2xl font-bold text-purple-700 mb-6 animate-fadein-top">Menunggu soal...</h1>
    <div class="text-lg font-semibold text-red-600 mb-4 animate-pulse-timer">
      <i class="fa-solid fa-hourglass-half animate-spin"></i>
      <span id="timer" class="timer-anim">--</span> detik
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="opsi-jawaban">
      <?php
        $warna = [
          'A' => 'from-red-500 to-pink-400',
          'B' => 'from-blue-500 to-cyan-400',
          'C' => 'from-yellow-400 to-orange-300 text-gray-900',
          'D' => 'from-green-500 to-lime-400',
        ];
        $ikon = [
          'A' => '<i class="fa-solid fa-triangle-exclamation mr-2"></i>',
          'B' => '<i class="fa-solid fa-diamond mr-2"></i>',
          'C' => '<i class="fa-solid fa-circle mr-2"></i>',
          'D' => '<i class="fa-solid fa-square mr-2"></i>',
        ];
        foreach (["A", "B", "C", "D"] as $h):
      ?>
        <button id="btn_<?= $h ?>" onclick="kirimJawaban('<?= $h ?>')"
          class="jawaban-btn bg-gradient-to-br <?= $warna[$h] ?> text-white py-4 rounded-2xl text-lg font-bold shadow-lg transition-all duration-300 transform hover:scale-105 focus:scale-95 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed animate-fadein-opsi"
          style="min-height:3.2rem;">
          <?= $ikon[$h] ?><span id="opsi_<?= $h ?>">Jawaban <?= $h ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
  <style>
    .animate-fadein-card { animation: fadeInCard 0.7s cubic-bezier(.4,2,.6,1); }
    .animate-fadein-top { animation: fadeInTop 0.8s cubic-bezier(.4,2,.6,1); }
    .animate-fadein-opsi { animation: fadeInOpsi 1s cubic-bezier(.4,2,.6,1); }
    @keyframes fadeInCard { from { opacity: 0; transform: scale(0.95) translateY(40px);} to { opacity: 1; transform: none; } }
    @keyframes fadeInTop { from { opacity: 0; transform: translateY(-30px);} to { opacity: 1; transform: none; } }
    @keyframes fadeInOpsi { from { opacity: 0; transform: scale(0.7);} to { opacity: 1; transform: none; } }
    .animate-pulse-timer { animation: pulseTimer 1.2s infinite; }
    @keyframes pulseTimer { 0% { color: #f59e42; } 50% { color: #ef4444; } 100% { color: #f59e42; } }
    .jawaban-btn:active { box-shadow: 0 0 0 4px #a5b4fc55; }
    @media (max-width: 640px) {
      .fade-in, .animate-fadein-card { border-radius: 1.2rem !important; padding: 1.2rem !important; }
    }
  </style>
  <script>
    let soalAktif = 0;
    let waktuHabis = false;
    let timerInterval;
    let durasiSoal = 20;
    let lastSoalId = 0;
    let timerStartedForSoal = 0;
    let lastMode = '';
    let jawabanTerakhir = null;

    async function ambilSoal() {
      // Ambil status presentasi (seperti preview)
      const res = await fetch(`api/quiz.php?action=status_presentasi&kode=<?= $kode_quiz ?>`);
      const text = await res.text();
      if (!text || text.trim() === '{}' || text.trim() === '[]') {
        document.getElementById('soal').innerText = 'Menunggu quiz dimulai...';
        document.getElementById('opsi-jawaban').style.display = 'none';
        setTimeout(ambilSoal, 2000);
        return;
      }
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        document.getElementById('soal').innerText = 'Terjadi error pada server.';
        document.getElementById('opsi-jawaban').style.display = 'none';
        setTimeout(ambilSoal, 2000);
        return;
      }
      let soalBaru = false;
      if (data && data.id_soal && data.id_soal != lastSoalId) {
        soalAktif = data.id_soal;
        lastSoalId = data.id_soal;
        waktuHabis = false;
        durasiSoal = data.durasi || 20;
        timerStartedForSoal = data.id_soal;
        tampilkanSoal(data, true); // reset timer
      } else if (data && data.id_soal) {
        tampilkanSoal(data, false); // update tampilan, jangan reset timer
      }
      // Cek mode presentasi
      if (data && data.mode === 'waiting') {
        document.getElementById('soal').innerText = 'Menunggu soal...';
        document.getElementById('soal-info').innerText = '';
        document.getElementById('timer').innerText = '--';
        ['A', 'B', 'C', 'D'].forEach(h => {
          document.getElementById('opsi_' + h).innerText = '';
          document.getElementById('btn_' + h).disabled = true;
        });
        document.getElementById('opsi-jawaban').style.display = 'none';
        setTimeout(ambilSoal, 2000);
        lastMode = data.mode;
        return;
      }
      if (data && data.mode === 'jawaban' && lastMode !== 'jawaban') {
        tampilkanSladeJawaban(data.jawaban_benar);
      }
      lastMode = data && data.mode ? data.mode : '';
      document.getElementById('opsi-jawaban').style.display = '';
      setTimeout(ambilSoal, 2000); // polling setiap 2 detik
    }

    function tampilkanSoal(data, resetTimer = false) {
      // Tampilkan info jumlah soal
      const soalInfo = document.getElementById('soal-info');
      if (soalInfo && data.current_index && data.total_soal) {
        soalInfo.innerText = `Soal ${data.current_index} dari ${data.total_soal}`;
      } else if (soalInfo) {
        soalInfo.innerText = '';
      }
      const soalDiv = document.getElementById('soal');
      soalDiv.innerHTML = `${data.gambar ? `<img src='assets/soal/${data.gambar}' alt='Gambar Soal' class='mb-4 max-h-64 rounded-lg mx-auto shadow'>` : ''}<div>${data.soal}</div>`;
      ['A', 'B', 'C', 'D'].forEach(h => {
        document.getElementById('opsi_' + h).innerText = data['jawaban_' + h.toLowerCase()];
        document.getElementById('btn_' + h).disabled = false;
      });
      // Cek timer di elemen, jika > 0, set waktuHabis = false
      const elemenTimer = document.getElementById('timer');
      if (elemenTimer && parseInt(elemenTimer.innerText) > 0) {
        waktuHabis = false;
      }
      const mulai = new Date(data.waktu_mulai).getTime();
      const now = Date.now();
      let sisa = data.durasi - Math.floor((now - mulai) / 1000);
      if (sisa < 0) sisa = 0;
      document.getElementById('timer').innerText = sisa; // update timer di elemen setiap polling
      if (resetTimer) {
        clearInterval(timerInterval);
        waktuHabis = false;
        if (sisa <= 0) {
          countdownTimer(now, data.durasi);
        } else {
          countdownTimer(mulai, data.durasi);
        }
      }
    }

    function countdownTimer(waktuMulai, durasi) {
      timerInterval = setInterval(() => {
        const now = Date.now();
        const sisa = durasi - Math.floor((now - waktuMulai) / 1000);
        const elemenTimer = document.getElementById('timer');
        elemenTimer.innerText = sisa > 0 ? sisa : 0;
        if (sisa <= 0) {
          clearInterval(timerInterval);
          waktuHabis = true;
          disableSemuaTombol();
        }
      }, 500);
    }

    function disableSemuaTombol() {
      ['A', 'B', 'C', 'D'].forEach(h => document.getElementById('btn_' + h).disabled = true);
    }
    function enableSemuaTombol() {
      ['A', 'B', 'C', 'D'].forEach(h => document.getElementById('btn_' + h).disabled = false);
    }

    async function kirimJawaban(pilihan) {
      const elemenTimer = document.getElementById('timer');
      if (!elemenTimer || parseInt(elemenTimer.innerText) <= 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Waktu habis!',
          text: 'Jawaban tidak dikirim.',
          confirmButtonColor: '#a21caf'
        });
        return;
      }
      // Jangan disable tombol, biarkan peserta bisa submit ulang selama timer berjalan
      const res = await fetch(`api/quiz.php?action=jawab`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id_peserta=<?= $id_peserta ?>&id_soal=${soalAktif}&jawaban=${pilihan}`
      });
      const data = await res.json();
      if (data.status === 'ok') {
        Swal.fire({
          icon: 'info',
          title: 'Jawaban Terkirim',
          text: `Jawaban yang kamu pilih: ${pilihan}`,
          timer: 2000,
          showConfirmButton: false
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Gagal mengirim jawaban',
          confirmButtonColor: '#ef4444'
        });
      }
    }

    async function tampilkanSladeJawaban(jawabanBenar) {
      // Ambil jawaban terakhir peserta untuk soal ini
      const res = await fetch(`api/jawaban_peserta.php?id_peserta=<?= $id_peserta ?>&id_soal=${soalAktif}`);
      const data = await res.json();
      let html = '<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">';
      html += '<div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center animate-fadein-card">';
      if (data.benar == 1) {
        html += '<div class="text-3xl font-bold text-green-600 mb-2">Selamat! Jawaban Anda BENAR</div>';
        html += `<div class="text-lg mb-4">Jawaban yang kamu pilih: <b>${data.jawaban}</b></div>`;
      } else {
        html += '<div class="text-3xl font-bold text-red-600 mb-2">Jawaban Anda SALAH</div>';
        html += `<div class="text-lg mb-2">Jawaban yang kamu pilih: <b>${data.jawaban}</b></div>`;
        html += `<div class="text-lg mb-4">Jawaban yang benar: <b>${jawabanBenar}</b></div>`;
      }
      html += '<button onclick="document.getElementById(\'slade-jawaban\').remove()" class="mt-4 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow">Tutup</button>';
      html += '</div></div>';
      let el = document.createElement('div');
      el.id = 'slade-jawaban';
      el.innerHTML = html;
      document.body.appendChild(el);
    }
    window.onload = ambilSoal;
  </script>
</body>
</html> 