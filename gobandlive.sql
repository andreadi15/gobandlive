-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 08 Jan 2026 pada 16.39
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
-- Database: `gobandlive`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `band`
--

CREATE TABLE `band` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_band` varchar(100) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `tarif` decimal(10,2) NOT NULL,
  `kontak` varchar(15) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status_ketersediaan` enum('tersedia','tidak tersedia') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `band`
--

INSERT INTO `band` (`id`, `user_id`, `nama_band`, `genre`, `tarif`, `kontak`, `deskripsi`, `foto`, `status_ketersediaan`, `created_at`) VALUES
(1, 2, 'Rizky & The Bands', 'Pop Rock', 5000000.00, '082134567890', 'Band profesional dengan pengalaman 5 tahun di industri musik', NULL, 'tersedia', '2026-01-07 06:26:13'),
(2, 7, 'Roy Band', 'Rock', 18000000.00, '085213467646', NULL, NULL, 'tersedia', '2026-01-07 07:00:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_tampil`
--

CREATE TABLE `jadwal_tampil` (
  `id` int(11) NOT NULL,
  `id_band` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi` text NOT NULL,
  `status` enum('belum tampil','sudah tampil') DEFAULT 'belum tampil',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id` int(11) NOT NULL,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `total_transaksi` int(11) DEFAULT 0,
  `total_pendapatan` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `metode` varchar(50) NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('belum','menunggu','verified') DEFAULT 'belum',
  `tanggal_bayar` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_band` int(11) NOT NULL,
  `tanggal_acara` date NOT NULL,
  `lokasi` text NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('menunggu','diterima','dibatalkan','selesai') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulasan`
--

CREATE TABLE `ulasan` (
  `id` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_band` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `komentar` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `alamat` text DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pelanggan','band') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `no_hp`, `alamat`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@gobandlive.com', '081234567890', NULL, 'admin', '$2y$10$m5sdPsF2tKUhNWUpBUxLv.5336hnYv6wJ/ytol2vJ7/o1UkV33q6q', 'admin', '2026-01-07 06:26:13', '2026-01-07 07:02:02'),
(2, 'Rizky Febian', 'rizky@band.com', '082134567890', NULL, 'rizkyband', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'band', '2026-01-07 06:26:13', '2026-01-07 06:26:13'),
(3, 'John Doe', 'john@email.com', '083134567890', NULL, 'johndoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pelanggan', '2026-01-07 06:26:13', '2026-01-07 06:26:13'),
(4, 'Jolong', 'jolong@gmail.com', '08987654345', 'Jl. Jolong', 'jolong', '$2y$10$m5sdPsF2tKUhNWUpBUxLv.5336hnYv6wJ/ytol2vJ7/o1UkV33q6q', 'pelanggan', '2026-01-07 06:47:32', '2026-01-07 07:27:51'),
(5, 'asdadad', 'dasd@gmail.com', '3112312313', 'asdadasda', 'adsadadadad', '$2y$10$R8STm7ONABGaHw4j4/trAu/7Iu0v4TXxoSBr6pjuinA4J9oPyRk4O', 'pelanggan', '2026-01-07 06:51:59', '2026-01-07 06:51:59'),
(6, 'asdasda', 'sadasda@gmial.com', '0887654567', 'asdasdad', 'asdadsad', '$2y$10$rpLLgoGxejoL3Orvcha9huWb1AZi36d5xPBC1NvTreQm9yjuwItxu', 'pelanggan', '2026-01-07 06:54:38', '2026-01-07 06:54:38'),
(7, 'Roy Ganda', 'roy@gmail.com', '085213467646', 'Jl. Intisari', 'royganda', '$2y$10$m5sdPsF2tKUhNWUpBUxLv.5336hnYv6wJ/ytol2vJ7/o1UkV33q6q', 'band', '2026-01-07 07:00:39', '2026-01-07 07:00:39');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `band`
--
ALTER TABLE `band`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `jadwal_tampil`
--
ALTER TABLE `jadwal_tampil`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_band` (`id_band`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_band` (`id_band`);

--
-- Indeks untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_band` (`id_band`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `band`
--
ALTER TABLE `band`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jadwal_tampil`
--
ALTER TABLE `jadwal_tampil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `band`
--
ALTER TABLE `band`
  ADD CONSTRAINT `band_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_tampil`
--
ALTER TABLE `jadwal_tampil`
  ADD CONSTRAINT `jadwal_tampil_ibfk_1` FOREIGN KEY (`id_band`) REFERENCES `band` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_tampil_ibfk_2` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`id_band`) REFERENCES `band` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `ulasan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ulasan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ulasan_ibfk_3` FOREIGN KEY (`id_band`) REFERENCES `band` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
