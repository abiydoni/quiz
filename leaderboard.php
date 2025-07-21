<?php
include_once 'api/db.php';
$kode = $_GET['kode'] ?? '';
if (!$kode) {
  echo "Kode quiz tidak ditemukan!";
  exit;
}
// Ambil quiz
$stmt = $pdo->prepare("SELECT * FROM tb_quiz WHERE kode_quiz = ?");
$stmt->execute([$kode]);
$quiz = $stmt->fetch();
if (!$quiz) {
  echo "Quiz tidak ditemukan.";
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leaderboard Quiz</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html, body { height: 100%; margin: 0; padding: 0; }
    body { min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #c7d2fe 100%); }
    .fullscreen { position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 9999; background: inherit; }
    .badge {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      min-width: 180px; min-height: 120px; padding: 1.5rem 1rem;
      margin: 0.5rem; font-size: 1.5rem; font-weight: bold;
      box-shadow: 0 4px 24px #0002; transition: transform 0.2s;
      border: 3px solid #fff; cursor: pointer;
      animation: popin 0.7s cubic-bezier(.4,2,.6,1);
    }
    @keyframes popin { from { opacity: 0; transform: scale(0.7);} to { opacity: 1; transform: scale(1);} }
    .badge .skor { font-size: 1.2rem; font-weight: 600; margin-top: 0.5rem; }
  </style>
</head>
<body class="fullscreen flex flex-col items-center justify-center" style="overflow:hidden;">
  <div id="leaderboard-header" style="position:fixed;top:0;left:0;width:100vw;z-index:10;background:rgba(255,255,255,0.95);box-shadow:0 2px 12px #0001;">
    <div class="w-full max-w-6xl mx-auto flex flex-wrap gap-4 items-center justify-center py-4 px-6">
      <span class="bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-mono text-lg shadow">
        Kode Quiz: <b><?= htmlspecialchars($kode) ?></b>
      </span>
      <span class="text-lg text-gray-700 font-semibold">Quiz: <?= htmlspecialchars($quiz['nama_quiz']) ?></span>
      <a href="host.php?kode=<?= htmlspecialchars($kode) ?>" class="ml-auto inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold shadow transition-all">
        <i class="fa-solid fa-chalkboard-user"></i> Kontrol Quiz
      </a>
      <a href="index.php" class="text-orange-600 hover:underline">&larr; Beranda</a>
    </div>
    <h1 class="text-4xl font-extrabold text-center text-orange-600 mb-0 flex items-center justify-center gap-2 pb-2">
      <i class="fa-solid fa-trophy"></i> Leaderboard
    </h1>
    <div class="w-full flex justify-center">
      <div class="text-blue-900 text-center text-xl flex flex-col items-center gap-2 mb-4">
        <div class="flex items-center justify-center gap-2 mb-2">
          <i class="fa-solid fa-circle-info text-2xl text-blue-500"></i>
          <span class="font-bold text-blue-700 text-2xl">Cara Join Quiz</span>
        </div>
        <div class="mb-2">1. Buka <b>join.php</b> di browser HP/laptop Anda.</div>
        <div class="mb-2">2. Masukkan kode berikut:</div>
        <div class="font-mono text-3xl text-blue-700 tracking-widest mb-2" style="letter-spacing:0.2em;">
          <?= htmlspecialchars($kode) ?>
        </div>
        <div class="mb-2">3. Isi nama, klik <b>Masuk</b>.</div>
      </div>
    </div>
  </div>
  <div id="leaderboard-area" style="position:relative;width:100%;height:60vh;min-height:350px;max-height:70vh;margin-top:140px;">
    <div id="leaderboard"></div>
  </div>
  <script>
    // Array warna text dan font random
    const warna = [
      '#fbbf24', '#34d399', '#60a5fa', '#f472b6', '#f87171', '#a78bfa', '#facc15', '#38bdf8', '#fb7185', '#4ade80', '#f59e42', '#818cf8', '#f43f5e', '#22d3ee', '#f472b6', '#fcd34d', '#a3e635', '#fca5a5', '#f9a8d4', '#fdba74'
    ];
    const font = [
      'Poppins, sans-serif', 'Caveat, cursive', 'Bebas Neue, cursive', 'Indie Flower, cursive', 'Montserrat, sans-serif', 'Permanent Marker, cursive', 'Quicksand, sans-serif', 'Fredoka, sans-serif', 'Lobster, cursive', 'Rubik, sans-serif'
    ];
    // Google Fonts import
    const fontLink = document.createElement('link');
    fontLink.rel = 'stylesheet';
    fontLink.href = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Caveat&family=Indie+Flower&family=Montserrat:wght@700&family=Permanent+Marker&family=Poppins:wght@700&family=Quicksand:wght@700&family=Fredoka:wght@700&family=Lobster&family=Rubik:wght@700&display=swap';
    document.head.appendChild(fontLink);

    let pesertaPosisi = null;
    async function loadLeaderboard() {
      const res = await fetch('api/leaderboard.php?kode=<?= $kode ?>');
      const data = await res.json();
      const leaderboard = document.getElementById('leaderboard');
      const area = document.getElementById('leaderboard-area');
      if (!data.length) {
        leaderboard.innerHTML = '<div class="text-center text-gray-500">Belum ada peserta mengikuti quiz ini.</div>';
        return;
      }
      // Shuffle array peserta dan generate posisi hanya sekali
      if (!pesertaPosisi) {
        function shuffle(arr) {
          for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
          }
        }
        shuffle(data);
        pesertaPosisi = data.map((p, i) => {
          const w = warna[Math.floor(Math.random()*warna.length)];
          const f = font[Math.floor(Math.random()*font.length)];
          const areaW = area.offsetWidth - 350;
          const areaH = area.offsetHeight - 80;
          const left = Math.random() * areaW;
          const top = Math.random() * areaH;
          const rot = (Math.random() * 60 - 30).toFixed(1);
          return { nama: p.nama, warna: w, font: f, left, top, rot };
        });
      }
      let html = '';
      pesertaPosisi.forEach(pos => {
        html += `<div style="position:absolute;left:${pos.left}px;top:${pos.top}px;transform:rotate(${pos.rot}deg);font-size:2.5rem;font-family:${pos.font};color:${pos.warna};font-weight:bold;line-height:1.2;text-shadow:0 2px 8px #0001;white-space:nowrap;">
          ${pos.nama}
        </div>`;
      });
      leaderboard.innerHTML = html;
    }
    loadLeaderboard();
    setInterval(loadLeaderboard, 3000); // polling setiap 3 detik
    // Auto fullscreen
    document.addEventListener('DOMContentLoaded', () => {
      if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    });
  </script>
</body>
</html> 