<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

// ===================== Filter =====================
$search   = trim($_GET['search']   ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$status   = $_GET['status']  ?? 'tersedia'; // tersedia | habis | semua
$urutkan  = $_GET['urutkan'] ?? 'terbaru';  // terbaru | judul | stok
$view     = $_GET['view']    ?? 'grid';     // grid | list

$search_esc   = mysqli_real_escape_string($koneksi, $search);
$kategori_esc = mysqli_real_escape_string($koneksi, $kategori);

// ===================== WHERE =====================
$where = "WHERE 1=1";
if ($search !== '') {
    $where .= " AND (Judul LIKE '%$search_esc%'
                     OR Penulis LIKE '%$search_esc%'
                     OR Penerbit LIKE '%$search_esc%'
                     OR ISBN LIKE '%$search_esc%')";
}
if ($kategori !== '' && $kategori !== 'Semua Kategori') {
    $where .= " AND Kategori = '$kategori_esc'";
}
if ($status === 'tersedia')     $where .= " AND Stok > 0";
elseif ($status === 'habis')    $where .= " AND Stok = 0";

// ===================== ORDER =====================
switch ($urutkan) {
    case 'judul': $order = "ORDER BY Judul ASC"; break;
    case 'stok':  $order = "ORDER BY Stok DESC, Judul ASC"; break;
    default:      $order = "ORDER BY BukuID DESC";
}

// ===================== Kategori dinamis =====================
$kat_q = mysqli_query($koneksi,
    "SELECT Kategori, COUNT(*) AS jml FROM buku
     WHERE Kategori IS NOT NULL AND Kategori != ''
     GROUP BY Kategori ORDER BY Kategori ASC");
$kategori_list = [];
if ($kat_q) while ($r = mysqli_fetch_assoc($kat_q)) $kategori_list[] = $r;

// ===================== Total =====================
$count_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM buku $where");
$total_hasil = (int)(mysqli_fetch_assoc($count_q)['total'] ?? 0);

// ===================== Data buku =====================
$result = mysqli_query($koneksi, "SELECT * FROM buku $where $order LIMIT 24");

// ===================== Kuota user =====================
$uid = (int)$_SESSION['UserID'];
$kuota_q = mysqli_query($koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman
     WHERE UserID = $uid AND TanggalPengembalian IS NULL");
$buku_dipinjam = (int)(mysqli_fetch_assoc($kuota_q)['total'] ?? 0);
$sisa_kuota = max(0, 3 - $buku_dipinjam);
$kuota_penuh = $sisa_kuota <= 0;

// Helper untuk query string
function qs($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari & Pinjam — Digital Library</title>
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

        .tnum { font-variant-numeric: tabular-nums; }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .card-book { cursor: pointer; }
        .card-book:hover {
            border-color: var(--brand);
            box-shadow: 0 1px 2px rgba(9,30,66,.08);
        }
        .card-book:hover .book-title { color: var(--brand); }

        /* Clamp */
        .clamp-1 { display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }

        /* Input / Select — bentuk seragam */
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

        /* Tombol primer */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            padding: 8px 14px;
            transition: background .15s ease;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-ghost {
            background: transparent; color: var(--ink-2);
            border: 1px solid var(--line);
        }
        .btn-ghost:hover { background: var(--bg); }

        /* Segmented control (grid/list) */
        .segment {
            display: inline-flex;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .segment a {
            padding: 8px 12px;
            font-size: 12px;
            color: var(--muted);
            transition: background .15s ease, color .15s ease;
        }
        .segment a:hover { background: var(--bg); }
        .segment a.active {
            background: var(--brand);
            color: #fff;
        }

        /* Radio/checkbox */
        input[type="radio"], input[type="checkbox"] { accent-color: var(--brand); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* Fokus */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* List view */
        .view-list #booksGrid { grid-template-columns: 1fr !important; }
        .view-list .book-item {
            display: flex;
            flex-direction: row;
        }
        .view-list .book-item .cover-wrap {
            width: 110px;
            flex-shrink: 0;
            aspect-ratio: 3/4;
        }
        .view-list .book-item .info-wrap { flex: 1; }

        /* Modal backdrop */
        .modal-backdrop { background: rgba(9, 30, 66, .54); }
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
            <a href="cari_buku.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-white" style="background: var(--brand);">
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
                <span style="color: var(--muted);">Katalog</span>
                <span style="color: var(--line);">/</span>
                <span class="font-medium" style="color: var(--ink);">Cari & Pinjam</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-md border text-[11px] font-medium"
                     style="<?= $kuota_penuh
                        ? 'background:#FFEBE6; border-color:#FFBDAD; color:#BF2600;'
                        : 'background:#E3FCEF; border-color:#ABF5D1; color:#006644;'; ?>">
                    <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $kuota_penuh ? '#BF2600' : '#00875A'; ?>;"></span>
                    <span class="tnum">Kuota <?= $sisa_kuota; ?>/3</span>
                </div>
                <button type="button" onclick="toggleKeranjang()"
                        class="relative flex items-center gap-2 px-3 py-1.5 rounded-md border text-[12px] font-medium transition hover:bg-[#F4F5F7]"
                        style="border-color: var(--line); color: var(--ink-2);">
                    <i class="fas fa-basket-shopping text-[11px]"></i>
                    <span>Keranjang</span>
                    <span id="cart-count"
                          class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-semibold tnum"
                          style="background: var(--brand); color: #fff;">0</span>
                </button>
            </div>
        </header>

        <!-- Scroll container -->
        <div class="flex-1 overflow-y-auto min-h-0">

            <!-- Header halaman -->
            <div class="px-6 pt-6 pb-4">
                <h1 class="text-[20px] font-semibold tracking-tight leading-tight" style="color: var(--ink);">
                    Eksplorasi Katalog
                </h1>
                <p class="text-[13px] mt-1" style="color: var(--muted);">
                    Temukan buku berdasarkan judul, penulis, penerbit, atau ISBN.
                </p>
            </div>

            <!-- Search bar -->
            <form method="GET" id="filterForm" class="px-6">
                <input type="hidden" name="view"     value="<?= htmlspecialchars($view); ?>">
                <input type="hidden" name="status"   value="<?= htmlspecialchars($status); ?>">
                <input type="hidden" name="urutkan"  value="<?= htmlspecialchars($urutkan); ?>">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori); ?>">

                <div class="flex flex-col md:flex-row gap-2">
                    <div class="flex-1 relative">
                        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[12px]" style="color: var(--muted);"></i>
                        <input type="text" name="search"
                               value="<?= htmlspecialchars($search); ?>"
                               placeholder="Cari judul, penulis, penerbit, atau ISBN…"
                               class="field w-full pl-9 pr-3 py-2">
                    </div>

                    <select name="urutkan" onchange="document.getElementById('filterForm').submit()"
                            class="field px-3 py-2 cursor-pointer min-w-[160px]">
                        <option value="terbaru" <?= $urutkan==='terbaru'?'selected':''; ?>>Terbaru</option>
                        <option value="judul"   <?= $urutkan==='judul'  ?'selected':''; ?>>Judul A–Z</option>
                        <option value="stok"    <?= $urutkan==='stok'   ?'selected':''; ?>>Stok terbanyak</option>
                    </select>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-magnifying-glass text-[11px]"></i>
                        <span>Cari</span>
                    </button>

                    <div class="segment">
                        <a href="?<?= qs(['view'=>'grid']); ?>"
                           class="<?= $view==='grid' ? 'active' : ''; ?>"
                           title="Tampilan grid">
                            <i class="fas fa-grip text-[11px]"></i>
                        </a>
                        <a href="?<?= qs(['view'=>'list']); ?>"
                           class="<?= $view==='list' ? 'active' : ''; ?>"
                           title="Tampilan daftar">
                            <i class="fas fa-list text-[11px]"></i>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Body: filter + grid -->
            <div class="px-6 py-5 flex flex-col lg:flex-row gap-5 pb-24">

                <!-- Sidebar filter -->
                <aside class="w-full lg:w-56 flex-shrink-0">
                    <form method="GET" id="sideFilter" class="card p-4 lg:sticky lg:top-4">
                        <input type="hidden" name="search"  value="<?= htmlspecialchars($search); ?>">
                        <input type="hidden" name="urutkan" value="<?= htmlspecialchars($urutkan); ?>">
                        <input type="hidden" name="view"    value="<?= htmlspecialchars($view); ?>">

                        <div class="flex items-center justify-between mb-4 pb-3 border-b" style="border-color: var(--line-soft);">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.14em]" style="color: var(--muted);">
                                Filter
                            </span>
                            <a href="cari_buku.php" class="text-[11px] font-medium transition hover:underline" style="color: var(--brand);">
                                Reset
                            </a>
                        </div>

                        <!-- Status -->
                        <div class="mb-5">
                            <div class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-2" style="color: var(--muted);">
                                Status
                            </div>
                            <div class="space-y-1.5">
                                <?php foreach ([
                                    'tersedia' => 'Tersedia',
                                    'habis'    => 'Stok habis',
                                    'semua'    => 'Semua',
                                ] as $val => $label): ?>
                                    <label class="flex items-center gap-2 cursor-pointer py-0.5">
                                        <input type="radio" name="status" value="<?= $val; ?>"
                                               <?= $status===$val ? 'checked' : ''; ?>
                                               onchange="document.getElementById('sideFilter').submit()"
                                               class="w-3.5 h-3.5">
                                        <span class="text-[12px]" style="color: var(--ink-2);"><?= $label; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Kategori -->
                        <div class="mb-5">
                            <div class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-2" style="color: var(--muted);">
                                Kategori
                            </div>
                            <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
                                <label class="flex items-center justify-between gap-2 cursor-pointer py-0.5">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <input type="radio" name="kategori" value=""
                                               <?= $kategori===''?'checked':''; ?>
                                               onchange="document.getElementById('sideFilter').submit()"
                                               class="w-3.5 h-3.5 flex-shrink-0">
                                        <span class="text-[12px] truncate <?= $kategori==='' ? 'font-semibold' : ''; ?>"
                                              style="color: <?= $kategori==='' ? 'var(--brand)' : 'var(--ink-2)'; ?>;">
                                            Semua
                                        </span>
                                    </span>
                                </label>
                                <?php foreach ($kategori_list as $k): ?>
                                    <?php $active = $kategori === $k['Kategori']; ?>
                                    <label class="flex items-center justify-between gap-2 cursor-pointer py-0.5">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <input type="radio" name="kategori"
                                                   value="<?= htmlspecialchars($k['Kategori']); ?>"
                                                   <?= $active ? 'checked' : ''; ?>
                                                   onchange="document.getElementById('sideFilter').submit()"
                                                   class="w-3.5 h-3.5 flex-shrink-0">
                                            <span class="text-[12px] truncate <?= $active ? 'font-semibold' : ''; ?>"
                                                  title="<?= htmlspecialchars($k['Kategori']); ?>"
                                                  style="color: <?= $active ? 'var(--brand)' : 'var(--ink-2)'; ?>;">
                                                <?= htmlspecialchars($k['Kategori']); ?>
                                            </span>
                                        </span>
                                        <span class="text-[10px] tnum flex-shrink-0" style="color: var(--muted);">
                                            <?= $k['jml']; ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            Terapkan Filter
                        </button>
                    </form>
                </aside>

                <!-- Grid -->
                <section class="flex-1 min-w-0">

                    <!-- Info bar -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="text-[12px]" style="color: var(--muted);">
                            <span class="tnum font-semibold" style="color: var(--ink);"><?= $total_hasil; ?></span>
                            judul ditemukan
                            <?php if ($kategori): ?>
                                · kategori <span class="font-medium" style="color: var(--ink-2);"><?= htmlspecialchars($kategori); ?></span>
                            <?php endif; ?>
                            <?php if ($search): ?>
                                · kata kunci <span class="font-medium" style="color: var(--ink-2);">“<?= htmlspecialchars($search); ?>”</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($search || $kategori || $status !== 'tersedia'): ?>
                            <a href="cari_buku.php" class="text-[11px] font-medium inline-flex items-center gap-1 transition hover:underline" style="color: var(--brand);">
                                <i class="fas fa-xmark text-[10px]"></i> Bersihkan filter
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Grid container -->
                    <div id="booksGrid"
                         class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 <?= $view==='list' ? 'view-list' : ''; ?>">

                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($buku = mysqli_fetch_assoc($result)):
                                $has_cover = !empty($buku['Cover']) && $buku['Cover'] !== 'default.jpg';
                                $cover = $has_cover ? 'uploads/' . $buku['Cover'] : null;
                                $tersedia = (int)$buku['Stok'] > 0;
                            ?>
                            <div class="book-item card card-book overflow-hidden flex flex-col"
                                 onclick="bukaModal(<?= htmlspecialchars(json_encode([
                                    'id'       => (int)$buku['BukuID'],
                                    'judul'    => $buku['Judul'],
                                    'penulis'  => $buku['Penulis'],
                                    'penerbit' => $buku['Penerbit'],
                                    'kategori' => $buku['Kategori'],
                                    'tahun'    => $buku['TahunTerbit'],
                                    'stok'     => (int)$buku['Stok'],
                                    'cover'    => $cover
                                 ]), ENT_QUOTES); ?>)">

                                <!-- Cover -->
                                <div class="cover-wrap aspect-[3/4] relative overflow-hidden" style="background: var(--bg);">
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

                                    <!-- Kategori chip -->
                                    <div class="absolute top-1.5 left-1.5 max-w-[calc(100%-3rem)]">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-[0.08em] clamp-1"
                                              style="background: rgba(255,255,255,.94); color: var(--ink-2);">
                                            <?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?>
                                        </span>
                                    </div>

                                    <!-- Stok badge -->
                                    <div class="absolute top-1.5 right-1.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tnum"
                                              style="<?= $tersedia
                                                ? 'background:#E3FCEF; color:#006644;'
                                                : 'background:#FFEBE6; color:#BF2600;'; ?>">
                                            <?= $tersedia ? $buku['Stok'] . ' stok' : 'Habis'; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="info-wrap p-2.5 flex-1 flex flex-col">
                                    <div class="book-title text-[12px] font-semibold leading-snug clamp-2 mb-0.5 transition-colors" style="color: var(--ink);">
                                        <?= htmlspecialchars($buku['Judul']); ?>
                                    </div>
                                    <div class="text-[11px] clamp-1 mb-2" style="color: var(--muted);">
                                        <?= htmlspecialchars($buku['Penulis']); ?>
                                    </div>

                                    <!-- Aksi cepat -->
                                    <button type="button"
                                            onclick="event.stopPropagation(); tambahKeKeranjang(<?= htmlspecialchars(json_encode([
                                                'id'      => (int)$buku['BukuID'],
                                                'judul'   => $buku['Judul'],
                                                'penulis' => $buku['Penulis'],
                                                'cover'   => $cover
                                            ]), ENT_QUOTES); ?>)"
                                            <?= (!$tersedia || $kuota_penuh) ? 'disabled' : ''; ?>
                                            class="mt-auto w-full py-1.5 rounded-md text-[11px] font-semibold transition flex items-center justify-center gap-1.5
                                                   <?= !$tersedia ? '' : ($kuota_penuh ? '' : ''); ?>"
                                            style="<?php
                                                if (!$tersedia) {
                                                    echo 'background: #FFEBE6; color: #BF2600; cursor: not-allowed;';
                                                } elseif ($kuota_penuh) {
                                                    echo 'background: #FFEBE6; color: #BF2600; cursor: not-allowed;';
                                                } else {
                                                    echo 'background: var(--brand); color: #fff;';
                                                }
                                            ?>"
                                            <?php if ($tersedia && !$kuota_penuh): ?>
                                                onmouseover="this.style.background='var(--brand-dark)'"
                                                onmouseout="this.style.background='var(--brand)'"
                                            <?php endif; ?>>
                                        <?php if (!$tersedia): ?>
                                            <i class="fas fa-ban text-[10px]"></i> Stok habis
                                        <?php elseif ($kuota_penuh): ?>
                                            <i class="fas fa-lock text-[10px]"></i> Kuota penuh
                                        <?php else: ?>
                                            <i class="fas fa-plus text-[10px]"></i> Keranjang
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-full card p-12 text-center">
                                <i class="fas fa-magnifying-glass text-2xl mb-3" style="color: var(--line);"></i>
                                <div class="text-[14px] font-semibold mb-1" style="color: var(--ink);">
                                    Tidak ada buku ditemukan
                                </div>
                                <p class="text-[12px] mb-4" style="color: var(--muted);">
                                    Coba ubah kata kunci atau bersihkan filter.
                                </p>
                                <a href="cari_buku.php" class="btn btn-primary inline-flex">
                                    <i class="fas fa-rotate-left text-[11px]"></i> Reset filter
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <!-- ============================ KERANJANG ============================ -->
        <div id="keranjang-panel"
             class="hidden absolute bottom-6 right-6 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-lg border z-40 flex flex-col"
             style="border-color: var(--line); box-shadow: 0 8px 16px -4px rgba(9,30,66,.12), 0 0 1px rgba(9,30,66,.15);">

            <div class="flex items-center justify-between px-4 py-3 border-b" style="border-color: var(--line-soft);">
                <div class="flex items-center gap-2">
                    <i class="fas fa-basket-shopping text-[12px]" style="color: var(--brand);"></i>
                    <span class="text-[13px] font-semibold" style="color: var(--ink);">Keranjang</span>
                </div>
                <button type="button" onclick="toggleKeranjang()"
                        class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="color: var(--muted);">
                    <i class="fas fa-xmark text-[12px]"></i>
                </button>
            </div>

            <div class="p-4">
                <div class="text-[11px] mb-3" style="color: var(--muted);">
                    <span id="cart-item-count" class="font-semibold tnum" style="color: var(--ink);">0</span>
                    buku siap dipinjam &middot; maks 3
                </div>

                <div id="cart-items" class="space-y-1.5 max-h-64 overflow-y-auto pr-0.5"></div>

                <form action="proses_pinjam.php" method="POST" class="mt-3">
                    <div id="hidden-inputs"></div>
                    <button type="submit" id="btn-lanjut-pinjam" disabled
                            class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                        Lanjut Konfirmasi
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- ============================ MODAL DETAIL ============================ -->
    <div id="modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 modal-backdrop">
        <div class="bg-white w-full max-w-md rounded-lg overflow-hidden border"
             style="border-color: var(--line); box-shadow: 0 12px 24px -6px rgba(9,30,66,.18);">

            <div class="flex items-center justify-between px-5 py-3 border-b" style="border-color: var(--line-soft);">
                <span class="text-[11px] font-semibold uppercase tracking-[0.14em]" style="color: var(--muted);">
                    Detail Buku
                </span>
                <button type="button" onclick="tutupModal()"
                        class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="color: var(--muted);">
                    <i class="fas fa-xmark text-[12px]"></i>
                </button>
            </div>

            <div class="p-5">
                <div class="flex gap-4">
                    <div class="w-24 aspect-[3/4] rounded-md overflow-hidden flex-shrink-0 border"
                         style="border-color: var(--line); background: var(--bg-soft);">
                        <img id="m-cover" src="" class="w-full h-full object-cover hidden" alt="">
                        <div id="m-cover-placeholder" class="w-full h-full flex items-center justify-center"
                             style="background: var(--brand-light); color: var(--brand);">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div id="m-kategori" class="text-[10px] font-semibold uppercase tracking-[0.14em] mb-1" style="color: var(--brand);">
                            —
                        </div>
                        <div id="m-judul" class="text-[14px] font-semibold leading-snug" style="color: var(--ink);">—</div>
                        <div id="m-penulis" class="text-[12px] mt-0.5" style="color: var(--muted);">—</div>

                        <div class="mt-3 pt-3 border-t space-y-1.5 text-[11.5px]" style="border-color: var(--line-soft);">
                            <div class="flex items-start justify-between gap-2">
                                <span style="color: var(--muted);">Penerbit</span>
                                <span id="m-penerbit" class="text-right font-medium clamp-1" style="color: var(--ink-2);">—</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span style="color: var(--muted);">Tahun</span>
                                <span id="m-tahun" class="font-medium tnum" style="color: var(--ink-2);">—</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span style="color: var(--muted);">Stok</span>
                                <span id="m-stok" class="font-medium tnum" style="color: var(--ink-2);">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 px-3 py-2 rounded-md text-[11px]" style="background: var(--bg-soft); color: var(--muted);">
                    <i class="fas fa-circle-info mr-1 text-[10px]"></i>
                    Durasi <strong style="color: var(--ink-2);">14 hari</strong>,
                    maks <strong style="color: var(--ink-2);">3 buku</strong> per akun.
                    Sisa kuota Anda <strong class="tnum" style="color: var(--ink-2);"><?= $sisa_kuota; ?></strong>.
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t" style="background: var(--bg-soft); border-color: var(--line-soft);">
                <button type="button" onclick="tutupModal()" class="btn btn-ghost">Batal</button>
                <button type="button" id="m-btn-add" class="btn btn-primary">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>Tambah ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    const KUOTA_PENUH = <?= $kuota_penuh ? 'true' : 'false'; ?>;
    const SISA_KUOTA  = <?= $sisa_kuota; ?>;
    let keranjang = [];
    let currentBook = null;

    /* ---------- MODAL ---------- */
    function bukaModal(data) {
        currentBook = data;

        document.getElementById('m-judul').textContent    = data.judul;
        document.getElementById('m-penulis').textContent  = 'oleh ' + data.penulis;
        document.getElementById('m-kategori').textContent = data.kategori || 'Umum';
        document.getElementById('m-penerbit').textContent = data.penerbit || '—';
        document.getElementById('m-tahun').textContent    = data.tahun || '—';
        document.getElementById('m-stok').textContent     = data.stok + ' eksemplar';

        const img = document.getElementById('m-cover');
        const ph  = document.getElementById('m-cover-placeholder');
        if (data.cover) {
            img.src = data.cover;
            img.classList.remove('hidden');
            ph.classList.add('hidden');
            img.onerror = () => { img.classList.add('hidden'); ph.classList.remove('hidden'); };
        } else {
            img.classList.add('hidden');
            ph.classList.remove('hidden');
        }

        const btn = document.getElementById('m-btn-add');
        const sudahAda = keranjang.some(b => b.bukuID === data.id);
        const span = btn.querySelector('span');

        if (data.stok <= 0) {
            btn.disabled = true;
            span.textContent = 'Stok habis';
            btn.querySelector('i').className = 'fas fa-ban text-[10px]';
        } else if (KUOTA_PENUH) {
            btn.disabled = true;
            span.textContent = 'Kuota penuh';
            btn.querySelector('i').className = 'fas fa-lock text-[10px]';
        } else if (sudahAda) {
            btn.disabled = true;
            span.textContent = 'Sudah di keranjang';
            btn.querySelector('i').className = 'fas fa-check text-[10px]';
        } else {
            btn.disabled = false;
            span.textContent = 'Tambah ke Keranjang';
            btn.querySelector('i').className = 'fas fa-plus text-[10px]';
        }

        const modal = document.getElementById('modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function tutupModal() {
        const modal = document.getElementById('modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.getElementById('modal').addEventListener('click', e => {
        if (e.target.id === 'modal') tutupModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupModal();
    });

    document.getElementById('m-btn-add').addEventListener('click', () => {
        if (!currentBook) return;
        tambahKeKeranjang({
            id: currentBook.id,
            judul: currentBook.judul,
            penulis: currentBook.penulis,
            cover: currentBook.cover
        });
        tutupModal();
    });

    /* ---------- KERANJANG ---------- */
    function tambahKeKeranjang(data) {
        if (KUOTA_PENUH) { alert('Kuota penuh, maksimal 3 buku.'); return; }
        if (keranjang.length >= SISA_KUOTA) { alert('Sisa kuota Anda ' + SISA_KUOTA + ' buku.'); return; }
        if (keranjang.some(b => b.bukuID === data.id)) { alert('Buku sudah ada di keranjang.'); return; }

        keranjang.push({
            bukuID: data.id,
            judul: data.judul,
            penulis: data.penulis,
            gambar: data.cover || ''
        });
        updateUIKeranjang();

        const panel = document.getElementById('keranjang-panel');
        if (panel.classList.contains('hidden')) toggleKeranjang();
    }

    function hapusDariKeranjang(index) {
        keranjang.splice(index, 1);
        updateUIKeranjang();
    }

    function updateUIKeranjang() {
        const container = document.getElementById('cart-items');
        const hiddenInputs = document.getElementById('hidden-inputs');
        container.innerHTML = '';
        hiddenInputs.innerHTML = '';

        document.getElementById('cart-count').textContent = keranjang.length;
        document.getElementById('cart-item-count').textContent = keranjang.length;

        const btnSubmit = document.getElementById('btn-lanjut-pinjam');
        btnSubmit.disabled = keranjang.length === 0;

        if (keranjang.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6 text-[11px]" style="color: var(--muted);">
                    Keranjang masih kosong
                </div>`;
        }

        keranjang.forEach((buku, index) => {
            const coverHtml = buku.gambar
                ? `<img src="${buku.gambar}" class="w-9 h-12 object-cover rounded border" style="border-color: var(--line); background: var(--bg);" onerror="this.style.display='none'">`
                : `<div class="w-9 h-12 rounded border flex items-center justify-center" style="border-color: var(--line); background: var(--brand-light); color: var(--brand);"><i class="fas fa-book text-[10px]"></i></div>`;

            container.innerHTML += `
                <div class="flex items-center justify-between gap-2 p-2 rounded-md border" style="border-color: var(--line-soft);">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        ${coverHtml}
                        <div class="min-w-0 flex-1">
                            <div class="text-[12px] font-semibold truncate" style="color: var(--ink);">${buku.judul}</div>
                            <div class="text-[10px] truncate" style="color: var(--muted);">${buku.penulis}</div>
                        </div>
                    </div>
                    <button type="button" onclick="hapusDariKeranjang(${index})"
                            class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#FFEBE6] flex-shrink-0"
                            style="color: #BF2600;">
                        <i class="fas fa-xmark text-[11px]"></i>
                    </button>
                </div>
            `;
            hiddenInputs.innerHTML += `<input type="hidden" name="buku_dipinjam[]" value="${buku.bukuID}">`;
        });
    }

    function toggleKeranjang() {
        document.getElementById('keranjang-panel').classList.toggle('hidden');
    }

    updateUIKeranjang();
    </script>
</body>
</html>