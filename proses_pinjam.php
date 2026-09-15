<?php
session_start();
require 'koneksi.php';

// ============================================================
// 1. SEMAK LOGIN
// ============================================================
if (!isset($_SESSION['UserID'])) {
    $_SESSION['error'] = "Sesi anda tamat. Sila login semula.";
    header("Location: login.php");
    exit;
}

// ============================================================
// 2. SEMAK METHOD POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Akses tidak sah.";
    header("Location: cari_buku.php");
    exit;
}

$user_id   = (int)$_SESSION['UserID'];
$today     = date('Y-m-d');
$due       = date('Y-m-d', strtotime('+14 days'));
$max_quota = 3;

// ============================================================
// 3. AMBIL & VALIDASI INPUT
// ============================================================
$buku_ids = $_POST['buku_dipinjam'] ?? [];

if (!is_array($buku_ids) || empty($buku_ids)) {
    $_SESSION['error'] = "Tidak ada buku yang dipilih untuk dipinjam.";
    header("Location: cari_buku.php");
    exit;
}

// Bersihkan & buang duplikat
$buku_ids = array_values(array_unique(array_map('intval', $buku_ids)));
$buku_ids = array_filter($buku_ids, fn($id) => $id > 0);

if (empty($buku_ids)) {
    $_SESSION['error'] = "ID buku tidak valid.";
    header("Location: cari_buku.php");
    exit;
}

if (count($buku_ids) > $max_quota) {
    $_SESSION['error'] = "Maksimal $max_quota buku per peminjaman.";
    header("Location: cari_buku.php");
    exit;
}

// ============================================================
// 4. SEMAK KUOTA USER
// ============================================================
$q_kuota = mysqli_query($koneksi, 
    "SELECT COUNT(*) AS total FROM peminjaman 
     WHERE UserID = $user_id AND TanggalPengembalian IS NULL");
$kuota_data = mysqli_fetch_assoc($q_kuota);
$sedang_dipinjam = (int)$kuota_data['total'];
$sisa_kuota = $max_quota - $sedang_dipinjam;

if ($sisa_kuota <= 0) {
    $_SESSION['error'] = "Kuota peminjaman penuh ($max_quota buku). Kembalikan buku terlebih dahulu.";
    header("Location: koleksi.php");
    exit;
}

if (count($buku_ids) > $sisa_kuota) {
    $_SESSION['error'] = "Anda hanya boleh meminjam $sisa_kuota buku lagi (sisa kuota).";
    header("Location: cari_buku.php");
    exit;
}

// ============================================================
// 5. SEMAK SETIAP BUKU: WUJUD, STOK, & BELUM DIPINJAM
// ============================================================
$id_list = implode(',', $buku_ids);

$q_buku = mysqli_query($koneksi, 
    "SELECT b.BukuID, b.Judul, b.Stok 
     FROM buku b 
     WHERE b.BukuID IN ($id_list)");
$buku_data = [];
while ($row = mysqli_fetch_assoc($q_buku)) {
    $buku_data[(int)$row['BukuID']] = $row;
}

// Semak semua buku wujud
$buku_tidak_ada = array_diff($buku_ids, array_keys($buku_data));
if (!empty($buku_tidak_ada)) {
    $_SESSION['error'] = "Sebagian buku tidak ditemukan di database.";
    header("Location: cari_buku.php");
    exit;
}

// Semak stok semua buku
$stok_habis = [];
foreach ($buku_data as $id => $b) {
    if ((int)$b['Stok'] <= 0) {
        $stok_habis[] = $b['Judul'];
    }
}
if (!empty($stok_habis)) {
    $_SESSION['error'] = "Stok habis: " . implode(', ', $stok_habis);
    header("Location: cari_buku.php");
    exit;
}

// Semak user belum pinjam buku yang sama (yang masih aktif)
$q_aktif = mysqli_query($koneksi, 
    "SELECT p.BukuID, b.Judul 
     FROM peminjaman p
     JOIN buku b ON p.BukuID = b.BukuID
     WHERE p.UserID = $user_id 
       AND p.TanggalPengembalian IS NULL 
       AND p.BukuID IN ($id_list)");
$sudah_dipinjam = [];
while ($row = mysqli_fetch_assoc($q_aktif)) {
    $sudah_dipinjam[] = $row['Judul'];
}
if (!empty($sudah_dipinjam)) {
    $_SESSION['error'] = "Anda masih meminjam: " . implode(', ', $sudah_dipinjam) . ". Kembalikan dulu.";
    header("Location: cari_buku.php");
    exit;
}

// ============================================================
// 6. RATE LIMITING — max 10 peminjaman per hari
// ============================================================
$q_rate = mysqli_query($koneksi, 
    "SELECT COUNT(*) AS total FROM peminjaman 
     WHERE UserID = $user_id 
       AND TanggalPeminjaman = '$today'");
$rate_data = mysqli_fetch_assoc($q_rate);
if ((int)$rate_data['total'] >= 10) {
    $_SESSION['error'] = "Terlalu banyak peminjaman hari ini. Coba lagi besok.";
    header("Location: cari_buku.php");
    exit;
}

// ============================================================
// 7. PROSES DALAM TRANSACTION
// ============================================================
mysqli_begin_transaction($koneksi);

try {
    $berhasil = 0;

    $insert_stmt = mysqli_prepare($koneksi, 
        "INSERT INTO peminjaman (UserID, BukuID, TanggalPeminjaman, TanggalKembaliEstimasi) 
         VALUES (?, ?, ?, ?)");
    
    $update_stmt = mysqli_prepare($koneksi, 
        "UPDATE buku SET Stok = Stok - 1 WHERE BukuID = ? AND Stok > 0");

    foreach ($buku_ids as $buku_id) {
        // Insert peminjaman
        mysqli_stmt_bind_param($insert_stmt, 'iiss', $user_id, $buku_id, $today, $due);
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("Gagal insert peminjaman untuk BukuID $buku_id");
        }

        // Kurangi stok
        mysqli_stmt_bind_param($update_stmt, 'i', $buku_id);
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal update stok untuk BukuID $buku_id");
        }

        if (mysqli_stmt_affected_rows($update_stmt) === 0) {
            throw new Exception("Stok buku #$buku_id sudah habis (race condition).");
        }

        $berhasil++;
    }

    mysqli_stmt_close($insert_stmt);
    mysqli_stmt_close($update_stmt);

    mysqli_commit($koneksi);

    $_SESSION['success'] = "✓ Berhasil meminjam $berhasil buku! Harap dikembalikan sebelum " 
                         . date('d M Y', strtotime($due)) . " (14 hari).";
    header("Location: koleksi.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "✗ Gagal meminjam: " . $e->getMessage();
    header("Location: cari_buku.php");
    exit;
}
?>