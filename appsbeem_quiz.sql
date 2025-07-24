-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Jul 2025 pada 18.31
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `appsbeem_quiz`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_jawaban`
--

CREATE TABLE `tb_jawaban` (
  `id` int(11) NOT NULL,
  `id_peserta` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban` enum('A','B','C','D') NOT NULL,
  `benar` tinyint(1) DEFAULT 0,
  `waktu_jawab` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_peserta`
--

CREATE TABLE `tb_peserta` (
  `id` int(11) NOT NULL,
  `id_quiz` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `skor` int(11) DEFAULT 0,
  `waktu_join` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_quiz`
--

CREATE TABLE `tb_quiz` (
  `id` int(11) NOT NULL,
  `kode_quiz` varchar(10) NOT NULL,
  `nama_quiz` varchar(100) NOT NULL,
  `waktu_buat` datetime DEFAULT current_timestamp(),
  `host` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_quiz`
--

INSERT INTO `tb_quiz` (`id`, `kode_quiz`, `nama_quiz`, `waktu_buat`, `host`) VALUES
(1, 'E408FC', '17an', '2025-07-21 11:18:33', 'abiydoni@gmail.com');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_soal`
--

CREATE TABLE `tb_soal` (
  `id` int(11) NOT NULL,
  `id_quiz` int(11) NOT NULL,
  `soal` text NOT NULL,
  `jawaban_a` varchar(255) NOT NULL,
  `jawaban_b` varchar(255) NOT NULL,
  `jawaban_c` varchar(255) NOT NULL,
  `jawaban_d` varchar(255) NOT NULL,
  `jawaban_benar` enum('A','B','C','D') NOT NULL,
  `durasi` int(11) DEFAULT 20,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_soal`
--

INSERT INTO `tb_soal` (`id`, `id_quiz`, `soal`, `jawaban_a`, `jawaban_b`, `jawaban_c`, `jawaban_d`, `jawaban_benar`, `durasi`, `gambar`) VALUES
(1, 1, 'Warna Bendera Indonesia?', 'Merah Biru', 'Merah Putih', 'Merah Hitam', 'Merah Pink', 'B', 20, 'soal_6881f17436689.jpg'),
(2, 1, 'Lagu Kebangsaan Indonesia?', 'Rayuan Pulau Kelapa', 'Indonesia Baru', 'Indonesia Kaya', 'Indonesia Raya', 'D', 20, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_status_quiz`
--

CREATE TABLE `tb_status_quiz` (
  `id` int(11) NOT NULL,
  `id_quiz` int(11) NOT NULL,
  `id_soal` int(11) DEFAULT NULL,
  `waktu_mulai` datetime NOT NULL,
  `mode` enum('waiting','soal','jawaban','grafik','ranking','podium') DEFAULT 'soal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_status_quiz`
--

INSERT INTO `tb_status_quiz` (`id`, `id_quiz`, `id_soal`, `waktu_mulai`, `mode`) VALUES
(40, 1, NULL, '2025-07-24 22:49:05', 'waiting');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_peserta` (`id_peserta`),
  ADD KEY `id_soal` (`id_soal`);

--
-- Indeks untuk tabel `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`);

--
-- Indeks untuk tabel `tb_quiz`
--
ALTER TABLE `tb_quiz`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_quiz` (`kode_quiz`);

--
-- Indeks untuk tabel `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`);

--
-- Indeks untuk tabel `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`),
  ADD KEY `id_soal` (`id_soal`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tb_peserta`
--
ALTER TABLE `tb_peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tb_quiz`
--
ALTER TABLE `tb_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD CONSTRAINT `tb_jawaban_ibfk_1` FOREIGN KEY (`id_peserta`) REFERENCES `tb_peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_jawaban_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD CONSTRAINT `tb_peserta_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD CONSTRAINT `tb_soal_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  ADD CONSTRAINT `tb_status_quiz_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_status_quiz_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
