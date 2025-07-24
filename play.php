<?php
session_start();
include_once 'api/db.php';

if (!isset($_SESSION['id_peserta'], $_SESSION['kode_quiz'])) {
  header("Location: peserta");
  exit;
}

$id_peserta = $_SESSION['id_peserta'];
$kode_quiz = $_SESSION['kode_quiz'];

// Ambil nama peserta
$stmt = $pdo->prepare("SELECT nama, id_quiz FROM tb_peserta WHERE id = ?");
$stmt->execute([$id_peserta]);
$peserta = $stmt->fetch();
$nama_peserta = $peserta ? $peserta['nama'] : '';
$id_quiz_peserta = $peserta ? $peserta['id_quiz'] : null;

// DEBUG: tampilkan session dan hasil query peserta
if (isset($_SESSION['id_peserta'], $_SESSION['kode_quiz'])) {
  echo '<!-- SESSION id_peserta: ' . htmlspecialchars($_SESSION['id_peserta']) . ' -->';
  echo '<!-- SESSION kode_quiz: ' . htmlspecialchars($_SESSION['kode_quiz']) . ' -->';
}
echo '<!-- Peserta DB: ' . htmlspecialchars(json_encode($peserta)) . ' -->';

// Cek jika peserta sudah dihapus tapi kode quiz masih sama
if (!$peserta) {
  // Cek apakah kode quiz masih ada
  $stmt = $pdo->prepare("SELECT id FROM tb_quiz WHERE kode_quiz = ?");
  $stmt->execute([$kode_quiz]);
  $id_quiz = $stmt->fetchColumn();
  if ($id_quiz) {
    // Quiz masih ada, berarti peserta sudah dihapus (reset quiz)
    // Slade Sesi Habis (khusus peserta lama yang belum join ulang)
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sesi Habis</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-black min-h-screen flex items-center justify-center"><div class="flex flex-col items-center justify-center min-h-screen w-full"><div class="mb-6 text-3xl sm:text-4xl font-bold text-pink-500 drop-shadow">Sesi Habis</div><div class="bg-black/90 backdrop-blur-lg rounded-3xl shadow-2xl p-10 flex flex-col items-center border-4 border-pink-300 max-w-md w-full"><div class="mb-8"><i class="fa-solid fa-hourglass-end text-7xl text-pink-400 drop-shadow"></i></div><h1 class="text-2xl sm:text-3xl font-extrabold text-pink-700 mb-3 text-center tracking-wide drop-shadow-lg">Sesi quiz sebelumnya telah habis.<br>Silakan join ulang untuk mengikuti quiz berikutnya!</h1><div class="text-lg text-gray-700 mb-6 text-center max-w-md">Klik tombol di bawah untuk join ulang ke quiz.</div><a href="peserta" class="mt-4 px-6 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-full text-lg font-bold shadow transition-all flex items-center gap-2"><i class="fa-solid fa-arrow-left"></i>Join Quiz</a></div></div></body></html>';
    exit;
  } else {
    // Quiz sudah tidak ada, redirect ke peserta
    header("Location: peserta");
    exit;
  }
}
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
    .animate-fadein-card { animation: fadeInCard 0.7s cubic-bezier(.4,2,.6,1); }
    .animate-fadein-top { animation: fadeInTop 0.8s cubic-bezier(.4,2,.6,1); }
    .animate-fadein-opsi { animation: fadeInOpsi 1s cubic-bezier(.4,2,.6,1); }
    @keyframes fadeInCard { from { opacity: 0; transform: scale(0.95) translateY(40px);} to { opacity: 1; transform: none; } }
    @keyframes fadeInTop { from { opacity: 0; transform: translateY(-30px);} to { opacity: 1; transform: none; } }
    @keyframes fadeInOpsi { from { opacity: 0; transform: scale(0.7);} to { opacity: 1; transform: none; } }
    .jawaban-btn:active { box-shadow: 0 0 0 4px #a5b4fc55; }
    @media (max-width: 640px) {
      .fade-in, .animate-fadein-card { border-radius: 1.2rem !important; padding: 1.2rem !important; }
    }
    /* Animasi custom jam pasir */
    @keyframes hourglass-spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }
    .hourglass-anim {
      animation: hourglass-spin 2.5s cubic-bezier(.4,0,.2,1) infinite;
      will-change: transform;
      display: inline-block;
    }
  </style>
</head>
<body class="bg-black min-h-screen flex items-center justify-center">
  <!-- Card menunggu soal -->
  <div id="waiting-container" class="flex flex-col items-center justify-center min-h-screen w-full">
    <div class="mb-6 text-2xl sm:text-3xl font-bold text-white drop-shadow">Selamat datang, <span class="text-indigo-400"><?= htmlspecialchars($nama_peserta) ?></span>!</div>
    <div class="bg-black/80 backdrop-blur-lg rounded-3xl shadow-2xl p-10 flex flex-col items-center border border-gray-800 max-w-md w-full">
      <div class="mb-8">
        <i class="fa-solid fa-hourglass-half text-7xl text-indigo-400 drop-shadow"></i>
      </div>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-3">Menunggu Soal...</h1>
      <div class="text-lg text-gray-200 mb-6 text-center max-w-md">Quiz akan segera dimulai.<br>Perhatikan layar proyektor dan tunggu host menampilkan soal.</div>
      <div class="flex gap-2 mt-2">
        <span class="w-3 h-3 bg-indigo-400 rounded-full"></span>
        <span class="w-3 h-3 bg-indigo-500 rounded-full"></span>
        <span class="w-3 h-3 bg-indigo-600 rounded-full"></span>
      </div>
    </div>
  </div>
  <!-- Card utama quiz -->
  <div id="main-card" style="display:none; background:rgba(0,0,0,0.92);" class="w-full max-w-xl p-4 sm:p-8 rounded-3xl shadow-2xl fade-in text-center backdrop-blur-md animate-fadein-card border border-gray-800">
    <div id="soal-info" class="text-lg sm:text-xl font-bold text-indigo-300 mb-4 animate-fadein-top"></div>
    <h1 id="soal" class="text-xl sm:text-2xl font-bold text-white mb-6 animate-fadein-top"></h1>
    <div class="text-lg font-semibold text-orange-400 mb-4">
      <i class="fa-solid fa-hourglass-half"></i>
      <span id="timer">--</span> detik
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="opsi-jawaban" style="display:none;">
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
    .jawaban-btn:active { box-shadow: 0 0 0 4px #a5b4fc55; }
    @media (max-width: 640px) {
      .fade-in, .animate-fadein-card { border-radius: 1.2rem !important; padding: 1.2rem !important; }
    }
    /* Animasi custom jam pasir */
    @keyframes hourglass-spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }
    .hourglass-anim { animation: hourglass-spin 2.5s cubic-bezier(.4,0,.2,1) infinite; }
  </style>
  <script>
    let soalAktif = 0;
    let waktuHabis = false;
    let timerInterval;
    let durasiSoal = 20;
    let lastSoalId = null; // deklarasi global di luar fungsi
    let timerStartedForSoal = 0;
    let lastMode = ''; // deklarasi global di luar fungsi
    let jawabanTerakhir = null;

    async function ambilSoal() {
      // Cek apakah peserta masih ada
      try {
        const cekPeserta = await fetch(`api/quiz.php?action=cek_peserta&id_peserta=<?= $id_peserta ?>`);
        const pesertaStatus = await cekPeserta.json();
        if (!pesertaStatus || pesertaStatus.status !== 'ok') {
          window.location.href = 'peserta';
          return;
        }
      } catch (e) {
        window.location.href = 'peserta';
        return;
      }
      // Ambil status presentasi (seperti preview)
      const res = await fetch(`api/quiz.php?action=status_presentasi&kode=<?= $kode_quiz ?>`);
      const text = await res.text();
      if (!text || text.trim() === '{}' || text.trim() === '[]') {
        window.location.href = 'peserta';
        return;
      }
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        document.getElementById('waiting-container').style.display = '';
        document.getElementById('main-card').style.display = 'none';
        document.getElementById('opsi-jawaban').style.display = 'none';
        document.getElementById('soal').innerText = 'Terjadi error pada server.';
        setTimeout(ambilSoal, 2000);
        return;
      }
      // Pindahkan pengecekan quiz selesai ke atas, tapi hanya tampilkan slade terima kasih jika mode 'podium' dan current_index > total_soal
      if (data && data.mode === 'podium') {
        var waitingEl = document.getElementById('waiting-container');
        var mainCardEl = document.getElementById('main-card');
        var opsiJawabanEl = document.getElementById('opsi-jawaban');
        if (waitingEl) {
          waitingEl.style.display = '';
          if (window.lastWaitingType !== 'terimakasih') {
            waitingEl.innerHTML = `
              <div class="flex flex-col items-center justify-center min-h-screen w-full">
                <div class="mb-6 text-3xl sm:text-4xl font-bold text-yellow-400 drop-shadow">Terima Kasih!</div>
                <div class="bg-black/90 backdrop-blur-lg rounded-3xl shadow-2xl p-10 flex flex-col items-center border border-gray-800 max-w-md w-full">
                  <div class="mb-8">
                    <i class="fa-solid fa-trophy text-7xl text-yellow-400 drop-shadow"></i>
                  </div>
                  <h1 class="text-xl sm:text-3xl font-extrabold text-white text-center mb-3 tracking-wide drop-shadow-lg">Quiz telah selesai.<br>Terima kasih sudah mengikuti quiz ini!</h1>
                  <div class="text-lg text-gray-200 mb-6 text-center max-w-md">Selamat kepada para juara!<br>Jangan lupa tetap semangat belajar dan sampai jumpa di quiz berikutnya.</div>
                </div>
              </div>
            `;
            window.lastWaitingType = 'terimakasih';
          }
        }
        if (mainCardEl) {
          mainCardEl.style.display = 'none';
          if (window.mainCardDefaultHTML) mainCardEl.innerHTML = window.mainCardDefaultHTML;
          initMainCard();
        }
        if (opsiJawabanEl) opsiJawabanEl.style.display = 'none';
        var slade = document.getElementById('slade-jawaban');
        if (slade) slade.remove();
        setTimeout(ambilSoal, 2000);
        lastMode = data.mode;
        return;
      }
      // Pengecekan mode waiting PALING ATAS
      if (data && data.mode === 'waiting') {
        var waitingEl = document.getElementById('waiting-container');
        var mainCardEl = document.getElementById('main-card');
        var opsiJawabanEl = document.getElementById('opsi-jawaban');
        if (waitingEl) {
          waitingEl.style.display = '';
          // Jika mode waiting dan tidak ada soal aktif, tampilkan slade Menunggu Soal...
          if (data.id_soal) {
            if (window.waitingDefaultHTML) waitingEl.innerHTML = window.waitingDefaultHTML;
            window.lastWaitingType = 'default';
          } else {
            // Tidak ada soal aktif, peserta baru join ulang, host belum mulai quiz
            waitingEl.innerHTML = `
              <div class="flex flex-col items-center justify-center min-h-screen w-full">
                <div class="mb-6 text-3xl sm:text-4xl font-bold text-pink-500 drop-shadow">Quiz Segera Dimulai</div>
                <div class="bg-black/90 backdrop-blur-lg rounded-3xl shadow-2xl p-10 flex flex-col items-center border-4 border-pink-300 max-w-md w-full">
                  <div class="mb-8 flex flex-col items-center">
                    <i class="fa-solid fa-hourglass-half text-7xl text-pink-300 drop-shadow hourglass-anim"></i>
                    <div class="mt-4 font-bold text-pink-200">Menunggu host menampilkan soal...</div>
                  </div>
                  <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-3 text-center tracking-wide drop-shadow-lg">Quiz akan segera dimulai.<br>Silakan tunggu host menampilkan soal pertama.</h1>
                  <div class="text-lg text-pink-200 mb-6 text-center max-w-md">Jika halaman ini tidak berubah dalam beberapa menit, silakan hubungi host.</div>
                </div>
              </div>
            `;
            window.lastWaitingType = 'habis';
          }
        }
        if (mainCardEl) {
          mainCardEl.style.display = 'none';
          if (window.mainCardDefaultHTML) mainCardEl.innerHTML = window.mainCardDefaultHTML;
          initMainCard();
        }
        if (opsiJawabanEl) opsiJawabanEl.style.display = 'none';
        var slade = document.getElementById('slade-jawaban');
        if (slade) slade.remove();
        setTimeout(ambilSoal, 2000);
        lastMode = data.mode;
        return;
      }
      // Pastikan saat mode bukan waiting, waiting-container di-hide dan main-card di-show
      if (data && data.mode !== 'waiting' && data.mode !== 'grafik' && data.mode !== 'ranking') {
        var waitingEl = document.getElementById('waiting-container');
        var mainCardEl = document.getElementById('main-card');
        var opsiJawabanEl = document.getElementById('opsi-jawaban');
        if (waitingEl) waitingEl.style.display = 'none';
        if (mainCardEl) mainCardEl.style.display = '';
        if (opsiJawabanEl) opsiJawabanEl.style.display = '';
      }
      // Reset lastSoalId dan lastMode jika mode berubah ke soal
      if (data && data.mode === 'soal' && lastMode !== 'soal') {
        lastSoalId = null;
        lastMode = null;
      }
      // Di awal polling ambilSoal()
      if (typeof window.countdownSudahJalan === 'undefined') window.countdownSudahJalan = false;
      // Pada JS, saat mode soal, langsung tampilkan card jawaban tanpa countdown
      if (data && data.mode === 'soal') {
        // polling tetap lanjut, JANGAN return
      }
      // Reset flag jika mode bukan soal
      if (data && data.mode !== 'soal') {
        window.countdownSudahJalan = false;
      }
      // Jika mode soal, pastikan main-card dan opsi-jawaban di-show
      if (data && data.mode === 'soal') {
        var mainCardEl = document.getElementById('main-card');
        var opsiJawabanEl = document.getElementById('opsi-jawaban');
        if (mainCardEl) mainCardEl.style.display = '';
        if (opsiJawabanEl) opsiJawabanEl.style.display = '';
      }
      // baru setelah ini boleh cek id_soal dan panggil tampilkanSoal()
      let soalBaru = false;
      if (data && data.id_soal && data.id_soal != lastSoalId) {
        soalAktif = data.id_soal;
        lastSoalId = data.id_soal;
        waktuHabis = false;
        durasiSoal = data.durasi || 20;
        timerStartedForSoal = data.id_soal;
        // Render ulang main-card dari template awal setiap kali soal baru
        var mainCardEl = document.getElementById('main-card');
        if (mainCardEl && window.mainCardDefaultHTML) {
          mainCardEl.innerHTML = window.mainCardDefaultHTML;
          initMainCard();
        }
        tampilkanSoal(data, true); // reset timer
      } else if (data && data.id_soal) {
        tampilkanSoal(data, false); // update tampilan, jangan reset timer
      }
      // Cek mode presentasi
      if (data && data.mode === 'jawaban' && (lastMode !== 'jawaban' || data.id_soal !== lastSoalId)) {
        tampilkanSladeJawaban(data.jawaban_benar);
        lastSoalId = data.id_soal;
        lastMode = data.mode;
      } else {
        lastMode = data && data.mode ? data.mode : '';
      }
      if (data && data.mode !== 'waiting' && data.mode !== 'grafik' && data.mode !== 'ranking') {
        var opsiJawabanEl = document.getElementById('opsi-jawaban');
        if (opsiJawabanEl) opsiJawabanEl.style.display = '';
      }
      setTimeout(ambilSoal, 2000); // polling setiap 2 detik
    }

    function tampilkanSoal(data, resetTimer = false) {
      // Paksa tampilkan main-card dan sembunyikan waiting-container setiap kali tampilkanSoal dipanggil
      var mainCardEl = document.getElementById('main-card');
      var waitingEl = document.getElementById('waiting-container');
      if (mainCardEl) mainCardEl.style.display = '';
      if (waitingEl) waitingEl.style.display = 'none';
      // Jangan tampilkan card jawaban jika mode waiting
      if (lastMode === 'waiting' || data.mode === 'waiting' || data.mode === 'grafik' || data.mode === 'ranking') {
        return;
      }
      // Tampilkan info jumlah soal
      const soalInfo = document.getElementById('soal-info');
      if (soalInfo && data.current_index && data.total_soal) {
        soalInfo.innerText = `Soal ${data.current_index} dari ${data.total_soal}`;
      } else if (soalInfo) {
        soalInfo.innerText = '';
      }
      const soalDiv = document.getElementById('soal');
      // soalDiv.innerHTML = `${data.gambar ? `<img src='assets/soal/${data.gambar}' alt='Gambar Soal' class='mb-4 max-h-64 rounded-lg mx-auto shadow'>` : ''}<div>${data.soal}</div>`;
      ['A', 'B', 'C', 'D'].forEach(h => {
        const opsi = document.getElementById('opsi_' + h);
        const btn = document.getElementById('btn_' + h);
        if (opsi) opsi.innerText = data['jawaban_' + h.toLowerCase()];
        if (btn) btn.disabled = false;
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
      const timerEl = document.getElementById('timer');
      if (timerEl) timerEl.innerText = sisa; // update timer di elemen setiap polling
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
        if (elemenTimer) elemenTimer.innerText = sisa > 0 ? sisa : 0;
        if (sisa <= 0) {
          clearInterval(timerInterval);
          waktuHabis = true;
          disableSemuaTombol();
        }
      }, 500);
    }

    function disableSemuaTombol() {
      ['A', 'B', 'C', 'D'].forEach(h => {
        const btn = document.getElementById('btn_' + h);
        if (btn) btn.disabled = true;
      });
    }
    function enableSemuaTombol() {
      ['A', 'B', 'C', 'D'].forEach(h => {
        const btn = document.getElementById('btn_' + h);
        if (btn) btn.disabled = false;
      });
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
      // Sembunyikan card jawaban
      const opsiJawaban = document.getElementById('opsi-jawaban');
      if (opsiJawaban) opsiJawaban.style.display = 'none';
      // Ganti isi main-card dengan slade jawaban
      const mainCard = document.getElementById('main-card');
      if (!mainCard) return;
      // Ambil jawaban terakhir peserta untuk soal ini
      const res = await fetch(`api/jawaban_peserta.php?id_peserta=<?= $id_peserta ?>&id_soal=${soalAktif}`);
      const data = await res.json();
      let html = '<div class="flex flex-col items-center justify-center min-h-[300px]">';
      if (data.benar == 1) {
        html += '<i class="fa-solid fa-circle-check text-6xl text-green-400 drop-shadow animate-shimmer mb-4"></i>';
        html += '<div class="text-3xl font-bold text-green-300 mb-2 animate-fadein-top">Selamat! Jawaban Anda BENAR</div>';
        html += `<div class="text-lg mb-4 animate-fadein-top text-gray-100">Jawaban yang kamu pilih: <b class='px-2 py-1 rounded bg-green-900 text-green-200 animate-shimmer'>${data.jawaban}</b></div>`;
      } else {
        html += '<i class="fa-solid fa-circle-xmark text-6xl text-red-400 drop-shadow animate-fadein-top mb-4"></i>';
        html += '<div class="text-3xl font-bold text-red-300 mb-2 animate-fadein-top">Jawaban Anda SALAH</div>';
        html += `<div class="text-lg mb-2 animate-fadein-top text-gray-100">Jawaban yang kamu pilih: <b class='px-2 py-1 rounded bg-red-900 text-red-200'>${data.jawaban}</b></div>`;
        html += `<div class="text-lg mb-4 animate-fadein-top text-gray-100">Jawaban yang benar: <b class='px-2 py-1 rounded bg-green-900 text-green-200 animate-shimmer'>${jawabanBenar}</b></div>`;
      }
      html += '</div>';
      mainCard.innerHTML = html;
      // Slade hilang otomatis setelah 2 detik
      setTimeout(() => { ambilSoal(); }, 2000);
    }

    function tutupSladeJawaban() {
      // Kembalikan tampilan main-card ke mode normal jika masih mode soal
      ambilSoal(); // polling ulang, akan render ulang main-card dan opsi jawaban jika mode soal
    }
    // Tambahkan animasi shimmer
    const shimmerStyle = document.createElement('style');
    shimmerStyle.innerHTML = `
    @keyframes shimmer {
      0% { box-shadow: 0 0 0 0 #a7f3d0; }
      50% { box-shadow: 0 0 24px 6px #6ee7b7; }
      100% { box-shadow: 0 0 0 0 #a7f3d0; }
    }
    .animate-shimmer { animation: shimmer 1.2s infinite; }
    `;
    document.head.appendChild(shimmerStyle);

    function initMainCard() {
      // Re-attach event handler tombol jawaban
      ['A', 'B', 'C', 'D'].forEach(h => {
        const btn = document.getElementById('btn_' + h);
        if (btn) btn.onclick = function() { kirimJawaban(h); };
      });
    }

    window.onload = function() {
      // Simpan isi awal main-card
      var mainCard = document.getElementById('main-card');
      if (mainCard) window.mainCardDefaultHTML = mainCard.innerHTML;
      var waiting = document.getElementById('waiting-container');
      if (waiting) window.waitingDefaultHTML = waiting.innerHTML;
      window.lastWaitingType = 'default';
      initMainCard();
      ambilSoal();
    }
  </script>
</body>
</html> 