<?php
include_once 'api/db.php';
$kode = $_GET['kode'] ?? '';
if (!$kode) die('Kode quiz tidak ditemukan.');
// Ambil quiz
$stmt = $pdo->prepare("SELECT id, nama_quiz FROM tb_quiz WHERE kode_quiz = ?");
$stmt->execute([$kode]);
$quiz = $stmt->fetch();
if (!$quiz) die('Quiz tidak ditemukan.');
$id_quiz = $quiz['id'];

// Ambil soal pertama untuk tombol Mulai
$stmt = $pdo->prepare("SELECT id FROM tb_soal WHERE id_quiz = ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$id_quiz]);
$soal_pertama = $stmt->fetch();
$id_soal_pertama = $soal_pertama ? $soal_pertama['id'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Presentasi Quiz - <?= htmlspecialchars($quiz['nama_quiz']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Fredoka:wght@700&family=Montserrat:wght@700&family=Quicksand:wght@700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <style>
    html, body { height: 100%; font-family: 'Poppins', 'Montserrat', 'Fredoka', 'Quicksand', sans-serif; }
    body { min-height: 100vh; background: linear-gradient(135deg, #a78bfa 0%, #38bdf8 100%); overflow-x: hidden; }
    .fade-in { animation: fadeIn 0.7s cubic-bezier(.4,2,.6,1); }
    .fade-out { animation: fadeOut 0.5s; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(40px);} to { opacity: 1; transform: none; } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
    .glass {
      background: rgba(255,255,255,0.25);
      box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 2rem;
      border: 1px solid rgba(255,255,255,0.18);
    }
    .jawaban-anim { animation: fadeInJawaban 0.7s cubic-bezier(.4,2,.6,1) both; }
    @keyframes fadeInJawaban { from { opacity: 0; transform: translateY(40px) scale(0.95);} to { opacity: 1; transform: none; } }
    .jawaban-hover:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18); filter: brightness(1.08); z-index:2; }
    .progress-anim { transition: width 0.5s cubic-bezier(.4,2,.6,1); background: linear-gradient(90deg, #a78bfa, #38bdf8); box-shadow:0 2px 8px #0002; }
    #progress-badge { font-family: 'Fredoka', 'Poppins', sans-serif; }
    .kontrol-float-anim { animation: fadeIn 0.7s cubic-bezier(.4,2,.6,1); }
    .statistik-anim { animation: fadeInJawaban 0.7s cubic-bezier(.4,2,.6,1); }
    .partikel-bg { position:fixed;z-index:0;pointer-events:none;top:0;left:0;width:100vw;height:100vh; }
    .countdown-number { 
      transition: all 0.3s cubic-bezier(.4,2,.6,1);
      text-shadow: 0 4px 20px rgba(168,139,250,0.4);
      animation: countdownPulse 1s ease-in-out infinite;
    }
    @keyframes countdownPulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    .countdown-container {
      animation: countdownFadeIn 0.5s cubic-bezier(.4,2,.6,1);
    }
    @keyframes countdownFadeIn {
      from { opacity: 0; transform: scale(0.8) rotate(-5deg); }
      to { opacity: 1; transform: scale(1) rotate(0deg); }
    }
    .peserta-counter {
      animation: bounceIn 0.5s cubic-bezier(.4,2,.6,1);
      transition: all 0.3s ease;
    }
    @keyframes bounceIn {
      0% { transform: scale(0.3); }
      50% { transform: scale(1.1); }
      70% { transform: scale(0.9); }
      100% { transform: scale(1); }
    }
    .peserta-counter[data-changed='true'] {
      animation: pulse 0.5s cubic-bezier(.4,2,.6,1);
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    .peserta-wall-area {
      position: relative;
      width: 100%;
      min-height: 350px;
      height: 38vh;
      max-height: 60vh;
      background: none;
      overflow: hidden;
      margin-bottom: 1.5rem;
      transition: height 0.3s;
    }
    @media (max-width: 600px) {
      .peserta-wall-area { min-height: 200px; height: 30vh; }
    }
    .peserta-wall-badge {
      position: absolute;
      white-space: nowrap;
      transition: all 0.4s cubic-bezier(.4,2,.6,1);
      opacity: 0.92;
      z-index: 1;
      pointer-events: none;
      box-shadow: 0 4px 24px #0002;
      border-radius: 1.2rem;
      padding: 0.7rem 1.5rem;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      /* animation: pesertaWallIn 0.7s cubic-bezier(.4,2,.6,1); */
    }
    @keyframes pesertaWallIn {
      from { opacity: 0; transform: scale(0.7) translateY(30px); }
      to { opacity: 0.92; transform: scale(1) translateY(0); }
    }
    .glass-card-main {
      max-width: 90vw;
      max-height: 90vh;
      min-width: 90vw;
      min-height: 90vh;
      width: 90vw;
      height: 90vh;
      margin: 0;
      padding: 2vw 2vw;
      box-sizing: border-box;
      overflow: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      background: rgba(255,255,255,0.25);
      box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 2rem;
      border: 1px solid rgba(255,255,255,0.18);
      transition: max-width 0.3s, max-height 0.3s, padding 0.3s;
    }
    @media (max-width: 600px) {
      .glass-card-main {
        padding: 2vw 1vw;
        border-radius: 1.2rem;
        min-width: 98vw;
        width: 98vw;
        max-width: 98vw;
        min-height: 98vh;
        height: 98vh;
        max-height: 98vh;
      }
    }
  </style>
</head>
<body class="min-h-screen min-w-screen flex items-center justify-center" style="background:url('assets/img/bg.jpg') center center/cover no-repeat fixed;">
  <div id="progress-badge" class="fixed top-8 right-8 z-50"></div>
  <div id="kontrol-quiz-float"></div>
  <div class="glass max-w-[90vw] max-h-[90vh] w-[90vw] h-[90vh] rounded-2xl z-10 flex items-center justify-center mx-auto my-auto p-4 md:p-8">
    <div class="flex flex-col justify-center items-center h-full w-full gap-6">
      <div id="konten-presentasi" class="flex flex-col justify-center items-center h-full w-full gap-6"></div>
    </div>
  </div>
  <svg class="partikel-bg" width="100%" height="100%" viewBox="0 0 1920 1080" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="200" cy="200" r="120" fill="#a78bfa22"/>
    <circle cx="1720" cy="180" r="90" fill="#38bdf822"/>
    <rect x="400" y="900" width="180" height="80" rx="40" fill="#fbbf2422"/>
    <rect x="1500" y="900" width="120" height="60" rx="30" fill="#f472b622"/>
    <circle cx="960" cy="1000" r="60" fill="#f43f5e22"/>
  </svg>
  <script>
    let soalAktif = null;
    let mode = 'soal';
    let timerInterval;
    let timerSisa = 0;
    let timerDurasi = 0;
    let waktuMulai = null;
    let chart = null;
    let lastMode = null;
    let lastSoalId = null;
    let totalSoal = 0;
    let currentIndex = 0;
    let isCountdown = false;
    let countdownValue = 3;
    let pesertaColors = {}; // Store consistent colors for each participant
    let pesertaPosisi = null; // Untuk posisi tetap wall of names
    let pesertaTerakhir = [];
    let pesertaWallObserver = null;
    // Flag untuk animasi jawaban hanya sekali
    let sudahAnimasiJawaban = false;

    // Function to generate consistent color based on name
    function getPesertaColor(nama) {
      if (pesertaColors[nama]) {
        return pesertaColors[nama];
      }
      
      // Generate hash from name
      let hash = 0;
      for (let i = 0; i < nama.length; i++) {
        const char = nama.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
      }
      
      // Use hash to select color
      const warna = [
        '#fbbf24', '#34d399', '#60a5fa', '#f472b6', '#f87171', '#a78bfa', 
        '#facc15', '#38bdf8', '#fb7185', '#4ade80', '#f59e42', '#818cf8', 
        '#f43f5e', '#22d3ee', '#f472b6', '#fcd34d', '#a3e635', '#fca5a5', 
        '#f9a8d4', '#fdba74'
      ];
      
      const color = warna[Math.abs(hash) % warna.length];
      pesertaColors[nama] = color;
      return color;
    }

    // Function to generate consistent font based on name
    function getPesertaFont(nama) {
      const font = [
        'Poppins, sans-serif', 'Caveat, cursive', 'Bebas Neue, cursive', 
        'Indie Flower, cursive', 'Montserrat, sans-serif', 'Permanent Marker, cursive', 
        'Quicksand, sans-serif', 'Fredoka, sans-serif', 'Lobster, cursive', 'Rubik, sans-serif'
      ];
      
      let hash = 0;
      for (let i = 0; i < nama.length; i++) {
        const char = nama.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
      }
      
      return font[Math.abs(hash) % font.length];
    }

    async function pollingStatus() {
      const res = await fetch('api/quiz.php?action=status_presentasi&kode=<?= $kode ?>');
      const data = await res.json();
      if (!data || (!data.id_soal && data.mode !== 'waiting')) {
        document.getElementById('konten-presentasi').innerHTML = '<div class="text-center text-gray-400">Belum ada soal aktif.</div>';
        setTimeout(pollingStatus, 2000);
        return;
      }
      
      // Check if mode or soal berubah
      const modeChanged = (data.mode !== lastMode);
      const soalChanged = (data.id_soal !== lastSoalId);
      soalAktif = data.id_soal;
      mode = data.mode;
      timerDurasi = data.durasi || 20;
      waktuMulai = data.waktu_mulai;
      totalSoal = data.total_soal || 0;
      currentIndex = data.current_index || 0;
      renderProgressBadge();
      // Logika animasi jawaban hanya sekali
      if (data.mode === 'jawaban') {
        if (!sudahAnimasiJawaban || modeChanged || soalChanged) {
          renderPresentasi(data, true);
          sudahAnimasiJawaban = true;
        } else {
          // Jangan renderPresentasi ulang, biarkan tampilan diam
        }
      } else {
        sudahAnimasiJawaban = false;
        if (modeChanged || soalChanged) {
          renderPresentasi(data, true);
        } else {
          updateTimerOnly();
        }
      }
      // Update lastMode dan lastSoalId setelah renderPresentasi
      lastMode = data.mode;
      lastSoalId = data.id_soal;
      setTimeout(pollingStatus, 2000);
    }

    // Fungsi untuk langsung polling status sekali dan renderPresentasi tanpa delay
    async function pollStatusSekali() {
      const res = await fetch('api/quiz.php?action=status_presentasi&kode=<?= $kode ?>');
      const data = await res.json();
      if (!data || (!data.id_soal && data.mode !== 'waiting')) {
        document.getElementById('konten-presentasi').innerHTML = '<div class="text-center text-gray-400">Belum ada soal aktif.</div>';
        return;
      }
      lastMode = data.mode;
      lastSoalId = data.id_soal;
      soalAktif = data.id_soal;
      mode = data.mode;
      timerDurasi = data.durasi || 20;
      waktuMulai = data.waktu_mulai;
      totalSoal = data.total_soal || 0;
      currentIndex = data.current_index || 0;
      renderProgressBadge();
      renderPresentasi(data);
    }

    function renderProgressBadge() {
      const el = document.getElementById('progress-badge');
      if (currentIndex && totalSoal) {
        el.innerHTML = `<div class='bg-purple-700 text-white text-lg font-bold px-5 py-2 rounded-full shadow-lg border-4 border-white/80 select-none'>${currentIndex} dari ${totalSoal}</div>`;
      } else {
        el.innerHTML = '';
      }
    }

    function renderCountdown() {
      const konten = document.getElementById('konten-presentasi');
      let html = `<div class='flex flex-col items-center justify-center w-full h-96 countdown-container'>
        <div class='glass px-16 py-12 text-center shadow-2xl'>
          <div class='text-6xl md:text-8xl font-extrabold text-purple-700 mb-4 countdown-number' style='font-family:Fredoka,sans-serif;'>${countdownValue}</div>
          <div class='text-2xl font-bold text-gray-700'>Mulai dalam...</div>
        </div>
      </div>`;
      konten.innerHTML = html;
      
      // Start countdown animation
      const countdownInterval = setInterval(() => {
        countdownValue--;
        const numberEl = document.querySelector('.countdown-number');
        if (numberEl) {
          // Animate number change
          numberEl.style.transform = 'scale(1.3) rotate(5deg)';
          numberEl.style.color = '#f43f5e';
          numberEl.style.textShadow = '0 4px 20px rgba(244,63,94,0.6)';
          
          setTimeout(() => {
            numberEl.style.transform = 'scale(1) rotate(0deg)';
            numberEl.style.color = '#a78bfa';
            numberEl.style.textShadow = '0 4px 20px rgba(168,139,250,0.4)';
            numberEl.textContent = countdownValue;
          }, 300);
        }
        
        if (countdownValue <= 0) {
          clearInterval(countdownInterval);
          // Show "GO!" message with confetti
          if (numberEl) {
            numberEl.textContent = 'GO!';
            numberEl.style.color = '#22c55e';
            numberEl.style.transform = 'scale(1.4)';
            numberEl.style.textShadow = '0 4px 20px rgba(34,197,94,0.6)';
            
            // Trigger confetti
            if (window.confetti) {
              confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#22c55e', '#a78bfa', '#38bdf8', '#fbbf24']
              });
            }
          }
        }
      }, 1000);
    }

    function renderPresentasi(data, withAnim = true) {
      const konten = document.getElementById('konten-presentasi');
      let html = '';
      let tombolKontrol = '';
      if (mode === 'waiting') {
        const jumlahPeserta = data.peserta ? data.peserta.length : 0;
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(window.location.origin+'/join.php')}`;
        html = `
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-full w-full">
            <!-- Sidebar kiri -->
            <div class="flex flex-col gap-4 h-full items-stretch relative">
              <form id="form-mulai-quiz" method='post' action='host.php?kode=<?= htmlspecialchars($kode) ?>' class='w-full'>
                <input type='hidden' name='kontrol_presentasi' value='1'>
                <input type='hidden' name='aksi' value='tampilkan_soal'>
                <input type='hidden' name='id_soal' value='<?= htmlspecialchars($id_soal_pertama) ?>'>
                <button id="btn-mulai-quiz" type="button" class='w-full px-4 py-2 bg-white text-purple-700 border-2 border-purple-700 hover:bg-purple-700 hover:text-white rounded-full text-lg font-bold shadow transition-all mb-2'>Mulai</button>
              </form>
              <div class="bg-white bg-opacity-50 rounded-xl shadow px-4 py-2 flex flex-col items-center">
                <div class="text-4xl text-gray-900 font-semibold mb-1">Silahkan buka: <span class='font-mono'>${window.location.origin}/join.php</span></div>
                <div class="text-xl text-gray-900 mb-1">atau scan QR code:</div>
                <img src="${qrUrl}" alt="QR Join" class="rounded shadow w-50 h-50 object-contain mx-auto">
              </div>
              <div class="bg-white bg-opacity-50 rounded-xl shadow px-4 py-2 flex flex-col items-center">
                <div class="text-xl text-gray-900 font-semibold mb-1">PIN Game:</div>
                <div class="font-mono text-5xl font-extrabold text-indigo-700 tracking-widest bg-white bg-opacity-60 px-6 py-2 rounded-xl shadow border-2 border-indigo-300">${data.kode_quiz || '<?= htmlspecialchars($kode) ?>'}</div>
              </div>
              <div class="bg-white bg-opacity-50 rounded-xl shadow px-4 py-2 flex flex-col items-center mt-auto">
                <div class="text-xs text-gray-900 font-semibold mb-1">Jumlah Peserta</div>
                <div id="peserta-counter" class="peserta-counter text-3xl font-extrabold text-purple-600" style="font-family:Fredoka,sans-serif;">${jumlahPeserta}</div>
              </div>
            </div>
            <!-- Main area kanan -->
            <div class="flex flex-col h-full bg-white bg-opacity-50 rounded-xl shadow p-4">
              <div class="text-base text-gray-900 mb-2">Menunggu peserta...</div>
              <div id="daftar-peserta-badge" class="flex flex-row flex-wrap gap-2 min-h-[60px] w-full"></div>
            </div>
          </div>
        `;
        konten.innerHTML = html;
        // Pasang event listener tombol Mulai setelah render
        const btnMulai = document.getElementById('btn-mulai-quiz');
        if (btnMulai) {
          btnMulai.addEventListener('click', function(e) {
            e.preventDefault();
            // Ambil data form sebelum renderCountdown (karena form akan hilang)
            const form = document.getElementById('form-mulai-quiz');
            const formData = new FormData(form);
            const formAction = form.action;
            countdownValue = 3;
            renderCountdown();
            setTimeout(function() {
                // Submit form via fetch dengan data yang sudah diambil
                fetch(formAction, {
                  method: 'POST',
                  body: formData
                }).then(async function(response) {
                  const text = await response.text();
                  // Setelah sukses, polling cepat sampai mode soal
                  pollingCepatSampaiSoal();
                }).catch(function(err) {
                });
            }, 3000);
          });
        }
        renderPesertaBadge(data.peserta || []);
        pollingPesertaBadge();
        lastSoalId = null;
        clearKontrolFloat();
      } else if (mode === 'soal') {
        tombolKontrol = '';
        html = `<div class='flex flex-col items-center justify-center w-full mx-auto p-0 h-full'>`;
        html += `<div class='w-full flex flex-col items-center'>
          <div class='mt-8 mb-4 w-full flex justify-center'>
            <div class='w-full text-center'>
              <div class='text-4xl md:text-6xl font-extrabold text-indigo-50 drop-shadow-lg mb-4' style='font-family:Fredoka,sans-serif; letter-spacing:0.5px;'>${data.soal || ''}</div>
              ${(data.gambar && data.gambar !== '' && data.gambar !== null) ? `<img src='assets/soal/${data.gambar}' alt='Gambar Soal' class='my-4 max-h-72 rounded-lg mx-auto shadow-lg'>` : ''}
            </div>
          </div>
        </div>`;
        const warna = [
          'bg-gradient-to-r from-red-500 to-pink-400',
          'bg-gradient-to-r from-blue-500 to-cyan-400',
          'bg-gradient-to-r from-yellow-400 to-orange-300 text-gray-900',
          'bg-gradient-to-r from-green-500 to-lime-400'
        ];
        const ikon = [
          '<i class="fa-solid fa-triangle-exclamation mr-2"></i>',
          '<i class="fa-solid fa-diamond mr-2"></i>',
          '<i class="fa-solid fa-circle mr-2"></i>',
          '<i class="fa-solid fa-square mr-2"></i>'
        ];
        const opsi = ['A','B','C','D'];
        html += `<div class='grid grid-cols-1 md:grid-cols-2 gap-6 w-full px-2 md:px-0 mb-4'>`;
        opsi.forEach((h, i) => {
          const teks = typeof data['jawaban_' + h.toLowerCase()] !== 'undefined' && data['jawaban_' + h.toLowerCase()] !== null ? data['jawaban_' + h.toLowerCase()] : '-';
          html += `<div class='${warna[i]} text-white rounded-2xl p-6 text-xl font-bold flex items-center shadow-xl transition-all select-none jawaban-anim jawaban-hover w-full' style='animation-delay:${i*0.12}s'>${ikon[i]}${teks}</div>`;
        });
        html += `</div>`;
        html += `<div class='flex flex-col items-center gap-2 mb-2'>
          <div class='text-lg text-gray-600'>Waktu tersisa</div>
          <div id='timer' class='text-5xl font-extrabold text-red-500'></div>
        </div>`;
        html += `<div class='w-full h-3 bg-gray-200 rounded-full overflow-hidden mb-2'><div id='progress-bar' class='h-3 progress-anim rounded-full' style='width:100%'></div></div>`;
        html += `</div>`;
        konten.innerHTML = html;
        if (tombolKontrol) renderKontrolFloat(tombolKontrol); else clearKontrolFloat();
        startTimer();
      } else if (mode === 'jawaban') {
        lastSoalId = null;
        tombolKontrol = `<form method='post' action='host.php?kode=<?= htmlspecialchars($kode) ?>'>
          <input type='hidden' name='kontrol_presentasi' value='1'>
          <input type='hidden' name='aksi' value='ke_grafik'>
          <button class='px-5 py-2 bg-white text-blue-700 border-2 border-blue-700 hover:bg-blue-700 hover:text-white rounded-full text-lg font-bold shadow transition-all'>
            <i class='fa-solid fa-chart-column'></i> Grafik
          </button>
        </form>`;
        // Card jawaban benar
        let html = `<div class='flex flex-col items-center w-full p-0${withAnim ? ' fade-in jawaban-anim' : ''}'>`;
        html += `<div class='w-full flex flex-col items-center'>
          <div class='mt-8 mb-4 w-full flex justify-center'>
            <div class='w-full text-center'>
              <div class='text-3xl md:text-4xl font-extrabold text-green-700 mb-2 flex items-center justify-center gap-2'><i class='fa-solid fa-circle-check'></i> Jawaban Benar</div>
              ${data.gambar ? `<img src='assets/soal/${data.gambar}' alt='Gambar Soal' class='my-4 max-h-72 rounded-lg mx-auto shadow-lg'>` : ''}
              <div class='text-4xl font-extrabold text-green-600 mb-6'>${data['jawaban_' + data.jawaban_benar.toLowerCase()]}</div>
            </div>
          </div>
        </div>`;
        // Pilihan jawaban dengan highlight benar
        const warna = [
          'bg-red-500', 'bg-blue-500', 'bg-yellow-400 text-gray-900', 'bg-green-500'
        ];
        const ikon = [
          '<i class="fa-solid fa-triangle-exclamation mr-2"></i>',
          '<i class="fa-solid fa-diamond mr-2"></i>',
          '<i class="fa-solid fa-circle mr-2"></i>',
          '<i class="fa-solid fa-square mr-2"></i>'
        ];
        const opsi = ['A','B','C','D'];
        html += `<div class='grid grid-cols-1 md:grid-cols-2 gap-6 w-full px-2 md:px-0 mb-4'>`;
        opsi.forEach((h, i) => {
          const isBenar = h === data.jawaban_benar;
          const teks = typeof data['jawaban_' + h.toLowerCase()] !== 'undefined' && data['jawaban_' + h.toLowerCase()] !== null ? data['jawaban_' + h.toLowerCase()] : '-';
          // Efek khusus hanya untuk jawaban benar dan hanya saat animasi awal
          let efekBenar = '';
          if (isBenar) {
            efekBenar = ' animate-pulse-smooth ring-8 ring-green-400 shadow-2xl';
          } else {
            efekBenar = ' opacity-60';
          }
          html += `<div class='${warna[i]} text-white rounded-2xl p-6 text-xl font-bold flex items-center shadow-xl transition-all select-none${efekBenar}'>${ikon[i]}${teks} ${isBenar ? '<i class=\"fa-solid fa-check ml-2\"></i>' : ''}</div>`;
        });
        html += `</div>`;
        konten.innerHTML = html;
        if (tombolKontrol) renderKontrolFloat(tombolKontrol); else clearKontrolFloat();
      } else if (mode === 'grafik') {
        lastSoalId = null;
        tombolKontrol = `<form method='post' action='host.php?kode=<?= htmlspecialchars($kode) ?>'>
          <input type='hidden' name='kontrol_presentasi' value='1'>
          <input type='hidden' name='aksi' value='ke_ranking'>
          <button class='px-5 py-2 bg-white text-orange-700 border-2 border-orange-700 hover:bg-orange-700 hover:text-white rounded-full text-lg font-bold shadow transition-all'>
            <i class='fa-solid fa-trophy'></i> Ranking
          </button>
        </form>`;
        // Statistik jawaban
        let html = `<div class='flex flex-col items-center w-full p-0 fade-in statistik-anim'>`;
        html += `<div class='w-full flex flex-col items-center'>
          <div class='mt-8 mb-4 w-full flex justify-center'>
            <div class='w-full text-center'>
              <div class='text-2xl font-bold text-blue-700 flex items-center justify-center gap-2 mb-4'><i class='fa-solid fa-chart-column'></i> Statistik Jawaban</div>
              <div id='statistik-jawaban' class='flex flex-row justify-center gap-4'></div>
            </div>
          </div>
        </div>`;
        konten.innerHTML = html;
        renderStatistikJawaban();
        if (tombolKontrol) renderKontrolFloat(tombolKontrol); else clearKontrolFloat();
      } else if (mode === 'ranking') {
        lastSoalId = null;
        tombolKontrol = `<form method='post' action='host.php?kode=<?= htmlspecialchars($kode) ?>'>
          <input type='hidden' name='kontrol_presentasi' value='1'>
          <input type='hidden' name='aksi' value='soal_berikutnya'>
          <button class='px-5 py-2 bg-white text-indigo-700 border-2 border-indigo-700 hover:bg-indigo-700 hover:text-white rounded-full text-lg font-bold shadow transition-all'>
            <i class='fa-solid fa-forward'></i> Soal Berikutnya
          </button>
        </form>`;
        // Leaderboard
        let html = `<div class='flex flex-col items-center w-full p-0 fade-in'>`;
        html += `<div class='w-full flex flex-col items-center'>
          <div class='mt-8 mb-4 w-full flex justify-center'>
            <div class='w-full text-center'>
              <div class='text-2xl font-bold text-orange-700 flex items-center justify-center gap-2 mb-4'><i class='fa-solid fa-trophy'></i> Ranking Peserta</div>
              <div id='leaderboard-presentasi'><div class='text-center text-gray-400'>Memuat ranking...</div></div>
            </div>
          </div>
        </div>`;
        konten.innerHTML = html;
        renderLeaderboard();
        if (tombolKontrol) renderKontrolFloat(tombolKontrol); else clearKontrolFloat();
      }
    }

    function startTimer() {
      clearInterval(timerInterval);
      if (!waktuMulai) return;
      const mulai = new Date(waktuMulai).getTime();
      timerInterval = setInterval(() => {
        const now = Date.now();
        const sisa = timerDurasi - Math.floor((now - mulai) / 1000);
        const timerEl = document.getElementById('timer');
        if (timerEl) timerEl.innerText = sisa > 0 ? sisa : 0;
        // Progress bar
        const bar = document.getElementById('progress-bar');
        if (bar) {
          const persen = Math.max(0, Math.min(100, (sisa / timerDurasi) * 100));
          bar.style.width = persen + '%';
        }
        if (sisa <= 0) {
          clearInterval(timerInterval);
          // Auto next to jawaban after 1 second
          setTimeout(() => autoNextToJawaban(), 1000);
        }
      }, 500);
    }

    async function autoNextToJawaban() {
      // Submit form ke_jawaban via fetch
      const formData = new FormData();
      formData.append('kontrol_presentasi', '1');
      formData.append('aksi', 'ke_jawaban');
      
      try {
        const response = await fetch('host.php?kode=<?= htmlspecialchars($kode) ?>', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) {
        }
      } catch (error) {
      }
    }

    async function renderStatistikJawaban() {
      const res = await fetch('api/statistik_jawaban.php?id_soal=' + soalAktif);
      const data = await res.json();
      const el = document.getElementById('statistik-jawaban');
      const warna = [
        'bg-gradient-to-r from-red-500 to-pink-400',
        'bg-gradient-to-r from-blue-500 to-cyan-400',
        'bg-gradient-to-r from-yellow-400 to-orange-300 text-gray-900',
        'bg-gradient-to-r from-green-500 to-lime-400'
      ];
      const ikon = [
        '<i class="fa-solid fa-triangle-exclamation mr-2"></i>',
        '<i class="fa-solid fa-diamond mr-2"></i>',
        '<i class="fa-solid fa-circle mr-2"></i>',
        '<i class="fa-solid fa-square mr-2"></i>'
      ];
      const opsi = ['A','B','C','D'];
      let html = '';
      opsi.forEach((h, i) => {
        html += `<div class='${warna[i]} text-white rounded-2xl px-8 py-6 text-xl font-bold flex items-center shadow-xl transition-all select-none min-w-[120px] justify-center statistik-anim' style='animation-delay:${i*0.12}s'>${ikon[i]}<span class='text-2xl font-extrabold ml-2'>${data[h] || 0}</span></div>`;
      });
      el.innerHTML = html;
      // confetti saat statistik muncul
      if (window.confetti) setTimeout(()=>{ confetti({particleCount: 120, spread: 90, origin: {y:0.6}}); }, 400);
    }

    async function renderLeaderboard() {
      const res = await fetch('api/leaderboard.php?kode=<?= $kode ?>');
      const data = await res.json();
      let html = '<ol class="space-y-3">';
      data.forEach((p, i) => {
        html += `<li class="rank-anim bg-orange-50 border-l-4 border-orange-400 p-3 rounded shadow-sm flex justify-between items-center">
          <span><b>#${i+1}.</b> <span class="font-semibold">${p.nama}</span></span>
          <span class="font-bold text-orange-700 text-lg">${p.skor} Poin</span>
        </li>`;
      });
      html += '</ol>';
      document.getElementById('leaderboard-presentasi').innerHTML = html;
    }

    // Badge peserta modern dengan warna tetap, urut join, tidak random posisi
    function renderPesertaBadge(peserta) {
      const el = document.getElementById('daftar-peserta-badge');
      const counterEl = document.getElementById('peserta-counter');
      if (!el) return;

      // Update counter with animation if changed
      if (counterEl) {
        const oldValue = parseInt(counterEl.textContent);
        const newValue = peserta.length;
        if (oldValue !== newValue) {
          counterEl.setAttribute('data-changed', 'true');
          counterEl.textContent = newValue;
          setTimeout(() => counterEl.removeAttribute('data-changed'), 500);
        }
      }

      // Render badge peserta urut join, tidak random posisi
      el.innerHTML = '';
      peserta.forEach(nama => {
        const w = getPesertaColor(nama);
        const f = getPesertaFont(nama);
        el.innerHTML += `<div style="background:${w};font-family:${f};color:#222;font-size:1.3rem;font-weight:bold;padding:0.6rem 1.3rem;border-radius:1.2rem;box-shadow:0 4px 24px #0002;display:inline-flex;align-items:center;gap:0.7rem;">
          <i class='fa-solid fa-user-astronaut'></i> ${nama}
        </div>`;
      });
    }
    function pollingPesertaBadge() {
      fetch('api/quiz.php?action=daftar_peserta&kode=<?= $kode ?>')
        .then(res => res.json())
        .then(data => renderPesertaBadge(data));
      setTimeout(pollingPesertaBadge, 2000);
    }

    function renderKontrolFloat(html) {
      let el = document.getElementById('kontrol-quiz-float');
      if (!el) {
        el = document.createElement('div');
        el.id = 'kontrol-quiz-float';
        document.body.appendChild(el);
      }
      el.innerHTML = html;
    }
    function clearKontrolFloat() {
      const el = document.getElementById('kontrol-quiz-float');
      if (el) el.innerHTML = '';
    }

    // Polling cepat setelah submit Mulai, agar soal langsung tampil
    function pollingCepatSampaiSoal() {
      let intervalId = setInterval(async function() {
        const res = await fetch('api/quiz.php?action=status_presentasi&kode=<?= $kode ?>');
        const data = await res.json();
        if (data && data.mode === 'soal') {
          lastMode = data.mode;
          lastSoalId = data.id_soal;
          soalAktif = data.id_soal;
          mode = data.mode;
          timerDurasi = data.durasi || 20;
          waktuMulai = data.waktu_mulai;
          totalSoal = data.total_soal || 0;
          currentIndex = data.current_index || 0;
          renderProgressBadge();
          renderPresentasi(data);
          clearInterval(intervalId);
        }
      }, 300);
    }

    // Fungsi untuk update timer dan progress bar saja
    function updateTimerOnly() {
      // Update timer
      const timerEl = document.getElementById('timer');
      if (timerEl && waktuMulai && timerDurasi) {
        const mulai = new Date(waktuMulai).getTime();
        const now = Date.now();
        const sisa = timerDurasi - Math.floor((now - mulai) / 1000);
        timerEl.innerText = sisa > 0 ? sisa : 0;
      }
      // Update progress bar
      const bar = document.getElementById('progress-bar');
      if (bar && waktuMulai && timerDurasi) {
        const mulai = new Date(waktuMulai).getTime();
        const now = Date.now();
        const sisa = timerDurasi - Math.floor((now - mulai) / 1000);
        const persen = Math.max(0, Math.min(100, (sisa / timerDurasi) * 100));
        bar.style.width = persen + '%';
      }
    }

    // Animasi smooth pulse untuk jawaban benar
    const style = document.createElement('style');
    style.innerHTML = `@keyframes pulse-smooth {0%{transform:scale(1.08);box-shadow:0 0 0 0 #22c55e55;}50%{transform:scale(1.15);box-shadow:0 0 32px 8px #22c55e88;}100%{transform:scale(1.08);box-shadow:0 0 0 0 #22c55e55;}}.animate-pulse-smooth{animation:pulse-smooth 1.2s cubic-bezier(.4,2,.6,1) infinite;}`;
    document.head.appendChild(style);

    pollingStatus();
  </script>
</body>
</html> 