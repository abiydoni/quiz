-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 01:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `tb_jawaban`
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
-- Table structure for table `tb_peserta`
--

CREATE TABLE `tb_peserta` (
  `id` int(11) NOT NULL,
  `id_quiz` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `skor` int(11) DEFAULT 0,
  `waktu_join` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_peserta`
--

INSERT INTO `tb_peserta` (`id`, `id_quiz`, `nama`, `skor`, `waktu_join`) VALUES
(1, 1, 'Sonto', 0, '2025-07-21 11:54:39'),
(2, 1, 'sugi', 0, '2025-07-21 14:54:45'),
(3, 1, 'Agus', 0, '2025-07-21 18:11:25'),
(4, 1, 'Ayu', 0, '2025-07-21 18:11:25'),
(5, 1, 'Supri', 0, '2025-07-21 18:12:07'),
(6, 1, 'Hermawan', 0, '2025-07-21 18:12:07');

-- --------------------------------------------------------

--
-- Table structure for table `tb_quiz`
--

CREATE TABLE `tb_quiz` (
  `id` int(11) NOT NULL,
  `kode_quiz` varchar(10) NOT NULL,
  `nama_quiz` varchar(100) NOT NULL,
  `waktu_buat` datetime DEFAULT current_timestamp(),
  `host` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_quiz`
--

INSERT INTO `tb_quiz` (`id`, `kode_quiz`, `nama_quiz`, `waktu_buat`, `host`) VALUES
(1, 'E408FC', '17an', '2025-07-21 11:18:33', 'abiydoni@gmail.com'),
(2, '36AD40', 'Tirakatan', '2025-07-21 12:36:54', 'abiydoni@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `tb_soal`
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
-- Dumping data for table `tb_soal`
--

INSERT INTO `tb_soal` (`id`, `id_quiz`, `soal`, `jawaban_a`, `jawaban_b`, `jawaban_c`, `jawaban_d`, `jawaban_benar`, `durasi`, `gambar`) VALUES
(1, 1, 'Warna Bendera Indonesia?', 'Merah Biru', 'Merah Putih', 'Merah Hitam', 'Merah Pink', 'B', 20, 'soal_687dcfe042754.jpg'),
(2, 1, 'Lagu Kebangsaan Indonesia?', 'Rayuan Pulau Kelapa', 'Indonesia Baru', 'Indonesia Kaya', 'Indonesia Raya', 'D', 20, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_status_quiz`
--

CREATE TABLE `tb_status_quiz` (
  `id` int(11) NOT NULL,
  `id_quiz` int(11) NOT NULL,
  `id_soal` int(11) DEFAULT NULL,
  `waktu_mulai` datetime NOT NULL,
  `mode` enum('waiting','soal','jawaban','grafik','ranking') DEFAULT 'soal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_status_quiz`
--

INSERT INTO `tb_status_quiz` (`id`, `id_quiz`, `id_soal`, `waktu_mulai`, `mode`) VALUES
(28, 1, NULL, '2025-07-21 15:54:05', 'waiting');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_peserta` (`id_peserta`),
  ADD KEY `id_soal` (`id_soal`);

--
-- Indexes for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`);

--
-- Indexes for table `tb_quiz`
--
ALTER TABLE `tb_quiz`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_quiz` (`kode_quiz`);

--
-- Indexes for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`);

--
-- Indexes for table `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_quiz` (`id_quiz`),
  ADD KEY `id_soal` (`id_soal`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_quiz`
--
ALTER TABLE `tb_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD CONSTRAINT `tb_jawaban_ibfk_1` FOREIGN KEY (`id_peserta`) REFERENCES `tb_peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_jawaban_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD CONSTRAINT `tb_peserta_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD CONSTRAINT `tb_soal_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_status_quiz`
--
ALTER TABLE `tb_status_quiz`
  ADD CONSTRAINT `tb_status_quiz_ibfk_1` FOREIGN KEY (`id_quiz`) REFERENCES `tb_quiz` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_status_quiz_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
