<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengembalian.php");
    exit;
}

$user_id       = (int)$_SESSION['UserID'];
$peminjaman_id = (int)($_POST['peminjaman_id'] ?? 0);
$rating        = (int)($_POST['rating'] ?? 0);
$komentar      = trim($_POST['komentar'] ?? '');

// Validasi
if ($peminjaman_id <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Data ulasan tidak valid. Rating harus 1-5.";
    header("Location: pengembalian.php");
    exit;
}

// Semak peminjaman milik user & sudah dikembalikan
$q = mysqli_query($koneksi, 
    "SELECT p.PeminjamanID, p.BukuID, p.TanggalPengembalian 
     FROM peminjaman p 
     WHERE p.PeminjamanID = $peminjaman_id AND p.UserID = $user_id LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    $_SESSION['error'] = "Peminjaman tidak ditemukan.";
    header("Location: pengembalian.php");
    exit;
}

if (is_null($data['TanggalPengembalian'])) {
    $_SESSION['error'] = "Buku belum dikembalikan. Ulasan hanya bisa diberikan setelah buku dikembalikan.";
    header("Location: pengembalian.php");
    exit;
}

$buku_id = (int)$data['BukuID'];

// Semak belum pernah diulas
$cek = mysqli_query($koneksi, 
    "SELECT UlasanID FROM ulasan WHERE PeminjamanID = $peminjaman_id LIMIT 1");
if (mysqli_num_rows($cek) > 0) {
    $_SESSION['error'] = "Anda sudah memberikan ulasan untuk buku ini.";
    header("Location: pengembalian.php");
    exit;
}

// Insert
$komentar_esc = mysqli_real_escape_string($koneksi, $komentar);
$insert = mysqli_query($koneksi, 
    "INSERT INTO ulasan (PeminjamanID, UserID, BukuID, Rating, Komentar) 
     VALUES ($peminjaman_id, $user_id, $buku_id, $rating, '$komentar_esc')");

if ($insert) {
    $_SESSION['success'] = "✓ Terima kasih! Ulasan anda berjaya dihantar.";
} else {
    $_SESSION['error'] = "✗ Gagal menyimpan ulasan: " . mysqli_error($koneksi);
}

header("Location: pengembalian.php");
exit;
?>