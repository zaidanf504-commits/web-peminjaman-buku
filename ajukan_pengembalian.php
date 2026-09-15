<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "ID peminjaman tidak valid.";
    header("Location: pengembalian.php");
    exit;
}

$peminjaman_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['UserID'];

// Cek apakah data peminjaman valid dan milik user yang login
$q = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE PeminjamanID = $peminjaman_id AND UserID = $user_id AND TanggalPengembalian IS NULL LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    $_SESSION['error'] = "Data peminjaman tidak ditemukan atau sudah dikembalikan.";
    header("Location: pengembalian.php");
    exit;
}

if (($data['Status'] ?? '') === 'Menunggu Konfirmasi') {
    $_SESSION['error'] = "Pengajuan pengembalian untuk buku ini sudah dikirim. Harap tunggu konfirmasi admin.";
    header("Location: pengembalian.php");
    exit;
}

// Update status menjadi 'Menunggu Konfirmasi'
$update = mysqli_query($koneksi, "UPDATE peminjaman SET Status = 'Menunggu Konfirmasi' WHERE PeminjamanID = $peminjaman_id AND UserID = $user_id");

if ($update) {
    $_SESSION['success'] = "Pengajuan pengembalian berhasil dikirim. Silakan tunggu konfirmasi dari admin.";
} else {
    $_SESSION['error'] = "Gagal mengajukan pengembalian: " . mysqli_error($koneksi);
}

header("Location: pengembalian.php");
exit;
