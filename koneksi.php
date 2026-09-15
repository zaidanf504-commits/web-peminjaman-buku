<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "digitallibrary";

// Pastikan variabelnya bernama $koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Auto Migration: Pastikan kolom Status ada di tabel peminjaman
$check_col = @mysqli_query($koneksi, "SHOW COLUMNS FROM peminjaman LIKE 'Status'");
if ($check_col && mysqli_num_rows($check_col) == 0) {
    @mysqli_query($koneksi, "ALTER TABLE peminjaman ADD COLUMN Status VARCHAR(50) DEFAULT 'Dipinjam'");
    @mysqli_query($koneksi, "UPDATE peminjaman SET Status = 'Dikembalikan' WHERE TanggalPengembalian IS NOT NULL");
    @mysqli_query($koneksi, "UPDATE peminjaman SET Status = 'Dipinjam' WHERE TanggalPengembalian IS NULL");
}
?>