<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$user_id = (int)$_SESSION['UserID'];

// Tab
$tab = $_GET['tab'] ?? 'dipinjam';
if (!in_array($tab, ['dipinjam', 'dikembalikan'])) $tab = 'dipinjam';

$where = $tab === 'dipinjam'
    ? "AND peminjaman.TanggalPengembalian IS NULL"
    : "AND peminjaman.TanggalPengembalian IS NOT NULL";

$query = "SELECT peminjaman.*, buku.Judul, buku.Penulis, buku.Kategori,
                 ulasan.UlasanID, ulasan.Rating, ulasan.Komentar, ulasan.TanggalUlasan
          FROM peminjaman
          JOIN buku ON peminjaman.BukuID = buku.BukuID
          LEFT JOIN ulasan ON ulasan.PeminjamanID = peminjaman.PeminjamanID
          WHERE peminjaman.UserID = $user_id $where
          ORDER BY peminjaman.TanggalPeminjaman DESC";
$result = mysqli_query($koneksi, $query);

// Counter tab
$c1 = (int)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS t FROM peminjaman WHERE UserID = $user_id AND TanggalPengembalian IS NULL"))['t'] ?? 0);
$c2 = (int)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS t FROM peminjaman WHERE UserID = $user_id AND TanggalPengembalian IS NOT NULL"))['t'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian & Ulasan — Digital Library</title>
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

            --warning: #7A4E00;
            --warning-bg: #FFF0B3;
            --warning-line: #FFE380;

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
        }

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
            white-space: nowrap;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-ghost {
            background: #fff; color: var(--ink-2);
            border: 1px solid var(--line);
        }
        .btn-ghost:hover { background: var(--bg); border-color: var(--ink-2); color: var(--ink); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #005238; }

        .btn:disabled { cursor: not-allowed; opacity: .55; }

        /* Segmented tabs */
        .tabs {
            display: inline-flex;
            padding: 3px;
            background: var(--bg);
            border-radius: 8px;
            border: 1px solid var(--line);
        }
        .tabs a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            transition: background .15s ease, color .15s ease;
        }
        .tabs a:hover { color: var(--ink-2); }
        .tabs a.active {
            background: #fff;
            color: var(--ink);
            box-shadow: 0 1px 2px rgba(9,30,66,.06);
        }
        .tabs a .count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            font-size: 10px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            background: var(--line-soft);
            color: var(--muted);
        }
        .tabs a.active .count {
            background: var(--brand-light);
            color: var(--brand);
        }

        /* Tabel */
        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th {
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--bg-soft);
            padding: 10px 16px;
            border-bottom: 1px solid var(--line);
        }
        td {
            padding: 12px 16px;
            font-size: 12.5px;
            color: var(--ink-2);
            border-bottom: 1px solid var(--line-soft);
            vertical-align: middle;
        }
        tbody tr:hover td { background: var(--bg-soft); }
        tbody tr:last-child td { border-bottom: none; }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        /* Clamp */
        .clamp-1 { display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

        /* Star rating (interactive) */
        .stars { display: inline-flex; flex-direction: row-reverse; justify-content: flex-end; gap: 2px; }
        .stars input { display: none; }
        .stars label {
            cursor: pointer;
            font-size: 26px;
            line-height: 1;
            color: var(--line);
            transition: color .15s ease, transform .15s ease;
            padding: 0 1px;
        }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label { color: #FFAB00; }
        .stars label:hover { transform: scale(1.06); }

        /* Modal */
        .modal-backdrop { background: rgba(9, 30, 66, .54); }
        .modal {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 12px 24px -6px rgba(9,30,66,.18);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* Focus */
        a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Input */
        .field {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            color: var(--ink);
            font-size: 13px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 2px rgba(0,82,204,.14);
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
            <a href="koleksi.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition hover:bg-[#F4F5F7]" style="color: var(--ink-2);">
                <i class="fas fa-bookmark w-4 text-center text-[12px]"></i>
                <span>Koleksi Pribadi</span>
            </a>
            <a href="pengembalian.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-white" style="background: var(--brand);">
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
                <span style="color: var(--muted);">Riwayat</span>
                <span style="color: var(--line);">/</span>
                <span class="font-medium" style="color: var(--ink);">Pengembalian & Ulasan</span>
            </div>

            <button type="button" onclick="location.reload()"
                    class="w-8 h-8 rounded-md border flex items-center justify-center transition hover:bg-[#F4F5F7]"
                    style="border-color: var(--line); color: var(--muted);"
                    title="Muat ulang">
                <i class="fas fa-rotate-right text-[12px]"></i>
            </button>
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
                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 text-[13px] rounded-md border"
                         style="background: var(--danger-bg); border-color: var(--danger-line); color: var(--danger);">
                        <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['error']); ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Header halaman -->
                <div>
                    <h1 class="text-[20px] font-semibold tracking-tight leading-tight" style="color: var(--ink);">
                        Riwayat Peminjaman
                    </h1>
                    <p class="text-[13px] mt-1" style="color: var(--muted);">
                        Lihat status peminjaman Anda dan berikan ulasan untuk buku yang telah dikembalikan.
                    </p>
                </div>

                <!-- Tab -->
                <div class="tabs">
                    <a href="?tab=dipinjam" class="<?= $tab==='dipinjam' ? 'active' : ''; ?>">
                        <i class="fas fa-book text-[11px]"></i>
                        <span>Sedang Dipinjam</span>
                        <span class="count"><?= $c1; ?></span>
                    </a>
                    <a href="?tab=dikembalikan" class="<?= $tab==='dikembalikan' ? 'active' : ''; ?>">
                        <i class="fas fa-circle-check text-[11px]"></i>
                        <span>Sudah Dikembalikan</span>
                        <span class="count"><?= $c2; ?></span>
                    </a>
                </div>

                <!-- Tabel -->
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th class="w-12">No</th>
                                    <th>Judul Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th><?= $tab==='dipinjam' ? 'Jatuh Tempo' : 'Tanggal Kembali'; ?></th>
                                    <th class="w-40">Status</th>
                                    <th class="w-48 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0):
                                $no = 1;
                                while ($pinjam = mysqli_fetch_assoc($result)):
                                    $is_dikembalikan = !is_null($pinjam['TanggalPengembalian']);
                                    $sudah_diulas    = !is_null($pinjam['UlasanID']);

                                    $tgl_kembali = strtotime($pinjam['TanggalKembaliEstimasi']);
                                    $hari_ini    = strtotime(date('Y-m-d'));
                                    $telat       = ($tgl_kembali < $hari_ini);

                                    // Badge status
                                    if ($is_dikembalikan) {
                                        $badge_bg = 'var(--success-bg)';
                                        $badge_fg = 'var(--success)';
                                        $badge_border = 'var(--success-line)';
                                        $badge_text = 'Dikembalikan';
                                        $badge_icon = 'fa-circle-check';
                                    } elseif (($pinjam['Status'] ?? '') === 'Menunggu Konfirmasi') {
                                        $badge_bg = 'var(--warning-bg)';
                                        $badge_fg = 'var(--warning)';
                                        $badge_border = 'var(--warning-line)';
                                        $badge_text = 'Menunggu Konfirmasi';
                                        $badge_icon = 'fa-hourglass-half';
                                    } elseif ($telat) {
                                        $badge_bg = 'var(--danger-bg)';
                                        $badge_fg = 'var(--danger)';
                                        $badge_border = 'var(--danger-line)';
                                        $badge_text = 'Terlambat';
                                        $badge_icon = 'fa-triangle-exclamation';
                                    } else {
                                        $badge_bg = 'var(--brand-light)';
                                        $badge_fg = 'var(--brand)';
                                        $badge_border = 'transparent';
                                        $badge_text = 'Dipinjam';
                                        $badge_icon = 'fa-book';
                                    }
                            ?>
                                <tr>
                                    <td class="tnum" style="color: var(--muted);"><?= $no++; ?></td>

                                    <!-- Judul + penulis + kategori -->
                                    <td>
                                        <div class="text-[13px] font-semibold clamp-1" style="color: var(--ink);">
                                            <?= htmlspecialchars($pinjam['Judul']); ?>
                                        </div>
                                        <div class="text-[11px] mt-0.5 clamp-1" style="color: var(--muted);">
                                            <?= htmlspecialchars($pinjam['Penulis']); ?>
                                            <span style="color: var(--line);"> · </span>
                                            <?= htmlspecialchars($pinjam['Kategori']); ?>
                                        </div>
                                    </td>

                                    <td class="tnum"><?= date('d M Y', strtotime($pinjam['TanggalPeminjaman'])); ?></td>

                                    <td class="tnum">
                                        <?= $is_dikembalikan
                                            ? date('d M Y', strtotime($pinjam['TanggalPengembalian']))
                                            : date('d M Y', strtotime($pinjam['TanggalKembaliEstimasi'])); ?>
                                    </td>

                                    <td>
                                        <span class="badge"
                                              style="background: <?= $badge_bg; ?>; color: <?= $badge_fg; ?>; border: 1px solid <?= $badge_border; ?>;">
                                            <i class="fas <?= $badge_icon; ?> text-[9px]"></i>
                                            <?= $badge_text; ?>
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td>
                                        <div class="flex items-center justify-center gap-2">
                                        <?php if ($is_dikembalikan): ?>
                                            <?php if ($sudah_diulas): ?>
                                                <button type="button"
                                                        onclick="lihatUlasan(<?= htmlspecialchars(json_encode([
                                                            'judul'    => $pinjam['Judul'],
                                                            'rating'   => (int)$pinjam['Rating'],
                                                            'komentar' => $pinjam['Komentar'],
                                                            'tanggal'  => $pinjam['TanggalUlasan']
                                                        ]), ENT_QUOTES); ?>)"
                                                        class="btn btn-ghost">
                                                    <i class="fas fa-star text-[10px]" style="color: #FFAB00;"></i>
                                                    <span>Lihat Ulasan</span>
                                                </button>
                                            <?php else: ?>
                                                <button type="button"
                                                        onclick="bukaUlasan(<?= (int)$pinjam['PeminjamanID']; ?>, '<?= htmlspecialchars(addslashes($pinjam['Judul'])); ?>')"
                                                        class="btn btn-primary">
                                                    <i class="fas fa-star text-[10px]"></i>
                                                    <span>Beri Ulasan</span>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (($pinjam['Status'] ?? '') === 'Menunggu Konfirmasi'): ?>
                                                <span class="text-[11px] font-medium" style="color: var(--warning);">
                                                    <i class="fas fa-hourglass-half mr-1 text-[10px]"></i>
                                                    Menunggu admin
                                                </span>
                                            <?php else: ?>
                                                <a href="ajukan_pengembalian.php?id=<?= (int)$pinjam['PeminjamanID']; ?>"
                                                   onclick="return confirm('Ajukan pengembalian buku ini? Pastikan Anda sudah membawanya ke perpustakaan.');"
                                                   class="btn btn-success">
                                                    <i class="fas fa-rotate-left text-[10px]"></i>
                                                    <span>Ajukan Pengembalian</span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="6" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                                             style="background: var(--bg); color: var(--muted);">
                                            <i class="fas fa-inbox text-[16px]"></i>
                                        </div>
                                        <div class="text-[14px] font-semibold mb-1" style="color: var(--ink);">
                                            <?= $tab==='dipinjam'
                                                ? 'Tidak ada buku yang sedang dipinjam'
                                                : 'Belum ada riwayat pengembalian'; ?>
                                        </div>
                                        <p class="text-[12px] mb-5 max-w-sm mx-auto" style="color: var(--muted);">
                                            <?= $tab==='dipinjam'
                                                ? 'Pinjam buku untuk memulai. Riwayat peminjaman akan muncul di sini.'
                                                : 'Setelah Anda mengembalikan buku, riwayatnya akan tampil di sini.'; ?>
                                        </p>
                                        <?php if ($tab==='dipinjam'): ?>
                                            <a href="cari_buku.php" class="btn btn-primary inline-flex">
                                                <i class="fas fa-magnifying-glass text-[11px]"></i>
                                                <span>Jelajahi katalog</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-2 pb-6 text-center text-[11px]" style="color: var(--muted);">
                    Digital Library &middot; Sistem Peminjaman Buku
                </div>
            </div>
        </div>
    </main>

    <!-- ============================ MODAL: BERI ULASAN ============================ -->
    <div id="modal-ulasan" class="hidden fixed inset-0 z-50 items-center justify-center p-4 modal-backdrop">
        <div class="modal">
            <div class="flex items-center justify-between px-5 py-3 border-b" style="border-color: var(--line-soft);">
                <div class="flex items-center gap-2">
                    <i class="fas fa-star text-[12px]" style="color: #FFAB00;"></i>
                    <span class="text-[13px] font-semibold" style="color: var(--ink);">Beri Ulasan</span>
                </div>
                <button type="button" onclick="tutupModal()"
                        class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="color: var(--muted);">
                    <i class="fas fa-xmark text-[12px]"></i>
                </button>
            </div>

            <form method="POST" action="proses_ulasan.php" class="p-5 space-y-4">
                <input type="hidden" name="peminjaman_id" id="u-peminjaman-id" value="">

                <!-- Judul buku -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-[0.14em] mb-1.5" style="color: var(--muted);">
                        Buku
                    </label>
                    <div class="px-3 py-2 rounded-md border text-[13px] font-medium"
                         style="background: var(--bg-soft); border-color: var(--line); color: var(--ink);"
                         id="u-judul">—</div>
                </div>

                <!-- Rating -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-[0.14em] mb-1.5" style="color: var(--muted);">
                        Rating <span style="color: var(--danger);">*</span>
                    </label>
                    <div class="stars">
                        <input type="radio" name="rating" id="s5" value="5" required>
                        <label for="s5" title="5 dari 5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="s4" value="4">
                        <label for="s4" title="4 dari 5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="s3" value="3">
                        <label for="s3" title="3 dari 5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="s2" value="2">
                        <label for="s2" title="2 dari 5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="s1" value="1">
                        <label for="s1" title="1 dari 5"><i class="fas fa-star"></i></label>
                    </div>
                    <p class="text-[11px] mt-1.5" style="color: var(--muted);">
                        Klik bintang untuk memberi penilaian.
                    </p>
                </div>

                <!-- Komentar -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-[0.14em] mb-1.5" style="color: var(--muted);">
                        Komentar <span class="normal-case font-normal" style="letter-spacing: 0; color: var(--muted);">(opsional)</span>
                    </label>
                    <textarea name="komentar" rows="3"
                              placeholder="Ceritakan pengalaman Anda membaca buku ini…"
                              class="field w-full px-3 py-2 resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" onclick="tutupModal()" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane text-[10px]"></i>
                        <span>Kirim Ulasan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================ MODAL: LIHAT ULASAN ============================ -->
    <div id="modal-lihat" class="hidden fixed inset-0 z-50 items-center justify-center p-4 modal-backdrop">
        <div class="modal">
            <div class="flex items-center justify-between px-5 py-3 border-b" style="border-color: var(--line-soft);">
                <div class="flex items-center gap-2">
                    <i class="fas fa-star text-[12px]" style="color: #FFAB00;"></i>
                    <span class="text-[13px] font-semibold" style="color: var(--ink);">Ulasan Anda</span>
                </div>
                <button type="button" onclick="tutupLihat()"
                        class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="color: var(--muted);">
                    <i class="fas fa-xmark text-[12px]"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-1" style="color: var(--muted);">
                        Buku
                    </div>
                    <div class="text-[13px] font-semibold" style="color: var(--ink);" id="v-judul">—</div>
                </div>

                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-1" style="color: var(--muted);">
                        Rating
                    </div>
                    <div id="v-stars" class="flex items-center gap-2">
                        <span style="color: #FFAB00; letter-spacing: 2px; font-size: 16px;">—</span>
                        <span id="v-rating-text" class="text-[12px] tnum" style="color: var(--muted);">—</span>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-1" style="color: var(--muted);">
                        Komentar
                    </div>
                    <div class="px-3 py-2.5 rounded-md border text-[12.5px] leading-relaxed"
                         style="background: var(--bg-soft); border-color: var(--line); color: var(--ink-2);"
                         id="v-komentar">—</div>
                </div>

                <div class="pt-2 border-t text-[11px] text-right" style="border-color: var(--line-soft); color: var(--muted);" id="v-tanggal">
                    —
                </div>
            </div>
        </div>
    </div>

    <script>
    /* ---------- Modal Beri Ulasan ---------- */
    function bukaUlasan(peminjamanId, judul) {
        document.getElementById('u-peminjaman-id').value = peminjamanId;
        document.getElementById('u-judul').textContent = judul;
        document.querySelectorAll('.stars input').forEach(i => i.checked = false);
        document.querySelector('textarea[name="komentar"]').value = '';
        const m = document.getElementById('modal-ulasan');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function tutupModal() {
        const m = document.getElementById('modal-ulasan');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    }

    /* ---------- Modal Lihat Ulasan ---------- */
    function lihatUlasan(data) {
        document.getElementById('v-judul').textContent = data.judul;
        const filled = '★'.repeat(data.rating);
        const empty  = '☆'.repeat(5 - data.rating);
        document.querySelector('#v-stars span').textContent = filled + empty;
        document.getElementById('v-rating-text').textContent = data.rating + ' / 5';
        document.getElementById('v-komentar').textContent = data.komentar && data.komentar.trim() !== ''
            ? data.komentar
            : 'Tidak ada komentar.';
        document.getElementById('v-tanggal').textContent = data.tanggal
            ? 'Diulas pada ' + data.tanggal
            : '';
        const m = document.getElementById('modal-lihat');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function tutupLihat() {
        const m = document.getElementById('modal-lihat');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    }

    /* ---------- Global ---------- */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { tutupModal(); tutupLihat(); }
    });
    document.getElementById('modal-ulasan').addEventListener('click', e => {
        if (e.target.id === 'modal-ulasan') tutupModal();
    });
    document.getElementById('modal-lihat').addEventListener('click', e => {
        if (e.target.id === 'modal-lihat') tutupLihat();
    });
    </script>
</body>
</html>