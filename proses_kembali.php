<?php
session_start();
require 'koneksi.php';

// Semak admin
if (!isset($_SESSION['UserID']) || ($_SESSION['Role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "ID peminjaman tidak valid.";
    header("Location: admin_peminjaman.php?page=peminjaman");
    exit;
}

$peminjaman_id = (int)$_GET['id'];

// Ambil data peminjaman
$q = mysqli_query($koneksi, 
    "SELECT p.*, b.BukuID, b.Judul 
     FROM peminjaman p
     JOIN buku b ON p.BukuID = b.BukuID
     WHERE p.PeminjamanID = $peminjaman_id LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    $_SESSION['error'] = "Data peminjaman tidak ditemukan.";
    header("Location: admin_peminjaman.php?page=peminjaman");
    exit;
}

if (!is_null($data['TanggalPengembalian'])) {
    $_SESSION['error'] = "Buku ini sudah pernah dikembalikan.";
    header("Location: admin_peminjaman.php?page=peminjaman");
    exit;
}

// Transaction
mysqli_begin_transaction($koneksi);

try {
    $today = date('Y-m-d');

    $upd1 = mysqli_prepare($koneksi, 
        "UPDATE peminjaman SET TanggalPengembalian = ?, Status = 'Dikembalikan' 
         WHERE PeminjamanID = ? AND TanggalPengembalian IS NULL");
    mysqli_stmt_bind_param($upd1, 'si', $today, $peminjaman_id);
    mysqli_stmt_execute($upd1);

    if (mysqli_stmt_affected_rows($upd1) === 0) {
        throw new Exception("Gagal update status pengembalian.");
    }

    $upd2 = mysqli_prepare($koneksi, 
        "UPDATE buku SET Stok = Stok + 1 WHERE BukuID = ?");
    mysqli_stmt_bind_param($upd2, 'i', $data['BukuID']);
    mysqli_stmt_execute($upd2);

    mysqli_commit($koneksi);

    $_SESSION['success'] = "✓ Buku \"" . htmlspecialchars($data['Judul']) . "\" berhasil dikembalikan. Stok diperbarui.";
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "✗ Gagal memproses pengembalian: " . $e->getMessage();
}

header("Location: admin_peminjaman.php?page=peminjaman");
exit;
?>