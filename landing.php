<?php
session_start();

if (isset($_SESSION['UserID'])) {
    if (($_SESSION['Role'] ?? '') === 'admin') {
        header("Location: admin_peminjaman.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

require 'koneksi.php';

// Statistik dari database
$total_buku   = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM buku"))['t'] ?? 0);
$total_stok   = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(Stok),0) AS t FROM buku"))['t'] ?? 0);
$total_user   = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM user"))['t'] ?? 0);
$total_pinjam = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM peminjaman"))['t'] ?? 0);

// Buku hero
$buku_hero = [];
$q = mysqli_query($koneksi, "SELECT BukuID, Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover FROM buku ORDER BY BukuID DESC LIMIT 3");
if ($q) while ($r = mysqli_fetch_assoc($q)) $buku_hero[] = $r;

// Buku showcase
$buku_showcase = [];
$q = mysqli_query($koneksi, "SELECT BukuID, Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover FROM buku ORDER BY BukuID DESC LIMIT 8");
if ($q) while ($r = mysqli_fetch_assoc($q)) $buku_showcase[] = $r;

// Fallback
if (empty($buku_hero)) {
    $buku_hero = [
        ['BukuID' => 1, 'Judul' => 'Atomic Habits', 'Penulis' => 'James Clear', 'Kategori' => 'Pengembangan Diri', 'Stok' => 5, 'Cover' => null, 'TahunTerbit' => 2018, 'Penerbit' => 'Gramedia'],
        ['BukuID' => 2, 'Judul' => 'Janji', 'Penulis' => 'Tere Liye', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 4, 'Cover' => null, 'TahunTerbit' => 2021, 'Penerbit' => 'Republika'],
        ['BukuID' => 3, 'Judul' => 'Filosofi Teras', 'Penulis' => 'Henry Manampiring', 'Kategori' => 'Filsafat', 'Stok' => 3, 'Cover' => null, 'TahunTerbit' => 2017, 'Penerbit' => 'Kompas'],
    ];
}
if (empty($buku_showcase)) $buku_showcase = $buku_hero;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library — Perpustakaan Digital</title>
    <meta name="description" content="Pinjam buku perpustakaan tanpa antre. Katalog lengkap, peminjaman cepat, pengembalian mudah.">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

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
            --bg: #FFFFFF;
            --bg-soft: #FAFBFC;
            --bg-section: #F4F5F7;
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
            overflow-x: hidden;
        }

        .serif {
            font-family: 'Instrument Serif', Georgia, serif;
            font-style: italic;
            font-weight: 400;
        }

        .tnum { font-variant-numeric: tabular-nums; }

        /* ===================== SCROLL REVEAL ===================== */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition:
                opacity .7s cubic-bezier(.2,.8,.2,1),
                transform .7s cubic-bezier(.2,.8,.2,1);
            will-change: opacity, transform;
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-d1 { transition-delay: .08s; }
        .reveal-d2 { transition-delay: .16s; }
        .reveal-d3 { transition-delay: .24s; }
        .reveal-d4 { transition-delay: .32s; }

        /* ===================== HERO ANIMASI ===================== */
        @keyframes heroFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .hero-item {
            opacity: 0;
            animation: heroFadeUp .8s cubic-bezier(.2,.8,.2,1) both;
        }
        .hero-1 { animation-delay: .10s; }
        .hero-2 { animation-delay: .22s; }
        .hero-3 { animation-delay: .34s; }
        .hero-4 { animation-delay: .46s; }

        /* Buku sedikit mengapung */
        @keyframes floatY {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        .float-a { animation: floatY 6s ease-in-out infinite; }
        .float-b { animation: floatY 6s ease-in-out infinite; animation-delay: -2s; }
        .float-c { animation: floatY 6s ease-in-out infinite; animation-delay: -4s; }

        /* Marquee */
        @keyframes marquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 40s linear infinite;
        }
        .marquee-track:hover { animation-play-state: paused; }

        /* Tombol utama */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 22px;
            border-radius: 8px;
            background: var(--brand);
            color: #fff;
            transition: background .18s ease, transform .12s ease, box-shadow .18s ease;
            box-shadow: 0 1px 2px rgba(9,30,66,.08);
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -6px rgba(0,82,204,.32);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 22px;
            border-radius: 8px;
            background: #fff;
            color: var(--ink-2);
            border: 1px solid var(--line);
            transition: border-color .18s ease, background .18s ease, color .18s ease;
        }
        .btn-ghost:hover {
            border-color: var(--ink-2);
            background: var(--bg-soft);
            color: var(--ink);
        }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            transition: border-color .2s ease, transform .3s cubic-bezier(.2,.8,.2,1), box-shadow .3s ease;
        }
        .card:hover {
            border-color: var(--brand);
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -12px rgba(9,30,66,.16);
        }

        /* Line clamp */
        .clamp-1 { display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 5px; border: 2px solid var(--bg); }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }

        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* Kurangi animasi kalau user prefer-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }
            .reveal, .hero-item {
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- ============================ HEADER ============================ -->
    <header id="siteHeader" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-transparent">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">

            <!-- Logo -->
            <a href="landing.php" class="flex items-center gap-2.5 group">
                <img src="logo/logo.png" alt="Digital Library" class="h-9 object-contain transition-transform group-hover:scale-105">
                <span class="text-[14px] font-semibold tracking-tight hidden sm:inline" style="color: var(--ink);">
                    Digital Library
                </span>
            </a>

            <!-- Nav -->
            <nav class="hidden md:flex items-center gap-7">
                <a href="#koleksi"  class="text-[13px] font-medium transition hover:opacity-70" style="color: var(--ink-2);">Katalog</a>
                <a href="#cara-kerja" class="text-[13px] font-medium transition hover:opacity-70" style="color: var(--ink-2);">Cara Kerja</a>
                <a href="#fitur"    class="text-[13px] font-medium transition hover:opacity-70" style="color: var(--ink-2);">Fitur</a>
                <a href="#faq"      class="text-[13px] font-medium transition hover:opacity-70" style="color: var(--ink-2);">FAQ</a>
            </nav>

            <!-- CTA -->
            <div class="flex items-center gap-2">
                <a href="login.php" class="btn-ghost !py-2.5 !px-4">Masuk</a>
                <a href="register.php" class="btn-primary !py-2.5 !px-4">Daftar</a>
            </div>
        </div>
    </header>

    <!-- ============================ HERO ============================ -->
    <section class="relative pt-32 pb-20 sm:pt-40 sm:pb-24 overflow-hidden">

        <!-- Aksen blob lembut di belakang -->
        <div class="absolute pointer-events-none" style="width: 480px; height: 480px; top: -120px; left: -100px; background: radial-gradient(circle, rgba(178, 212, 255, .55) 0%, transparent 65%); filter: blur(40px);"></div>
        <div class="absolute pointer-events-none" style="width: 400px; height: 400px; top: 60px; right: -80px; background: radial-gradient(circle, rgba(222, 235, 255, .75) 0%, transparent 65%); filter: blur(40px);"></div>

        <div class="max-w-6xl mx-auto px-6 relative">
            <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_1fr] gap-14 lg:gap-16 items-center">

                <!-- Kiri: teks -->
                <div>
                    <div class="hero-item hero-1 inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-6 bg-white"
                         style="border-color: var(--line);">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60" style="background: #36B37E;"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5" style="background: #36B37E;"></span>
                        </span>
                        <span class="text-[11px] font-medium" style="color: var(--ink-2);">
                            Sistem perpustakaan digital · <span class="tnum"><?= number_format($total_buku, 0, ',', '.'); ?></span> judul tersedia
                        </span>
                    </div>

                    <h1 class="hero-item hero-2 text-[42px] sm:text-[54px] lg:text-[62px] font-extrabold leading-[1.02] tracking-[-0.03em] mb-6"
                        style="color: var(--ink);">
                        Pinjam buku<br>
                        <span class="serif font-normal" style="color: var(--muted);">tanpa antre.</span>
                    </h1>

                    <p class="hero-item hero-3 text-[15px] leading-relaxed mb-8 max-w-lg"
                       style="color: var(--muted);">
                        Cari dari katalog perpustakaan, ajukan peminjaman, dan pantau tenggat pengembalian.
                        Semua dari satu halaman — tanpa mengisi formulir kertas atau mengantre di loket.
                    </p>

                    <div class="hero-item hero-4 flex flex-wrap items-center gap-3 mb-10">
                        <a href="register.php" class="btn-primary !py-3 !px-6">
                            <span>Daftar sekarang</span>
                            <i class="fas fa-arrow-right text-[11px]"></i>
                        </a>
                        <a href="#koleksi" class="btn-ghost !py-3 !px-6">
                            <i class="fas fa-magnifying-glass text-[11px]"></i>
                            <span>Lihat katalog</span>
                        </a>
                    </div>

                    <!-- Micro-badges -->
                    <div class="hero-item hero-4 flex items-center gap-5 text-[12px]" style="color: var(--muted);">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]" style="color: var(--brand);"></i>
                            <span>Gratis</span>
                        </div>
                        <div class="w-px h-3" style="background: var(--line);"></div>
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]" style="color: var(--brand);"></i>
                            <span>Maks 3 buku</span>
                        </div>
                        <div class="w-px h-3" style="background: var(--line);"></div>
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-check text-[10px]" style="color: var(--brand);"></i>
                            <span>14 hari pinjam</span>
                        </div>
                    </div>
                </div>

                <!-- Kanan: stack buku (mockup tipografi, tanpa 3D berlebihan) -->
                <div class="hero-item hero-3 relative h-[420px] hidden lg:block">

                    <!-- Buku belakang kiri -->
                    <?php
                    $b = $buku_hero[1] ?? $buku_hero[0];
                    $cover = (!empty($b['Cover']) && $b['Cover'] !== 'default.jpg' && file_exists('uploads/' . $b['Cover'])) ? 'uploads/' . $b['Cover'] : null;
                    ?>
                    <div class="absolute top-10 left-4 w-[180px] h-[260px] rounded-xl border overflow-hidden float-b"
                         style="border-color: var(--line); background: #fff; transform: rotate(-6deg); box-shadow: 0 12px 24px -10px rgba(9,30,66,.18);">
                        <?php if ($cover): ?>
                            <img src="<?= htmlspecialchars($cover); ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col justify-between p-4" style="background: #B3D4FF;">
                                <div class="text-[9px] font-bold uppercase tracking-[0.16em]" style="color: var(--brand-dark);">
                                    <?= htmlspecialchars($b['Kategori'] ?: 'Umum'); ?>
                                </div>
                                <div class="text-[14px] font-bold leading-tight clamp-3" style="color: var(--brand-dark);">
                                    <?= htmlspecialchars($b['Judul']); ?>
                                </div>
                                <div class="text-[11px] font-medium" style="color: var(--brand-dark);">
                                    <?= htmlspecialchars($b['Penulis']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Buku belakang kanan -->
                    <?php
                    $b = $buku_hero[2] ?? $buku_hero[0];
                    $cover = (!empty($b['Cover']) && $b['Cover'] !== 'default.jpg' && file_exists('uploads/' . $b['Cover'])) ? 'uploads/' . $b['Cover'] : null;
                    ?>
                    <div class="absolute top-20 right-4 w-[180px] h-[260px] rounded-xl border overflow-hidden float-c"
                         style="border-color: var(--line); background: #fff; transform: rotate(5deg); box-shadow: 0 12px 24px -10px rgba(9,30,66,.18);">
                        <?php if ($cover): ?>
                            <img src="<?= htmlspecialchars($cover); ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col justify-between p-4" style="background: var(--brand-light);">
                                <div class="text-[9px] font-bold uppercase tracking-[0.16em]" style="color: var(--brand);">
                                    <?= htmlspecialchars($b['Kategori'] ?: 'Umum'); ?>
                                </div>
                                <div class="text-[14px] font-bold leading-tight clamp-3" style="color: var(--brand-dark);">
                                    <?= htmlspecialchars($b['Judul']); ?>
                                </div>
                                <div class="text-[11px] font-medium" style="color: var(--brand);">
                                    <?= htmlspecialchars($b['Penulis']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Buku depan -->
                    <?php
                    $b = $buku_hero[0];
                    $cover = (!empty($b['Cover']) && $b['Cover'] !== 'default.jpg' && file_exists('uploads/' . $b['Cover'])) ? 'uploads/' . $b['Cover'] : null;
                    ?>
                    <div class="absolute top-14 left-1/2 -translate-x-1/2 w-[220px] h-[320px] rounded-xl border overflow-hidden float-a z-10"
                         style="border-color: var(--line); background: #fff; box-shadow: 0 24px 48px -18px rgba(9,30,66,.28), 0 0 0 1px rgba(9,30,66,.04);">
                        <?php if ($cover): ?>
                            <img src="<?= htmlspecialchars($cover); ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col justify-between p-5" style="background: var(--brand);">
                                <div class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/75">
                                    <?= htmlspecialchars($b['Kategori'] ?: 'Umum'); ?>
                                </div>
                                <div class="text-[18px] font-bold leading-tight clamp-3 text-white">
                                    <?= htmlspecialchars($b['Judul']); ?>
                                </div>
                                <div class="text-[12px] font-medium text-white/80">
                                    <?= htmlspecialchars($b['Penulis']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Chip kecil: jumlah stok -->
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 flex items-center gap-2 px-3 py-1.5 rounded-full border bg-white/95 backdrop-blur-sm z-20"
                         style="border-color: var(--line);">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: var(--brand);"></span>
                        <span class="text-[11px] font-medium tnum" style="color: var(--ink-2);">
                            <span class="font-bold"><?= number_format($total_stok, 0, ',', '.'); ?></span> eksemplar siap dipinjam
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Strip marquee -->
        <div class="mt-20 border-y overflow-hidden" style="border-color: var(--line-soft); background: var(--bg-soft);">
            <div class="marquee-track py-4">
                <?php for ($i = 0; $i < 2; $i++): ?>
                    <div class="flex items-center gap-10 px-6 whitespace-nowrap">
                        <?php
                        $items = [
                            'Cari berdasarkan judul, penulis, atau ISBN',
                            'Filter kategori & ketersediaan',
                            'Pinjam hingga 3 buku sekaligus',
                            'Durasi peminjaman 14 hari',
                            'Ulas buku setelah dikembalikan',
                            'Pantau tenggat pengembalian',
                        ];
                        foreach ($items as $item): ?>
                            <span class="flex items-center gap-3 text-[12px] font-medium" style="color: var(--muted);">
                                <span class="w-1 h-1 rounded-full" style="background: var(--brand);"></span>
                                <?= htmlspecialchars($item); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- ============================ STATISTIK ============================ -->
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-10">

                <div class="reveal reveal-d1">
                    <div class="text-[48px] sm:text-[56px] font-extrabold leading-none mb-3 tnum stat-num"
                         data-target="<?= $total_buku; ?>" style="color: var(--brand);">0</div>
                    <div class="text-[12px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                        Judul buku
                    </div>
                </div>

                <div class="reveal reveal-d2">
                    <div class="text-[48px] sm:text-[56px] font-extrabold leading-none mb-3 tnum stat-num"
                         data-target="<?= $total_stok; ?>" style="color: var(--brand);">0</div>
                    <div class="text-[12px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                        Eksemplar fisik
                    </div>
                </div>

                <div class="reveal reveal-d3">
                    <div class="text-[48px] sm:text-[56px] font-extrabold leading-none mb-3 tnum stat-num"
                         data-target="<?= $total_user; ?>" style="color: var(--brand);">0</div>
                    <div class="text-[12px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                        Anggota terdaftar
                    </div>
                </div>

                <div class="reveal reveal-d4">
                    <div class="text-[48px] sm:text-[56px] font-extrabold leading-none mb-3 tnum stat-num"
                         data-target="<?= $total_pinjam; ?>" style="color: var(--brand);">0</div>
                    <div class="text-[12px] font-semibold uppercase tracking-[0.1em]" style="color: var(--muted);">
                        Peminjaman total
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================ KATALOG ============================ -->
    <section id="koleksi" class="py-20" style="background: var(--bg-section);">
        <div class="max-w-6xl mx-auto px-6">

            <div class="mb-12 reveal">
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] mb-3" style="color: var(--brand);">
                    Katalog
                </div>
                <h2 class="text-[30px] sm:text-[40px] font-extrabold leading-[1.1] tracking-[-0.02em] mb-3"
                    style="color: var(--ink);">
                    Buku yang bisa dipinjam hari ini.
                </h2>
                <p class="text-[14px] max-w-xl leading-relaxed" style="color: var(--muted);">
                    Delapan judul terbaru dari katalog perpustakaan. Klik untuk melihat detail atau langsung pinjam setelah mendaftar.
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($buku_showcase as $idx => $buku):
                    $cover_src = (!empty($buku['Cover']) && $buku['Cover'] !== 'default.jpg' && file_exists('uploads/' . $buku['Cover']))
                        ? 'uploads/' . htmlspecialchars($buku['Cover'])
                        : null;
                    $is_available = ((int)$buku['Stok'] > 0);
                    $delay = 'reveal-d' . (($idx % 4) + 1);
                ?>
                <div class="card overflow-hidden flex flex-col reveal <?= $delay; ?> group">

                    <!-- Cover -->
                    <div class="aspect-[3/4] relative overflow-hidden" style="background: var(--bg);">
                        <?php if ($cover_src): ?>
                            <img src="<?= $cover_src; ?>" alt="<?= htmlspecialchars($buku['Judul']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col justify-between p-4" style="background: var(--brand-light);">
                                <div class="text-[9px] font-bold uppercase tracking-[0.14em] clamp-2" style="color: var(--brand);">
                                    <?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?>
                                </div>
                                <div class="text-[13px] font-bold leading-tight clamp-3" style="color: var(--brand-dark);">
                                    <?= htmlspecialchars($buku['Judul']); ?>
                                </div>
                                <div class="text-[10px] font-medium clamp-1" style="color: var(--brand);">
                                    <?= htmlspecialchars($buku['Penulis']); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Badge stok -->
                        <div class="absolute top-2 left-2">
                            <?php if ($is_available): ?>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold tnum"
                                      style="background: #E3FCEF; color: #006644;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background: #36B37E;"></span>
                                    <?= (int)$buku['Stok']; ?> stok
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                      style="background: #FFEBE6; color: #BF2600;">
                                    Habis
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-3.5 flex-1 flex flex-col">
                        <div class="text-[10px] font-bold uppercase tracking-[0.12em] mb-1.5 clamp-1" style="color: var(--brand);">
                            <?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?>
                        </div>
                        <div class="text-[13px] font-semibold leading-snug clamp-2 mb-1" style="color: var(--ink);">
                            <?= htmlspecialchars($buku['Judul']); ?>
                        </div>
                        <div class="text-[11px] clamp-1" style="color: var(--muted);">
                            <?= htmlspecialchars($buku['Penulis']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 text-center">
                <a href="register.php" class="text-[13px] font-semibold inline-flex items-center gap-1.5 transition hover:gap-2.5"
                   style="color: var(--brand);">
                    Daftar untuk meminjam semua koleksi
                    <i class="fas fa-arrow-right text-[11px]"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================ CARA KERJA ============================ -->
    <section id="cara-kerja" class="py-24">
        <div class="max-w-6xl mx-auto px-6">

            <div class="max-w-2xl mb-14 reveal">
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] mb-3" style="color: var(--brand);">
                    Cara Kerja
                </div>
                <h2 class="text-[30px] sm:text-[40px] font-extrabold leading-[1.1] tracking-[-0.02em] mb-3"
                    style="color: var(--ink);">
                    Tiga langkah, satu alur.
                </h2>
                <p class="text-[14px] leading-relaxed" style="color: var(--muted);">
                    Dari mencari sampai mengembalikan, semuanya tercatat rapi dalam satu akun.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="reveal reveal-d1">
                    <div class="text-[56px] font-extrabold leading-none tnum mb-4" style="color: var(--brand-light);">01</div>
                    <h3 class="text-[16px] font-bold mb-2" style="color: var(--ink);">Cari buku</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Telusuri katalog dengan filter kategori, ketersediaan, dan kata kunci.
                        Tambahkan beberapa buku ke keranjang sebelum konfirmasi.
                    </p>
                </div>

                <div class="reveal reveal-d2">
                    <div class="text-[56px] font-extrabold leading-none tnum mb-4" style="color: var(--brand-light);">02</div>
                    <h3 class="text-[16px] font-bold mb-2" style="color: var(--ink);">Ajukan peminjaman</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Konfirmasi buku yang Anda pilih. Sistem otomatis mencatat tanggal pinjam
                        dan tenggat pengembalian 14 hari.
                    </p>
                </div>

                <div class="reveal reveal-d3">
                    <div class="text-[56px] font-extrabold leading-none tnum mb-4" style="color: var(--brand-light);">03</div>
                    <h3 class="text-[16px] font-bold mb-2" style="color: var(--ink);">Kembalikan & ulas</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Setelah admin konfirmasi pengembalian, beri rating dan ulasan
                        supaya pembaca lain tahu kualitas buku.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ FITUR ============================ -->
    <section id="fitur" class="py-24" style="background: var(--bg-section);">
        <div class="max-w-6xl mx-auto px-6">

            <div class="max-w-2xl mb-14 reveal">
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] mb-3" style="color: var(--brand);">
                    Fitur
                </div>
                <h2 class="text-[30px] sm:text-[40px] font-extrabold leading-[1.1] tracking-[-0.02em] mb-3"
                    style="color: var(--ink);">
                    Yang Anda dapat setelah mendaftar.
                </h2>
                <p class="text-[14px] leading-relaxed" style="color: var(--muted);">
                    Setiap fitur dirancang untuk mempercepat peminjaman buku. Tidak ada yang tidak dipakai.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="card p-6 reveal reveal-d1">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4"
                         style="background: var(--brand-light); color: var(--brand);">
                        <i class="fas fa-magnifying-glass text-[14px]"></i>
                    </div>
                    <h3 class="text-[15px] font-bold mb-2" style="color: var(--ink);">Pencarian & filter lengkap</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Cari berdasarkan judul, penulis, penerbit, atau ISBN. Filter berdasarkan kategori
                        dan ketersediaan stok.
                    </p>
                </div>

                <div class="card p-6 reveal reveal-d2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4"
                         style="background: var(--brand-light); color: var(--brand);">
                        <i class="fas fa-bookmark text-[14px]"></i>
                    </div>
                    <h3 class="text-[15px] font-bold mb-2" style="color: var(--ink);">Koleksi pribadi</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Semua buku yang sedang Anda pinjam terkumpul di satu halaman, lengkap
                        dengan sisa hari sebelum jatuh tempo.
                    </p>
                </div>

                <div class="card p-6 reveal reveal-d3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4"
                         style="background: var(--brand-light); color: var(--brand);">
                        <i class="fas fa-clock-rotate-left text-[14px]"></i>
                    </div>
                    <h3 class="text-[15px] font-bold mb-2" style="color: var(--ink);">Riwayat & status</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Lihat riwayat peminjaman, status pengembalian, dan tanggal jatuh tempo
                        dalam dua tab terpisah: aktif dan selesai.
                    </p>
                </div>

                <div class="card p-6 reveal reveal-d4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4"
                         style="background: var(--brand-light); color: var(--brand);">
                        <i class="fas fa-star text-[14px]"></i>
                    </div>
                    <h3 class="text-[15px] font-bold mb-2" style="color: var(--ink);">Ulas & rating</h3>
                    <p class="text-[13px] leading-relaxed" style="color: var(--muted);">
                        Setiap buku yang sudah Anda kembalikan bisa diberi rating 1–5
                        dan komentar untuk membantu pembaca lain.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================ FAQ ============================ -->
    <section id="faq" class="py-24">
        <div class="max-w-3xl mx-auto px-6">

            <div class="mb-12 reveal">
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] mb-3" style="color: var(--brand);">
                    Tanya Jawab
                </div>
                <h2 class="text-[30px] sm:text-[40px] font-extrabold leading-[1.1] tracking-[-0.02em] mb-3"
                    style="color: var(--ink);">
                    Pertanyaan yang sering diajukan.
                </h2>
                <p class="text-[14px] leading-relaxed" style="color: var(--muted);">
                    Seputar keanggotaan dan peminjaman buku.
                </p>
            </div>

            <div class="space-y-3">
                <?php
                $faqs = [
                    ['Apakah pendaftaran anggota dipungut biaya?',
                     'Tidak ada biaya sama sekali. Seluruh anggota dapat mendaftar gratis dan langsung mendapat akses peminjaman katalog.'],
                    ['Berapa batas maksimal buku yang dapat dipinjam sekaligus?',
                     'Setiap akun memiliki kuota aktif maksimal 3 buku fisik secara bersamaan. Setelah buku dikembalikan, kuota Anda terisi otomatis.'],
                    ['Berapa lama masa berlaku peminjaman satu buku?',
                     'Durasi standar 14 hari kalender sejak tanggal persetujuan peminjaman. Tanggal jatuh tempo dapat dipantau di dashboard akun Anda.'],
                    ['Bagaimana cara memberikan ulasan dan rating?',
                     'Setelah buku Anda kembalikan dan admin konfirmasi, sistem menampilkan opsi "Beri Ulasan" di riwayat peminjaman. Anda bisa memilih bintang 1–5 dan menuliskan komentar.'],
                ];
                foreach ($faqs as $i => $faq): ?>
                    <div class="card overflow-hidden reveal reveal-d<?= ($i % 4) + 1; ?>">
                        <button type="button" onclick="toggleFaq(this)"
                                class="w-full px-5 py-4 text-left flex items-center justify-between gap-4 text-[13.5px] font-semibold transition"
                                style="color: var(--ink);">
                            <span><?= htmlspecialchars($faq[0]); ?></span>
                            <i class="fas fa-chevron-down text-[11px] transition-transform duration-300" style="color: var(--muted);"></i>
                        </button>
                        <div class="px-5 pb-5 text-[12.5px] leading-relaxed hidden" style="color: var(--muted);">
                            <?= htmlspecialchars($faq[1]); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================ CTA PENUTUP ============================ -->
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="rounded-2xl border p-8 sm:p-14 reveal"
                 style="border-color: var(--line); background: var(--bg-section);">

                <div class="max-w-2xl">
                    <h2 class="text-[28px] sm:text-[36px] font-extrabold leading-[1.15] tracking-[-0.02em] mb-4"
                        style="color: var(--ink);">
                        Siap mulai meminjam?
                    </h2>
                    <p class="text-[14px] leading-relaxed mb-8" style="color: var(--muted);">
                        Buat akun gratis, jelajahi katalog, dan pinjam buku pertama Anda hari ini.
                        Tanpa biaya, tanpa formulir panjang.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="register.php" class="btn-primary !py-3 !px-6">
                            <span>Daftar gratis</span>
                            <i class="fas fa-arrow-right text-[11px]"></i>
                        </a>
                        <a href="login.php" class="btn-ghost !py-3 !px-6">
                            <i class="fas fa-arrow-right-to-bracket text-[11px]"></i>
                            <span>Sudah punya akun</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ FOOTER ============================ -->
    <footer class="border-t" style="border-color: var(--line-soft);">
        <div class="max-w-6xl mx-auto px-6 py-10">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">

                <div class="flex items-center gap-2.5">
                    <img src="logo/logo.png" alt="Digital Library" class="h-8 object-contain">
                    <span class="text-[13px] font-semibold tracking-tight" style="color: var(--ink);">
                        Digital Library
                    </span>
                </div>

                <div class="flex items-center gap-6 text-[12px]" style="color: var(--muted);">
                    <a href="#koleksi" class="transition hover:opacity-70">Katalog</a>
                    <a href="#cara-kerja" class="transition hover:opacity-70">Cara Kerja</a>
                    <a href="#fitur" class="transition hover:opacity-70">Fitur</a>
                    <a href="#faq" class="transition hover:opacity-70">FAQ</a>
                    <a href="register.php" class="font-semibold transition hover:opacity-70" style="color: var(--brand);">
                        Daftar
                    </a>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-[11px]"
                 style="border-color: var(--line-soft); color: var(--muted);">
                <div>&copy; <?= date('Y'); ?> Digital Library &middot; Sistem Peminjaman Buku</div>
                <div>Dibuat untuk mempermudah akses literasi.</div>
            </div>
        </div>
    </footer>

    <script>
    /* ============ Header transparan → solid saat scroll ============ */
    (function () {
        const header = document.getElementById('siteHeader');
        let last = -1;
        function onScroll() {
            const y = window.scrollY;
            if (y === last) return;
            last = y;
            if (y > 20) {
                header.style.background = 'rgba(255,255,255,0.88)';
                header.style.backdropFilter = 'blur(12px)';
                header.style.borderBottom = '1px solid #EBECF0';
            } else {
                header.style.background = 'transparent';
                header.style.backdropFilter = 'none';
                header.style.borderBottom = 'none';
            }
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();

    /* ============ Scroll reveal ============ */
    (function () {
        const els = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            els.forEach(el => el.classList.add('is-visible'));
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
        els.forEach(el => io.observe(el));
    })();

    /* ============ Counter naik ============ */
    (function () {
        const counters = document.querySelectorAll('.stat-num');
        if (!counters.length) return;

        function animate(el) {
            const target = parseInt(el.dataset.target || '0', 10);
            if (isNaN(target) || target <= 0) { el.textContent = '0'; return; }

            const duration = 1400;
            const start = performance.now();
            function tick(now) {
                const t = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - t, 3);
                el.textContent = Math.round(target * eased).toLocaleString('id-ID');
                if (t < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        }

        if (!('IntersectionObserver' in window)) {
            counters.forEach(animate);
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });
        counters.forEach(el => io.observe(el));
    })();

    /* ============ FAQ accordion ============ */
    function toggleFaq(button) {
        const content = button.nextElementSibling;
        const icon = button.querySelector('i');
        const isOpen = !content.classList.contains('hidden');

        // Tutup semua
        document.querySelectorAll('#faq .card > div').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('#faq .card i').forEach(ic => ic.style.transform = 'rotate(0deg)');

        if (!isOpen) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        }
    }

    /* ============ Smooth scroll untuk anchor ============ */
    (function () {
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', function (e) {
                const id = this.getAttribute('href');
                if (!id || id === '#') return;
                const target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                const top = target.getBoundingClientRect().top + window.scrollY - 72;
                window.scrollTo({ top, behavior: 'smooth' });
            });
        });
    })();
    </script>
</body>
</html>
