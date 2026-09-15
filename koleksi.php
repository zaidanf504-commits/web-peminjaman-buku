<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$user_id = (int)$_SESSION['UserID'];

$query = "SELECT peminjaman.*, buku.Judul, buku.Penulis, buku.Penerbit, buku.Cover, buku.Kategori
          FROM peminjaman
          JOIN buku ON peminjaman.BukuID = buku.BukuID
          WHERE peminjaman.UserID = $user_id AND peminjaman.TanggalPengembalian IS NULL
          ORDER BY peminjaman.TanggalPeminjaman DESC";
$result = mysqli_query($koneksi, $query);

$total_aktif = $result ? mysqli_num_rows($result) : 0;
$sisa_kuota  = max(0, 3 - $total_aktif);
$kuota_penuh = $sisa_kuota <= 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Pribadi — Digital Library</title>
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
            --danger: #BF2600;
            --danger-bg: #FFEBE6;
            --danger-line: #FFBDAD;
            --success: #006644;
            --success-bg: #E3FCEF;
            --success-line: #ABF5D1;
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        html, body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .tnum { font-variant-numeric: tabular-nums; }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .card-book { cursor: default; }
        .card-book:hover {
            border-color: var(--brand);
            box-shadow: 0 1px 2px rgba(9,30,66,.08);
        }
        .card-book:hover .book-title { color: var(--brand); }

        /* Clamp */
        .clamp-1 { display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            padding: 7px 12px;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }
        .btn-ghost {
            background: #fff;
            color: var(--ink-2);
            border: 1px solid var(--line);
        }
        .btn-ghost:hover {
            background: var(--bg);
            border-color: var(--ink-2);
            color: var(--ink);
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* Fokus */
        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- ============================ SIDEBAR ============================ -->
    <aside class="w-60 bg-white border-r border-[#DFE1E6] flex-col hidden md:flex h-full flex-shrink-0">
        <div class="h-14 px-5 flex items-center gap-2.5 border-b border-[#EBECF0]">
            <img src="logo/logo.png" alt="Logo" class="h-7 object-contain">
            <span class="text-[13px] font-semibold tracking-tight" style="color: var(--ink);">Digital Library</span>
        </div>

        <div class="px-5 pt-5 pb-2 text-[10px] font-semibold uppercase tracking-[0.14em]" style="color: var(--muted);">
            Menu
        </div>

        <nav class="flex-1 px-2 space-y-0.5">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-house w-4 text-center text-[12px]"></i>
                <span>Dashboard</span>
            </a>
            <a href="cari_buku.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-magnifying-glass w-4 text-center text-[12px]"></i>
                <span>Cari & Pinjam</span>
            </a>
            <a href="koleksi.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-white" style="background: var(--brand);">
                <i class="fas fa-bookmark w-4 text-center text-[12px]"></i>
                <span>Koleksi Pribadi</span>
            </a>
            <a href="pengembalian.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-rotate-left w-4 text-center text-[12px]"></i>
                <span>Pengembalian</span>
            </a>
        </nav>

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
                <span style="color: var(--muted);">Koleksi</span>
                <span style="color: var(--line);">/</span>
                <span class="font-medium" style="color: var(--ink);">Buku yang Dipinjam</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-md border text-[11px] font-medium"
                     style="<?= $kuota_penuh
                        ? 'background:#FFEBE6; border-color:#FFBDAD; color:#BF2600;'
                        : 'background:#E3FCEF; border-color:#ABF5D1; color:#006644;'; ?>">
                    <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $kuota_penuh ? '#BF2600' : '#00875A'; ?>;"></span>
                    <span class="tnum">Sisa kuota <?= $sisa_kuota; ?>/3</span>
                </div>
                <button type="button" onclick="location.reload()"
                        class="w-8 h-8 rounded-md border flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="border-color: var(--line); color: var(--muted);"
                        title="Muat ulang">
                    <i class="fas fa-rotate-right text-[12px]"></i>
                </button>
            </div>
        </header>

        <!-- Scroll area -->
        <div class="flex-1 overflow-y-auto min-h-0">
            <div class="p-6 max-w-[1200px] space-y-5">

                <!-- Notifikasi -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                         style="background: var(--success-bg); border-color: var(--success-line); color: var(--success);">
                        <i class="fas fa-circle-check mt-0.5 text-[12px]"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['success']); ?></span>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                         style="background: var(--danger-bg); border-color: var(--danger-line); color: var(--danger);">
                        <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['error']); ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Header halaman -->
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <div>
                        <h1 class="text-[20px] font-semibold tracking-tight leading-tight" style="color: var(--ink);">
                            Koleksi Pribadi
                        </h1>
                        <p class="text-[13px] mt-1" style="color: var(--muted);">
                            <?php if ($total_aktif > 0): ?>
                                Anda sedang meminjam
                                <span class="font-semibold tnum" style="color: var(--ink-2);"><?= $total_aktif; ?> buku</span>.
                                Durasi peminjaman 14 hari sejak tanggal pinjam.
                            <?php else: ?>
                                Daftar buku yang sedang Anda pinjam akan muncul di sini.
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($total_aktif > 0): ?>
                        <a href="cari_buku.php" class="btn btn-primary self-start sm:self-auto">
                            <i class="fas fa-plus text-[10px]"></i>
                            <span>Pinjam buku lain</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Grid buku -->
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        <?php while ($pinjam = mysqli_fetch_assoc($result)):
                            $has_cover = !empty($pinjam['Cover']) && $pinjam['Cover'] !== 'default.jpg' && file_exists('uploads/' . $pinjam['Cover']);
                            $cover = $has_cover ? 'uploads/' . $pinjam['Cover'] : null;

                            // Hitung sisa hari
                            $tanggal_kembali = strtotime($pinjam['TanggalKembaliEstimasi']);
                            $hari_ini = strtotime(date('Y-m-d'));
                            $sisa_hari = (int)round(($tanggal_kembali - $hari_ini) / 86400);

                            if ($sisa_hari < 0) {
                                $badge_label = 'Telat ' . abs($sisa_hari) . ' hari';
                                $badge_style = 'background:#FFEBE6; color:#BF2600;';
                            } elseif ($sisa_hari === 0) {
                                $badge_label = 'Hari ini';
                                $badge_style = 'background:#FFEBE6; color:#BF2600;';
                            } elseif ($sisa_hari <= 3) {
                                $badge_label = $sisa_hari . ' hari lagi';
                                $badge_style = 'background:#FFEBE6; color:#BF2600;';
                            } else {
                                $badge_label = $sisa_hari . ' hari lagi';
                                $badge_style = 'background:#E3FCEF; color:#006644;';
                            }
                        ?>
                        <div class="card card-book overflow-hidden flex flex-col">

                            <!-- Cover -->
                            <div class="aspect-[3/4] relative overflow-hidden" style="background: var(--bg);">
                                <?php if ($cover): ?>
                                    <img src="<?= htmlspecialchars($cover); ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                         alt="" class="w-full h-full object-cover">
                                    <div class="hidden absolute inset-0 items-center justify-center"
                                         style="background: var(--brand-light); color: var(--brand);">
                                        <i class="fas fa-book text-2xl"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col justify-between p-3" style="background: var(--brand-light);">
                                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] clamp-2" style="color: var(--brand);">
                                            <?= htmlspecialchars($pinjam['Kategori'] ?: 'Umum'); ?>
                                        </div>
                                        <div class="text-[12px] font-semibold leading-snug clamp-3" style="color: var(--brand-dark);">
                                            <?= htmlspecialchars($pinjam['Judul']); ?>
                                        </div>
                                        <div class="text-[10px] font-medium clamp-1" style="color: var(--brand);">
                                            <?= htmlspecialchars($pinjam['Penulis']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Chip kategori -->
                                <div class="absolute top-1.5 left-1.5 max-w-[calc(100%-6rem)]">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-[0.08em] clamp-1"
                                          style="background: rgba(255,255,255,.94); color: var(--ink-2);">
                                        <?= htmlspecialchars($pinjam['Kategori'] ?: 'Umum'); ?>
                                    </span>
                                </div>

                                <!-- Badge sisa hari -->
                                <div class="absolute top-1.5 right-1.5">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold tnum"
                                          style="<?= $badge_style; ?>">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <?= $badge_label; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="p-3 flex-1 flex flex-col">
                                <div class="book-title text-[13px] font-semibold leading-snug clamp-2 mb-0.5 transition-colors" style="color: var(--ink);">
                                    <?= htmlspecialchars($pinjam['Judul']); ?>
                                </div>
                                <div class="text-[11px] clamp-1 mb-3" style="color: var(--muted);">
                                    <?= htmlspecialchars($pinjam['Penulis']); ?>
                                </div>

                                <!-- Meta tanggal -->
                                <div class="text-[11px] space-y-1 mb-3 pb-3 border-b" style="border-color: var(--line-soft);">
                                    <div class="flex items-center justify-between gap-2">
                                        <span style="color: var(--muted);">Dipinjam</span>
                                        <span class="font-medium tnum" style="color: var(--ink-2);">
                                            <?= date('d M Y', strtotime($pinjam['TanggalPeminjaman'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span style="color: var(--muted);">Jatuh tempo</span>
                                        <span class="font-medium tnum" style="color: var(--ink-2);">
                                            <?= date('d M Y', strtotime($pinjam['TanggalKembaliEstimasi'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Aksi -->
                                <a href="pengembalian.php?id=<?= (int)$pinjam['PeminjamanID']; ?>"
                                   class="btn btn-ghost mt-auto w-full">
                                    <i class="fas fa-circle-info text-[10px]"></i>
                                    <span>Lihat detail</span>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty state -->
                    <div class="card p-12 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                             style="background: var(--bg); color: var(--muted);">
                            <i class="fas fa-book-open text-[16px]"></i>
                        </div>
                        <div class="text-[14px] font-semibold mb-1" style="color: var(--ink);">
                            Belum ada buku yang dipinjam
                        </div>
                        <p class="text-[12px] mb-5 max-w-sm mx-auto" style="color: var(--muted);">
                            Jelajahi katalog perpustakaan dan mulai meminjam buku untuk mengisi koleksi pribadi Anda.
                        </p>
                        <a href="cari_buku.php" class="btn btn-primary inline-flex">
                            <i class="fas fa-magnifying-glass text-[11px]"></i>
                            <span>Jelajahi katalog</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Footer kecil -->
                <div class="pt-2 pb-6 text-center text-[11px]" style="color: var(--muted);">
                    Digital Library &middot; Sistem Peminjaman Buku
                </div>
            </div>
        </div>
    </main>
</body>
</html>