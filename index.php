<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$user_id = (int)$_SESSION['UserID'];

// Statistik
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peminjaman WHERE UserID = $user_id AND TanggalPengembalian IS NULL");
$buku_dipinjam = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peminjaman WHERE UserID = $user_id");
$total_koleksi = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

$q = mysqli_query($koneksi, "SELECT COALESCE(SUM(Stok),0) AS total FROM buku");
$total_stok = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

// Buku terbaru
$q = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY BukuID DESC LIMIT 8");

// Kuota
$sisa_kuota = max(0, 3 - $buku_dipinjam);
$kuota_penuh = $sisa_kuota <= 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0052CC;
            --brand-dark: #0747A6;
            --brand-light: #DEEBFF;
            --ink: #172B4D;
            --ink-2: #42526E;
            --muted: #5E6C84;
            --line: #DFE1E6;
            --line-soft: #EBECF0;
            --bg: #F4F5F7;
            --bg-soft: #FAFBFC;
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        html, body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        /* Angka rata */
        .tnum { font-variant-numeric: tabular-nums; }

        /* Card — border tipis, shadow netral, hover halus */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .card-book {
            cursor: pointer;
        }
        .card-book:hover {
            border-color: var(--brand);
            box-shadow: 0 1px 2px rgba(9,30,66,.08);
        }
        .card-book:hover .book-title {
            color: var(--brand);
        }

        /* Line clamp */
        .clamp-1 {
            display: -webkit-box; -webkit-line-clamp: 1;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .clamp-2 {
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .clamp-3 {
            display: -webkit-box; -webkit-line-clamp: 3;
            -webkit-box-orient: vertical; overflow: hidden;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* Radio / checkbox brand */
        input[type="radio"], input[type="checkbox"] { accent-color: var(--brand); }

        /* Fokus keyboard untuk aksesibilitas */
        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Modal */
        .modal-backdrop {
            background: rgba(9, 30, 66, .54);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- ============================ SIDEBAR ============================ -->
    <aside class="w-60 bg-white border-r border-[#DFE1E6] flex-col hidden md:flex h-full flex-shrink-0">

        <!-- Brand -->
        <div class="h-14 px-5 flex items-center gap-2.5 border-b border-[#EBECF0]">
            <img src="logo/logo.png" alt="Logo" class="h-7 object-contain">
            <span class="text-[13px] font-semibold tracking-tight" style="color: var(--ink);">Digital Library</span>
        </div>

        <!-- Menu label -->
        <div class="px-5 pt-5 pb-2 text-[10px] font-semibold uppercase tracking-[0.14em]" style="color: var(--muted);">
            Menu
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-2 space-y-0.5">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-white" style="background: var(--brand);">
                <i class="fas fa-house w-4 text-center text-[12px]"></i>
                <span>Dashboard</span>
            </a>
            <a href="cari_buku.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-magnifying-glass w-4 text-center text-[12px]"></i>
                <span>Cari & Pinjam</span>
            </a>
            <a href="koleksi.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-bookmark w-4 text-center text-[12px]"></i>
                <span>Koleksi Pribadi</span>
            </a>
            <a href="pengembalian.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-rotate-left w-4 text-center text-[12px]"></i>
                <span>Pengembalian</span>
            </a>
        </nav>

        <!-- Profil -->
        <div class="border-t border-[#EBECF0] p-3">
            <div class="flex items-center gap-3 px-2 py-2 mb-1">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['NamaLengkap'] ?? 'User'); ?>&background=DEEBFF&color=0052CC&bold=true"
                     alt="" class="w-8 h-8 rounded-full flex-shrink-0">
                <div class="min-w-0">
                    <div class="text-[13px] font-semibold truncate" style="color: var(--ink);">
                        <?= htmlspecialchars($_SESSION['NamaLengkap'] ?? 'User'); ?>
                    </div>
                    <div class="text-[11px] truncate" style="color: var(--muted);">
                        @<?= htmlspecialchars($_SESSION['Username'] ?? 'user'); ?>
                    </div>
                </div>
            </div>
            <a href="logout.php"
               class="flex items-center gap-2 px-3 py-2 rounded-md text-[12px] font-medium transition hover:bg-[#FFEBE6]"
               style="color: #BF2600;">
                <i class="fas fa-arrow-right-from-bracket w-3.5 text-center text-[11px]"></i>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <!-- ============================ MAIN ============================ -->
    <main class="flex-1 flex flex-col h-full overflow-hidden min-w-0">

        <!-- Topbar -->
        <header class="h-14 px-6 bg-white border-b border-[#DFE1E6] flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2 text-[13px]">
                <span style="color: var(--muted);">Dashboard</span>
                <span style="color: var(--line);">/</span>
                <span class="font-medium" style="color: var(--ink);">Ringkasan</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-md border text-[11px] font-medium"
                     style="<?= $kuota_penuh
                        ? 'background:#FFEBE6; border-color:#FFBDAD; color:#BF2600;'
                        : 'background:#E3FCEF; border-color:#ABF5D1; color:#006644;'; ?>">
                    <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $kuota_penuh ? '#BF2600' : '#00875A'; ?>;"></span>
                    <span class="tnum">Kuota <?= $sisa_kuota; ?>/3</span>
                </div>
                <div class="w-px h-5" style="background: var(--line);"></div>
                <div class="flex items-center gap-2.5">
                    <div class="text-right hidden sm:block">
                        <div class="text-[12px] font-semibold leading-tight" style="color: var(--ink);">
                            <?= htmlspecialchars($_SESSION['NamaLengkap']); ?>
                        </div>
                        <div class="text-[10px] leading-tight" style="color: var(--muted);">
                            @<?= htmlspecialchars($_SESSION['Username']); ?>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] font-semibold" style="background: var(--brand);">
                        <?= strtoupper(substr($_SESSION['NamaLengkap'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 flex overflow-hidden min-h-0">

            <!-- ============ KOLOM KIRI ============ -->
            <div class="flex-1 overflow-y-auto min-w-0">
                <div class="p-6 space-y-6 max-w-[1100px]">

                    <!-- Notifikasi -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                             style="background:#E3FCEF; border-color:#ABF5D1; color:#006644;">
                            <i class="fas fa-circle-check mt-0.5 text-[12px]"></i>
                            <span class="font-medium"><?= htmlspecialchars($_SESSION['success']); ?></span>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                             style="background:#FFEBE6; border-color:#FFBDAD; color:#BF2600;">
                            <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                            <span class="font-medium"><?= htmlspecialchars($_SESSION['error']); ?></span>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- ===== Header halaman ===== -->
                    <div>
                        <h1 class="text-[20px] font-semibold tracking-tight leading-tight" style="color: var(--ink);">
                            Selamat datang kembali
                        </h1>
                        <p class="text-[13px] mt-1" style="color: var(--muted);">
                            Berikut ringkasan aktivitas literasi Anda hari ini,
                            <span class="tnum"><?= date('d M Y'); ?></span>.
                        </p>
                    </div>

                    <!-- ===== Statistik ===== -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <!-- Dipinjam -->
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                                    Sedang Dipinjam
                                </span>
                                <i class="fas fa-book-open text-[12px]" style="color: var(--muted);"></i>
                            </div>
                            <div class="flex items-end gap-1.5">
                                <div class="text-[28px] font-semibold leading-none tnum" style="color: var(--ink);">
                                    <?= $buku_dipinjam; ?>
                                </div>
                                <div class="text-[12px] mb-0.5 tnum" style="color: var(--muted);">
                                    / 3 kuota
                                </div>
                            </div>
                            <!-- Progress bar -->
                            <div class="mt-3 h-1 rounded-full overflow-hidden" style="background: var(--line-soft);">
                                <div class="h-full transition-all"
                                     style="width: <?= min(100, ($buku_dipinjam/3)*100); ?>%; background: <?= $kuota_penuh ? '#BF2600' : 'var(--brand)'; ?>;"></div>
                            </div>
                        </div>

                        <!-- Total Koleksi -->
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                                    Total Koleksi
                                </span>
                                <i class="fas fa-clock-rotate-left text-[12px]" style="color: var(--muted);"></i>
                            </div>
                            <div class="text-[28px] font-semibold leading-none tnum" style="color: var(--ink);">
                                <?= $total_koleksi; ?>
                            </div>
                            <div class="text-[12px] mt-3" style="color: var(--muted);">
                                Sepanjang waktu
                            </div>
                        </div>

                        <!-- Buku Tersedia -->
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                                    Buku Tersedia
                                </span>
                                <i class="fas fa-layer-group text-[12px]" style="color: var(--muted);"></i>
                            </div>
                            <div class="text-[28px] font-semibold leading-none tnum" style="color: var(--ink);">
                                <?= $total_stok; ?>
                            </div>
                            <div class="text-[12px] mt-3" style="color: var(--muted);">
                                Eksemplar fisik
                            </div>
                        </div>
                    </div>

                    <!-- ===== Aktivitas terakhir ===== -->
                    <div class="card p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-[0.08em]"
                                          style="background: var(--brand-light); color: var(--brand);">
                                        <i class="fas fa-circle text-[5px]"></i>
                                        Status
                                    </span>
                                </div>
                                <?php if ($buku_dipinjam > 0): ?>
                                    <div class="text-[14px] font-semibold" style="color: var(--ink);">
                                        Anda sedang meminjam <?= $buku_dipinjam; ?> buku
                                    </div>
                                    <p class="text-[12px] mt-0.5" style="color: var(--muted);">
                                        Jangan lupa periksa tanggal pengembalian di halaman koleksi.
                                    </p>
                                <?php else: ?>
                                    <div class="text-[14px] font-semibold" style="color: var(--ink);">
                                        Belum ada buku yang dipinjam
                                    </div>
                                    <p class="text-[12px] mt-0.5" style="color: var(--muted);">
                                        Mulai jelajahi katalog untuk menemukan buku.
                                    </p>
                                <?php endif; ?>
                            </div>
                            <a href="<?= $buku_dipinjam > 0 ? 'koleksi.php' : 'cari_buku.php'; ?>"
                               class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md text-[12px] font-semibold text-white whitespace-nowrap transition"
                               style="background: var(--brand);"
                               onmouseover="this.style.background='var(--brand-dark)'"
                               onmouseout="this.style.background='var(--brand)'">
                                <?= $buku_dipinjam > 0 ? 'Buka Koleksi' : 'Cari Buku'; ?>
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ===== Koleksi buku ===== -->
                    <div>
                        <div class="flex items-baseline justify-between mb-3">
                            <div>
                                <h2 class="text-[15px] font-semibold tracking-tight" style="color: var(--ink);">
                                    Koleksi Terbaru
                                </h2>
                                <p class="text-[12px] mt-0.5" style="color: var(--muted);">
                                    Delapan judul terakhir yang ditambahkan.
                                </p>
                            </div>
                            <a href="cari_buku.php"
                               class="text-[12px] font-medium inline-flex items-center gap-1 transition hover:underline"
                               style="color: var(--brand);">
                                Lihat semua <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <?php
                            if ($q && mysqli_num_rows($q) > 0):
                                while ($buku = mysqli_fetch_assoc($q)):
                                    $has_cover = !empty($buku['Cover']) && $buku['Cover'] !== 'default.jpg' && file_exists('uploads/' . $buku['Cover']);
                                    $cover = $has_cover ? 'uploads/' . $buku['Cover'] : null;
                                    $stok = (int)$buku['Stok'];
                                    $tersedia = $stok > 0;
                            ?>
                                <div class="card card-book overflow-hidden flex flex-col"
                                     onclick='tampilDetail(<?= json_encode([
                                        "judul"    => $buku["Judul"],
                                        "penulis"  => $buku["Penulis"],
                                        "kategori" => $buku["Kategori"],
                                        "penerbit" => $buku["Penerbit"],
                                        "tahun"    => $buku["TahunTerbit"],
                                        "cover"    => $cover,
                                        "id"       => $buku["BukuID"],
                                        "stok"     => $stok,
                                        "isbn"     => $buku["ISBN"],
                                     ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>

                                    <!-- Cover -->
                                    <div class="aspect-[3/4] relative overflow-hidden" style="background: var(--bg);">
                                        <?php if ($cover): ?>
                                            <img src="<?= htmlspecialchars($cover); ?>"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                 alt=""
                                                 class="w-full h-full object-cover">
                                            <div class="hidden absolute inset-0 items-center justify-center"
                                                 style="background: var(--brand-light); color: var(--brand);">
                                                <i class="fas fa-book text-2xl"></i>
                                            </div>
                                        <?php else: ?>
                                            <!-- Placeholder tipografi — bukan gradient -->
                                            <div class="w-full h-full flex flex-col justify-between p-3" style="background: var(--brand-light);">
                                                <div class="text-[9px] font-semibold uppercase tracking-[0.14em] clamp-2" style="color: var(--brand);">
                                                    <?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?>
                                                </div>
                                                <div class="text-[12px] font-semibold leading-snug clamp-3" style="color: var(--brand-dark);">
                                                    <?= htmlspecialchars($buku['Judul']); ?>
                                                </div>
                                                <div class="text-[10px] font-medium clamp-1" style="color: var(--brand);">
                                                    <?= htmlspecialchars($buku['Penulis']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Stok badge -->
                                        <div class="absolute top-1.5 right-1.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tnum"
                                                  style="<?= $tersedia
                                                    ? 'background:#E3FCEF; color:#006644;'
                                                    : 'background:#FFEBE6; color:#BF2600;'; ?>">
                                                <?= $tersedia ? $stok . ' stok' : 'Habis'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="p-2.5 flex-1 flex flex-col">
                                        <div class="text-[12px] font-semibold leading-snug clamp-2 mb-0.5 book-title transition-colors" style="color: var(--ink);">
                                            <?= htmlspecialchars($buku['Judul']); ?>
                                        </div>
                                        <div class="text-[11px] clamp-1" style="color: var(--muted);">
                                            <?= htmlspecialchars($buku['Penulis']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <div class="col-span-full card p-10 text-center">
                                    <i class="fas fa-inbox text-2xl mb-2" style="color: var(--line);"></i>
                                    <p class="text-[12px]" style="color: var(--muted);">Belum ada buku dalam katalog.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer kecil -->
                    <div class="pt-2 pb-6 text-center text-[11px]" style="color: var(--muted);">
                        Digital Library &middot; Sistem Peminjaman Buku
                    </div>
                </div>
            </div>

            <!-- ============ PANEL KANAN: DETAIL ============ -->
            <aside class="hidden xl:flex flex-col w-80 flex-shrink-0 border-l border-[#DFE1E6] bg-white overflow-y-auto">

                <div class="h-14 px-5 flex items-center justify-between border-b border-[#EBECF0] flex-shrink-0">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em]" style="color: var(--muted);">
                        Detail Buku
                    </span>
                    <i class="fas fa-circle-info text-[12px]" style="color: var(--muted);"></i>
                </div>

                <div class="p-5 space-y-5">

                    <!-- Preview -->
                    <div id="detail-image-container"
                         class="w-full aspect-[4/5] rounded-md border flex items-center justify-center overflow-hidden"
                         style="border-color: var(--line); background: var(--bg-soft);">
                        <div class="text-center px-4">
                            <i class="fas fa-book-open text-2xl mb-2" style="color: var(--line);"></i>
                            <div class="text-[12px]" style="color: var(--muted);">
                                Pilih buku untuk melihat detail
                            </div>
                        </div>
                    </div>

                    <!-- Judul + Penulis -->
                    <div>
                        <div id="detail-judul" class="text-[14px] font-semibold leading-snug" style="color: var(--ink);">
                            Belum ada buku dipilih
                        </div>
                        <div id="detail-penulis" class="text-[12px] mt-1" style="color: var(--muted);">
                            Klik salah satu buku untuk melihat informasinya.
                        </div>
                    </div>

                    <!-- Stok -->
                    <div id="detail-stok-box"
                         class="hidden flex items-center justify-between px-3 py-2 rounded-md border text-[12px]">
                        <span class="font-medium" style="color: var(--ink-2);">
                            <i class="fas fa-boxes-stacked mr-1.5 text-[11px]"></i> Stok
                        </span>
                        <span id="detail-stok" class="font-semibold tnum">—</span>
                    </div>

                    <!-- Meta -->
                    <div class="pt-4 border-t" style="border-color: var(--line-soft);">
                        <div class="space-y-2.5 text-[12px]">
                            <div class="flex items-start justify-between gap-3">
                                <span style="color: var(--muted);">Kategori</span>
                                <span id="detail-kategori" class="text-right font-medium" style="color: var(--ink-2);">—</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span style="color: var(--muted);">Penerbit</span>
                                <span id="detail-penerbit" class="text-right font-medium clamp-2" style="color: var(--ink-2);">—</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span style="color: var(--muted);">Tahun</span>
                                <span id="detail-tahun" class="text-right font-medium tnum" style="color: var(--ink-2);">—</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span style="color: var(--muted);">ISBN</span>
                                <span id="detail-isbn" class="text-right font-medium tnum clamp-2" style="color: var(--ink-2);">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol pinjam -->
                    <form action="proses_pinjam.php" method="POST" id="form-pinjam" class="pt-1">
                        <input type="hidden" name="buku_dipinjam[]" id="input-buku-id" value="">
                        <button type="submit" id="btn-pinjam" disabled
                                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-md text-[12px] font-semibold transition cursor-not-allowed"
                                style="background: var(--line-soft); color: #97A0AF;">
                            <i class="fas fa-book-reader text-[11px]"></i>
                            Pinjam Buku
                        </button>
                    </form>

                    <div class="text-[11px] leading-relaxed" style="color: var(--muted);">
                        <i class="fas fa-circle-info mr-1 text-[10px]"></i>
                        Durasi peminjaman 14 hari, maksimal 3 buku per akun.
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script>
        let selectedBuku = null;

        function tampilDetail(data) {
            selectedBuku = data;

            document.getElementById('detail-judul').textContent = data.judul;
            document.getElementById('detail-penulis').textContent = 'oleh ' + data.penulis;
            document.getElementById('detail-kategori').textContent = data.kategori || '—';
            document.getElementById('detail-penerbit').textContent = data.penerbit || '—';
            document.getElementById('detail-tahun').textContent = data.tahun || '—';
            document.getElementById('detail-isbn').textContent = data.isbn || '—';

            // Gambar
            const imgContainer = document.getElementById('detail-image-container');
            if (data.cover) {
                imgContainer.innerHTML = `
                    <img src="${data.cover}" alt="" class="w-full h-full object-cover"
                         onerror="this.parentElement.innerHTML='<div class=\\'text-center px-4\\'><i class=\\'fas fa-book-open text-2xl mb-2\\' style=\\'color: var(--line);\\'></i><div class=\\'text-[12px]\\' style=\\'color: var(--muted);\\'>Cover tidak tersedia</div></div>'">
                `;
            } else {
                imgContainer.innerHTML = `
                    <div class="w-full h-full flex flex-col justify-between p-4" style="background: var(--brand-light);">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] clamp-2" style="color: var(--brand);">
                            ${data.kategori || 'Umum'}
                        </div>
                        <div class="text-[13px] font-semibold leading-snug clamp-3" style="color: var(--brand-dark);">
                            ${data.judul}
                        </div>
                        <div class="text-[10px] font-medium clamp-1" style="color: var(--brand);">
                            ${data.penulis}
                        </div>
                    </div>
                `;
            }

            // Stok
            const stokBox = document.getElementById('detail-stok-box');
            const stokEl = document.getElementById('detail-stok');
            stokBox.classList.remove('hidden');
            if (data.stok > 0) {
                stokBox.style.background = '#E3FCEF';
                stokBox.style.borderColor = '#ABF5D1';
                stokBox.style.color = '#006644';
                stokEl.textContent = data.stok + ' tersedia';
            } else {
                stokBox.style.background = '#FFEBE6';
                stokBox.style.borderColor = '#FFBDAD';
                stokBox.style.color = '#BF2600';
                stokEl.textContent = 'Habis';
            }

            // Tombol
            const btn = document.getElementById('btn-pinjam');
            const input = document.getElementById('input-buku-id');
            if (data.stok > 0) {
                btn.disabled = false;
                btn.style.cssText = 'background: var(--brand); color: #fff; cursor: pointer;';
                btn.onmouseover = function () { this.style.background = 'var(--brand-dark)'; };
                btn.onmouseout = function () { this.style.background = 'var(--brand)'; };
                input.value = data.id;
            } else {
                btn.disabled = true;
                btn.style.cssText = 'background: var(--line-soft); color: #97A0AF; cursor: not-allowed;';
                btn.onmouseover = null;
                btn.onmouseout = null;
                input.value = '';
            }
        }

        document.getElementById('form-pinjam').addEventListener('submit', function (e) {
            if (!selectedBuku || selectedBuku.stok <= 0) {
                e.preventDefault();
                return false;
            }
            const ok = confirm(`Pinjam buku "${selectedBuku.judul}"?\n\nDurasi peminjaman 14 hari.`);
            if (!ok) e.preventDefault();
        });
    </script>
</body>
</html>