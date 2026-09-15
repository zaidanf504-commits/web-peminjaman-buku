<?php
session_start();

// Kalau sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['UserID'])) {
    if (($_SESSION['Role'] ?? '') === 'admin') {
        header("Location: admin_peminjaman.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

require 'koneksi.php';

// Ambil statistik nyata dari database
$total_buku    = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM buku"))['t'] ?? 0);
$total_stok    = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(Stok),0) AS t FROM buku"))['t'] ?? 0);
$total_user    = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM user"))['t'] ?? 0);
$total_pinjam  = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM peminjaman"))['t'] ?? 0);

// Ambil buku untuk hero (top 4 buku terbaru)
$buku_hero = [];
$q_hero = mysqli_query($koneksi, "SELECT BukuID, Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover FROM buku ORDER BY BukuID DESC LIMIT 4");
if ($q_hero) {
    while ($r = mysqli_fetch_assoc($q_hero)) {
        $buku_hero[] = $r;
    }
}

// Ambil buku untuk showcase katalog pilihan (hingga 8 buku)
$buku_showcase = [];
$q_show = mysqli_query($koneksi, "SELECT BukuID, Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover FROM buku ORDER BY BukuID DESC LIMIT 8");
if ($q_show) {
    while ($r = mysqli_fetch_assoc($q_show)) {
        $buku_showcase[] = $r;
    }
}

// Fallback jika database masih kosong
if (empty($buku_hero)) {
    $buku_hero = [
        ['BukuID' => 1, 'Judul' => 'Atomic Habits', 'Penulis' => 'James Clear', 'Kategori' => 'Pengembangan Diri', 'Stok' => 5, 'Cover' => 'atomic.jpeg', 'TahunTerbit' => 2018, 'Penerbit' => 'Gramedia'],
        ['BukuID' => 2, 'Judul' => 'Janji', 'Penulis' => 'Tere Liye', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 4, 'Cover' => 'janji.jpeg', 'TahunTerbit' => 2021, 'Penerbit' => 'Republika'],
        ['BukuID' => 3, 'Judul' => 'Dompet Ayah Sepatu Ibu', 'Penulis' => 'J.S Khairen', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 9, 'Cover' => 'dompet.jpeg', 'TahunTerbit' => 2020, 'Penerbit' => 'Noura Books'],
    ];
}
if (empty($buku_showcase)) {
    $buku_showcase = $buku_hero;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library — Perpustakaan Modern Generasi Baru</title>
    <meta name="description" content="Platform peminjaman buku modern, cepat, dan 100% digital. Akses ribuan koleksi literasi tanpa antre dengan pengalaman premium.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        navy: {
                            950: '#06080F',
                            900: '#0B0F19',
                            850: '#0F1626',
                            800: '#141D32',
                            700: '#1E2B48',
                            600: '#2A3C63',
                        },
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            violet: '#8b5cf6',
                            emerald: '#10b981',
                            amber: '#f59e0b',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-base: #07090E;
            --bg-surface: #0E1320;
            --bg-card: rgba(18, 26, 43, 0.7);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(59, 130, 246, 0.35);
            --glow-primary: rgba(59, 130, 246, 0.45);
            --glow-cyan: rgba(6, 182, 212, 0.35);
            --glow-violet: rgba(139, 92, 246, 0.35);
        }

        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background-color: var(--bg-base);
            color: #F1F5F9;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            overflow-x: hidden;
            selection-background-color: #3b82f6;
            selection-color: #ffffff;
        }

        /* Ambient interactive spotlight */
        #cursorSpotlight {
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, rgba(139, 92, 246, 0.06) 40%, transparent 70%);
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
            mix-blend-mode: screen;
        }

        /* Subtle luxury grid */
        .bg-grid-pattern {
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        }

        /* High-end glassmorphism */
        .glass-panel {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
        }

        .glass-card {
            background: rgba(15, 22, 38, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .glass-card:hover {
            border-color: rgba(96, 165, 250, 0.35);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), 0 0 25px rgba(59, 130, 246, 0.15);
        }

        /* Iridescent text gradient */
        .text-gradient {
            background: linear-gradient(135deg, #FFFFFF 20%, #93C5FD 60%, #60A5FA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient-cyan {
            background: linear-gradient(135deg, #38BDF8 0%, #818CF8 50%, #C084FC 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient-amber {
            background: linear-gradient(135deg, #FDE68A 0%, #F59E0B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Glowing button with sweep light */
        .btn-glow {
            position: relative;
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.25);
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .btn-glow::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 40%;
            height: 200%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.35), transparent);
            transform: rotate(30deg);
            transition: all 0.75s ease;
        }

        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(37, 99, 235, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .btn-glow:hover::after {
            left: 140%;
        }

        /* 3D Realistic Book Styling */
        .book-stage {
            perspective: 1200px;
        }

        .book-3d {
            position: relative;
            width: 220px;
            height: 315px;
            border-radius: 4px 14px 14px 4px;
            transform-style: preserve-3d;
            box-shadow: 
                -12px 15px 35px rgba(0, 0, 0, 0.75),
                0 0 45px rgba(59, 130, 246, 0.2);
            transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.6s ease;
        }

        /* Book spine thickness */
        .book-3d::before {
            content: '';
            position: absolute;
            left: 0;
            top: 2px;
            bottom: 2px;
            width: 14px;
            background: linear-gradient(90deg, rgba(0,0,0,0.5) 0%, rgba(255,255,255,0.2) 50%, rgba(0,0,0,0.4) 100%);
            border-radius: 3px 0 0 3px;
            z-index: 10;
        }

        /* Book paper pages side effect */
        .book-3d::after {
            content: '';
            position: absolute;
            right: -8px;
            top: 6px;
            bottom: 6px;
            width: 8px;
            background: linear-gradient(90deg, #E2E8F0 0%, #CBD5E1 50%, #94A3B8 100%);
            border-radius: 0 4px 4px 0;
            box-shadow: inset 0 0 3px rgba(0,0,0,0.2);
        }

        /* Floating Bobbing Keyframes */
        @keyframes floatHero {
            0%, 100% { transform: translateY(0px) rotate(-3deg); }
            50% { transform: translateY(-14px) rotate(-1deg); }
        }

        @keyframes floatBackLeft {
            0%, 100% { transform: translateY(0px) rotate(-12deg) scale(0.92); }
            50% { transform: translateY(-10px) rotate(-10deg) scale(0.92); }
        }

        @keyframes floatBackRight {
            0%, 100% { transform: translateY(0px) rotate(11deg) scale(0.94); }
            50% { transform: translateY(-12px) rotate(9deg) scale(0.94); }
        }

        .anim-float-center {
            animation: floatHero 7s ease-in-out infinite;
        }

        .anim-float-left {
            animation: floatBackLeft 8s ease-in-out infinite;
            animation-delay: -2s;
        }

        .anim-float-right {
            animation: floatBackRight 7.5s ease-in-out infinite;
            animation-delay: -4s;
        }

        /* Aurora orbs */
        @keyframes auroraDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -40px) scale(1.1); }
            66% { transform: translate(-25px, 20px) scale(0.95); }
        }

        .aurora-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.35;
            animation: auroraDrift 20s ease-in-out infinite;
            pointer-events: none;
        }

        /* Reveal animations */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.8s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: opacity, transform;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #07090E; }
        ::-webkit-scrollbar-thumb { background: #1E293B; border-radius: 4px; border: 2px solid #07090E; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }

        /* Badge pulse */
        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.6); opacity: 0.4; }
        }
        .pulse-dot {
            animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Marquee */
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-container {
            display: flex;
            width: max-content;
            animation: marqueeScroll 35s linear infinite;
        }
        .marquee-container:hover {
            animation-play-state: paused;
        }

        /* Modal blur transition */
        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .modal-active {
            opacity: 1;
            transform: scale(1);
        }
    </style>
</head>
<body class="relative min-h-screen text-slate-100">

    <!-- Ambient Cursor Spotlight Effect -->
    <div id="cursorSpotlight" class="hidden md:block"></div>

    <!-- Background Aurora Glow Orbs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="aurora-orb w-[650px] h-[650px] -top-48 -left-32 bg-blue-600/30"></div>
        <div class="aurora-orb w-[550px] h-[550px] top-1/4 -right-40 bg-indigo-600/25" style="animation-delay: -6s;"></div>
        <div class="aurora-orb w-[700px] h-[700px] -bottom-40 left-1/3 bg-cyan-600/20" style="animation-delay: -12s;"></div>
        <div class="absolute inset-0 bg-grid-pattern opacity-40"></div>
        <!-- Radial mask to make grid fade away at edges -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-navy-950/70 to-navy-950 pointer-events-none"></div>
    </div>

    <!-- Canvas Particle Network (Ultra-smooth 60fps Starfield/Nodes) -->
    <canvas id="particleCanvas" class="fixed inset-0 pointer-events-none z-0 opacity-40"></canvas>

    <!-- ============================================================
         NAVBAR / HEADER
         ============================================================ -->
    <header id="mainHeader" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl px-5 py-3 flex items-center justify-between shadow-2xl border border-white/10 transition-all duration-300" id="navContainer">
                
                <!-- Brand Logo -->
                <a href="landing.php" class="flex items-center gap-3.5 group">
                    <img src="logo/logo.png" alt="Digital Library" class="h-10 object-contain transition-transform group-hover:scale-105 duration-300">
                    <div class="flex flex-col">
                        <span class="text-[15px] font-extrabold tracking-tight text-white flex items-center gap-1.5">
                            Digital<span class="text-blue-400">Library</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-medium tracking-wide">Perpustakaan Pintar Digital</span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 bg-navy-900/80 px-3 py-1.5 rounded-xl border border-white/5 text-sm font-medium text-slate-300">
                    <a href="#fitur" class="px-3.5 py-1.5 rounded-lg hover:text-white hover:bg-white/5 transition-all">Keunggulan</a>
                    <a href="#koleksi" class="px-3.5 py-1.5 rounded-lg hover:text-white hover:bg-white/5 transition-all">Katalog Pilihan</a>
                    <a href="#alur" class="px-3.5 py-1.5 rounded-lg hover:text-white hover:bg-white/5 transition-all">Alur Pinjam</a>
                    <a href="#testimoni" class="px-3.5 py-1.5 rounded-lg hover:text-white hover:bg-white/5 transition-all">Ulasan</a>
                    <a href="#faq" class="px-3.5 py-1.5 rounded-lg hover:text-white hover:bg-white/5 transition-all">FAQ</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="flex items-center gap-3">
                    <a href="login.php" class="text-sm font-semibold text-slate-300 hover:text-white px-3.5 py-2 rounded-xl transition hover:bg-white/5">
                        <i class="fas fa-arrow-right-to-bracket mr-1.5 text-xs text-slate-400"></i> Masuk
                    </a>
                    <a href="register.php" class="btn-glow px-4 sm:px-5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white flex items-center gap-2">
                        <span>Daftar Akun</span>
                        <i class="fas fa-sparkles text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- ============================================================
         HERO SECTION
         ============================================================ -->
    <section class="relative pt-36 pb-20 md:pt-44 md:pb-28 overflow-hidden z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                
                <!-- Left Column: High-Impact Typography & CTA -->
                <div class="lg:col-span-7 flex flex-col items-start text-left">
                    
                    <!-- Live Status Pill -->
                    <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-300 text-xs font-semibold mb-6 shadow-sm shadow-blue-500/10 backdrop-blur-md">
                        <span class="relative flex h-2 w-2">
                            <span class="pulse-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>Sistem Realtime v2.6 &middot; Katalog Terkini Terbuka</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.12] mb-6">
                        Pintu Gerbang Literasi.<br>
                        <span class="text-gradient">Pinjam Instan</span>, <br class="hidden sm:inline">
                        <span class="font-serif italic font-normal text-slate-300">Tanpa Antrean Fisik.</span>
                    </h1>

                    <!-- Persuasive Subtext -->
                    <p class="text-base sm:text-lg text-slate-400 font-normal leading-relaxed max-w-xl mb-8">
                        Eksplorasi ribuan judul buku fisik dan referensi akademis langsung dari genggaman. Peminjaman cepat 14 hari, manajemen kuota transparan, dan ulasan komunitas terpercaya.
                    </p>

                    <!-- CTA Actions -->
                    <div class="w-full sm:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-10">
                        <a href="register.php" class="btn-glow px-7 py-3.5 rounded-xl text-sm font-bold text-white text-center flex items-center justify-center gap-3">
                            <span>Mulai Pinjam Sekarang</span>
                            <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                        </a>
                        <a href="#koleksi" class="glass-card px-6 py-3.5 rounded-xl text-sm font-semibold text-slate-200 hover:text-white text-center flex items-center justify-center gap-2.5 hover:border-blue-400/30">
                            <i class="fas fa-compass text-blue-400 text-xs"></i>
                            <span>Jelajahi Katalog</span>
                        </a>
                    </div>

                    <!-- Trust Micro-Badges -->
                    <div class="grid grid-cols-3 gap-4 pt-6 border-t border-white/10 w-full max-w-lg text-slate-400 text-xs">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-400"></i>
                            <span class="font-medium">100% Gratis</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-layer-group text-blue-400"></i>
                            <span class="font-medium">Maks. 3 Buku</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-check text-indigo-400"></i>
                            <span class="font-medium">Durasi 14 Hari</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Interactive 3D Book Stage (Hero Visual Masterpiece) -->
                <div class="lg:col-span-5 relative flex items-center justify-center pt-8 lg:pt-0">
                    <div class="book-stage relative w-[320px] sm:w-[380px] h-[440px] flex items-center justify-center">
                        
                        <!-- Glowing backdrop disc -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/30 via-indigo-600/20 to-cyan-400/20 rounded-full filter blur-3xl -z-10 scale-90"></div>

                        <!-- Back Left Book (Item 1) -->
                        <?php 
                        $b_left = $buku_hero[1] ?? $buku_hero[0]; 
                        $has_left = !empty($b_left['Cover']) && $b_left['Cover'] !== 'default.jpg';
                        ?>
                        <div class="absolute -left-4 sm:-left-8 top-10 book-3d anim-float-left cursor-pointer z-10 group" onclick="openQuickView(<?= htmlspecialchars(json_encode($b_left)); ?>)">
                            <div class="w-full h-full rounded-r-xl overflow-hidden bg-navy-800 border border-white/15 relative">
                                <?php if ($has_left && file_exists('uploads/' . $b_left['Cover'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($b_left['Cover']); ?>" alt="<?= htmlspecialchars($b_left['Judul']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-indigo-900 via-slate-900 to-navy-950 p-5 flex flex-col justify-between">
                                        <span class="text-[9px] font-bold text-indigo-300 uppercase tracking-widest"><?= htmlspecialchars($b_left['Kategori'] ?? 'Katalog'); ?></span>
                                        <h4 class="text-sm font-bold text-white leading-snug line-clamp-3"><?= htmlspecialchars($b_left['Judul']); ?></h4>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($b_left['Penulis']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                    <span class="text-[11px] font-semibold text-cyan-300"><i class="fas fa-eye mr-1"></i> Preview Cepat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Back Right Book (Item 2) -->
                        <?php 
                        $b_right = $buku_hero[2] ?? ($buku_hero[1] ?? $buku_hero[0]); 
                        $has_right = !empty($b_right['Cover']) && $b_right['Cover'] !== 'default.jpg';
                        ?>
                        <div class="absolute -right-4 sm:-right-8 top-20 book-3d anim-float-right cursor-pointer z-10 group" onclick="openQuickView(<?= htmlspecialchars(json_encode($b_right)); ?>)">
                            <div class="w-full h-full rounded-r-xl overflow-hidden bg-navy-800 border border-white/15 relative">
                                <?php if ($has_right && file_exists('uploads/' . $b_right['Cover'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($b_right['Cover']); ?>" alt="<?= htmlspecialchars($b_right['Judul']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-blue-950 via-slate-900 to-navy-950 p-5 flex flex-col justify-between">
                                        <span class="text-[9px] font-bold text-cyan-300 uppercase tracking-widest"><?= htmlspecialchars($b_right['Kategori'] ?? 'Katalog'); ?></span>
                                        <h4 class="text-sm font-bold text-white leading-snug line-clamp-3"><?= htmlspecialchars($b_right['Judul']); ?></h4>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($b_right['Penulis']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                    <span class="text-[11px] font-semibold text-cyan-300"><i class="fas fa-eye mr-1"></i> Preview Cepat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Centerpiece Main Book (Item 0) -->
                        <?php 
                        $b_center = $buku_hero[0]; 
                        $has_center = !empty($b_center['Cover']) && $b_center['Cover'] !== 'default.jpg';
                        ?>
                        <div class="relative book-3d anim-float-center z-20 cursor-pointer group shadow-2xl" id="heroMainBook" onclick="openQuickView(<?= htmlspecialchars(json_encode($b_center)); ?>)">
                            <div class="w-full h-full rounded-r-xl overflow-hidden bg-navy-850 border-2 border-blue-500/30 relative">
                                <?php if ($has_center && file_exists('uploads/' . $b_center['Cover'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($b_center['Cover']); ?>" alt="<?= htmlspecialchars($b_center['Judul']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-blue-600 via-indigo-700 to-navy-900 p-6 flex flex-col justify-between text-white">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-black uppercase tracking-widest bg-white/20 px-2 py-0.5 rounded backdrop-blur-md">Pilihan Utama</span>
                                            <i class="fas fa-star text-amber-300 text-xs"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-black leading-tight mb-1 text-white"><?= htmlspecialchars($b_center['Judul']); ?></h3>
                                            <p class="text-xs text-blue-200"><?= htmlspecialchars($b_center['Penulis']); ?></p>
                                        </div>
                                        <div class="text-[10px] text-blue-200/80 flex items-center justify-between border-t border-white/10 pt-3">
                                            <span><?= htmlspecialchars($b_center['Kategori'] ?? 'Koleksi Digital'); ?></span>
                                            <span>Stok: <?= (int)$b_center['Stok']; ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Center Glass overlay on hover -->
                                <div class="absolute inset-0 bg-gradient-to-t from-navy-950/95 via-navy-950/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col justify-end p-5">
                                    <span class="text-xs font-bold text-cyan-300 mb-1 flex items-center gap-1.5">
                                        <i class="fas fa-sparkles"></i> <?= htmlspecialchars($b_center['Judul']); ?>
                                    </span>
                                    <span class="text-[11px] text-slate-300">Klik untuk melihat detail & sinopsis</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Glass Pill: Rating -->
                        <div class="absolute -bottom-6 -left-6 z-30 glass-card px-4 py-2.5 rounded-2xl flex items-center gap-3 shadow-xl border border-white/10 animate-pulse">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white flex items-center gap-1">
                                    <span>4.9 / 5.0</span>
                                    <span class="text-[10px] text-slate-400 font-normal">(1.4k+ rating)</span>
                                </div>
                                <div class="text-[10px] text-emerald-400 font-medium">Review Sangat Positif</div>
                            </div>
                        </div>

                        <!-- Floating Glass Pill: Fast Borrow -->
                        <div class="absolute -top-4 -right-4 z-30 glass-card px-3.5 py-2 rounded-2xl flex items-center gap-2.5 shadow-xl border border-white/10">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                            <span class="text-xs font-semibold text-slate-200">Siap Dipinjam Hari Ini</span>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Dynamic Marquee Strip -->
        <div class="mt-20 border-y border-white/5 bg-navy-900/50 backdrop-blur-md overflow-hidden py-3.5">
            <div class="marquee-container flex items-center gap-12 text-xs font-medium text-slate-400 whitespace-nowrap">
                <div class="flex items-center gap-12">
                    <span class="flex items-center gap-2"><i class="fas fa-bolt text-blue-400"></i> Peminjaman Otomatis 1-Klik</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-shield-halved text-emerald-400"></i> Sistem Verifikasi Akun Aman</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-book-open text-cyan-400"></i> Terintegrasi <?= number_format($total_buku, 0, ',', '.'); ?> Judul Buku Resmi</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-clock-rotate-left text-indigo-400"></i> Pantau Riwayat & Jatuh Tempo 14 Hari</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-comments text-amber-400"></i> Ulasan & Rekomendasi Antar Pembaca</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-circle-check text-emerald-400"></i> Bebas Biaya Pendaftaran</span>
                    <span class="text-white/20">&bull;</span>
                </div>
                <!-- Duplicate for seamless infinite loop -->
                <div class="flex items-center gap-12">
                    <span class="flex items-center gap-2"><i class="fas fa-bolt text-blue-400"></i> Peminjaman Otomatis 1-Klik</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-shield-halved text-emerald-400"></i> Sistem Verifikasi Akun Aman</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-book-open text-cyan-400"></i> Terintegrasi <?= number_format($total_buku, 0, ',', '.'); ?> Judul Buku Resmi</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-clock-rotate-left text-indigo-400"></i> Pantau Riwayat & Jatuh Tempo 14 Hari</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-comments text-amber-400"></i> Ulasan & Rekomendasi Antar Pembaca</span>
                    <span class="text-white/20">&bull;</span>
                    <span class="flex items-center gap-2"><i class="fas fa-circle-check text-emerald-400"></i> Bebas Biaya Pendaftaran</span>
                    <span class="text-white/20">&bull;</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         LIVE METRICS / STATISTIK RESMI
         ============================================================ -->
    <section class="py-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                <!-- Stat 1: Judul Buku -->
                <div class="glass-card p-6 rounded-2xl reveal reveal-delay-1 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Koleksi Judul</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 text-sm">
                            <i class="fas fa-book-bookmark"></i>
                        </div>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-1 counter-number" data-target="<?= $total_buku; ?>">0</div>
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-emerald-400 font-semibold"><i class="fas fa-arrow-trend-up"></i> Terus Bertambah</span>
                    </p>
                </div>

                <!-- Stat 2: Eksemplar Fisik -->
                <div class="glass-card p-6 rounded-2xl reveal reveal-delay-2 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Stok Eksemplar</span>
                        <div class="w-9 h-9 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 text-sm">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-1 counter-number" data-target="<?= $total_stok; ?>">0</div>
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-cyan-400 font-semibold">Tersedia di Rak</span>
                    </p>
                </div>

                <!-- Stat 3: Anggota -->
                <div class="glass-card p-6 rounded-2xl reveal reveal-delay-3 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pembaca Aktif</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 text-sm">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-1 counter-number" data-target="<?= $total_user; ?>">0</div>
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-indigo-400 font-semibold">Member Terverifikasi</span>
                    </p>
                </div>

                <!-- Stat 4: Peminjaman Selesai -->
                <div class="glass-card p-6 rounded-2xl reveal reveal-delay-4 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Sirkulasi</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-1 counter-number" data-target="<?= $total_pinjam; ?>">0</div>
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-emerald-400 font-semibold">Transaksi Peminjaman</span>
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         BENTO GRID: KEUNGGULAN SISTEM MODERN (EXPENSIVE LOOK)
         ============================================================ -->
    <section id="fitur" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Title -->
            <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-3">
                    Keunggulan Arsitektur
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-4">
                    Dirancang untuk Pengalaman Membaca Tanpa Hambatan.
                </h2>
                <p class="text-slate-400 text-base leading-relaxed">
                    Setiap interaksi dioptimalkan dengan performa tinggi. Rasakan kemudahan manajemen peminjaman yang intuitif dan profesional.
                </p>
            </div>

            <!-- Bento Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Bento 1: Large Span 2 Column (Katalog Pintar) -->
                <div class="md:col-span-2 glass-card p-8 sm:p-10 rounded-3xl reveal relative overflow-hidden flex flex-col justify-between group">
                    <div class="absolute top-0 right-0 w-80 h-80 bg-gradient-to-bl from-blue-500/20 to-transparent rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-400 text-lg mb-6 shadow-inner">
                            <i class="fas fa-magnifying-glass-chart"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">Katalog Realtime & Pencarian Multikriteria</h3>
                        <p class="text-slate-400 text-sm leading-relaxed max-w-lg mb-6">
                            Temukan karya favorit Anda dalam hitungan detik. Filter berdasarkan kategori, ketersediaan stok fisik di rak, pengarang, maupun nomor ISBN dengan akurasi presisi.
                        </p>
                    </div>

                    <!-- Mini UI Mockup inside Bento -->
                    <div class="bg-navy-950/80 rounded-2xl border border-white/10 p-4 sm:p-5 backdrop-blur-md shadow-2xl">
                        <div class="flex items-center justify-between mb-3 border-b border-white/5 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                <span class="text-xs text-slate-400 ml-2 font-mono">katalog_filter.sql</span>
                            </div>
                            <span class="text-[11px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">Sinkronisasi Otomatis</span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="px-3 py-1.5 rounded-lg bg-blue-500/20 text-blue-300 font-medium border border-blue-500/30">Semua Kategori</span>
                            <span class="px-3 py-1.5 rounded-lg bg-white/5 text-slate-400 hover:text-white transition">Teknologi & Informatika</span>
                            <span class="px-3 py-1.5 rounded-lg bg-white/5 text-slate-400 hover:text-white transition">Sastra & Fiksi</span>
                            <span class="px-3 py-1.5 rounded-lg bg-white/5 text-slate-400 hover:text-white transition">Pengembangan Diri</span>
                        </div>
                    </div>
                </div>

                <!-- Bento 2: 1 Column (Durasi 14 Hari & Reminder) -->
                <div class="glass-card p-8 rounded-3xl reveal reveal-delay-1 flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/15 rounded-full blur-2xl pointer-events-none"></div>
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-lg mb-6 shadow-inner">
                            <i class="fas fa-calendar-days"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Transparansi Durasi</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-6">
                            Peminjaman berlaku 14 hari kerja dengan countdown jelas pada dashboard, menghindari keterlambatan dan menjaga kenyamanan sirkulasi.
                        </p>
                    </div>

                    <!-- Visual Timeline Card -->
                    <div class="bg-navy-950/80 rounded-xl border border-white/10 p-3.5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-300 flex items-center justify-center font-bold text-xs">
                            14D
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-white">Siklus Peminjaman Standar</div>
                            <div class="text-[10px] text-slate-400">Pengingat otomatis sebelum jatuh tempo</div>
                        </div>
                    </div>
                </div>

                <!-- Bento 3: 1 Column (Keranjang Multi-Buku) -->
                <div class="glass-card p-8 rounded-3xl reveal reveal-delay-2 flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-cyan-500/15 rounded-full blur-2xl pointer-events-none"></div>
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg mb-6 shadow-inner">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Multi-Buku Cerdas</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-6">
                            Pinjam hingga 3 judul sekaligus dalam satu kali konfirmasi. Keranjang cerdas mencegah duplikasi peminjaman secara realtime.
                        </p>
                    </div>

                    <!-- Mini Visual Pill -->
                    <div class="flex items-center justify-between bg-navy-950/80 rounded-xl border border-white/10 px-4 py-3">
                        <span class="text-xs text-slate-300">Batas Kuota Anggota</span>
                        <span class="text-xs font-bold text-cyan-300 bg-cyan-500/20 px-2.5 py-1 rounded-md border border-cyan-500/30">3 Eksemplar</span>
                    </div>
                </div>

                <!-- Bento 4: Large Span 2 Column (Ulasan & Komunitas) -->
                <div class="md:col-span-2 glass-card p-8 sm:p-10 rounded-3xl reveal reveal-delay-3 relative overflow-hidden flex flex-col justify-between group">
                    <div class="absolute top-0 left-0 w-80 h-80 bg-gradient-to-br from-amber-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg mb-6 shadow-inner">
                            <i class="fas fa-star-half-stroke"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">Sistem Ulasan & Rekomendasi Terverifikasi</h3>
                        <p class="text-slate-400 text-sm leading-relaxed max-w-lg mb-6">
                            Hanya pembaca yang telah menyelesaikan peminjaman dan mengembalikan buku yang dapat memberikan rating dan review. Menghasilkan ekosistem rekomendasi yang objektif dan terpercaya.
                        </p>
                    </div>

                    <!-- Mockup Review Card -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="bg-navy-950/80 border border-white/10 rounded-xl p-4">
                            <div class="flex items-center gap-1 text-amber-400 text-xs mb-2">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <span class="text-white text-xs font-bold ml-1.5">5.0</span>
                            </div>
                            <p class="text-xs text-slate-300 italic mb-2">"Proses pinjam bukunya cepet banget, gak perlu antre isi kertas lagi. Pas kembaliin tinggal kasih rating."</p>
                            <span class="text-[11px] text-slate-500 font-semibold">— Pembaca Terverifikasi</span>
                        </div>
                        <div class="bg-navy-950/80 border border-white/10 rounded-xl p-4 hidden sm:block">
                            <div class="flex items-center gap-1 text-amber-400 text-xs mb-2">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <span class="text-white text-xs font-bold ml-1.5">5.0</span>
                            </div>
                            <p class="text-xs text-slate-300 italic mb-2">"Koleksi buku teknologinya lengkap, notifikasi tenggat waktunya sangat membantu."</p>
                            <span class="text-[11px] text-slate-500 font-semibold">— Mahasiswa Informatika</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         KATALOG PILIHAN PEMBACA (LIVE REAL BOOKS FROM DATABASE)
         ============================================================ -->
    <section id="koleksi" class="py-24 relative z-10 bg-navy-900/30 border-y border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header & Filter Controls -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14 reveal">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-bold uppercase tracking-widest mb-3">
                        Koleksi Unggulan
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-2">
                        Jelajahi Karya Literasi Terpopuler.
                    </h2>
                    <p class="text-slate-400 text-sm">
                        Buku fisik berkualitas siap dipinjam hari ini dari katalog perpustakaan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="login.php" class="text-xs sm:text-sm font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2 group transition">
                        <span>Buka Semua Katalog</span>
                        <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            <!-- Grid of Real Database Books -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-7">
                <?php foreach ($buku_showcase as $idx => $buku): 
                    $cover_src = '';
                    if (!empty($buku['Cover']) && $buku['Cover'] !== 'default.jpg' && file_exists('uploads/' . $buku['Cover'])) {
                        $cover_src = 'uploads/' . htmlspecialchars($buku['Cover']);
                    }
                    $is_available = ((int)$buku['Stok'] > 0);
                    $delay_class = 'reveal-delay-' . (($idx % 4) + 1);
                ?>
                <div class="glass-card rounded-2xl overflow-hidden p-3.5 sm:p-4 flex flex-col justify-between reveal <?= $delay_class; ?> group">
                    
                    <!-- Cover Container -->
                    <div class="relative w-full aspect-[3/4] rounded-xl overflow-hidden mb-3.5 bg-navy-800 shadow-md group-hover:shadow-2xl transition-all">
                        <?php if ($cover_src): ?>
                            <img src="<?= $cover_src; ?>" alt="<?= htmlspecialchars($buku['Judul']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-slate-800 via-navy-900 to-navy-950 p-4 flex flex-col justify-between text-white">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-blue-400"><?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?></span>
                                <div class="text-xs font-bold leading-tight"><?= htmlspecialchars($buku['Judul']); ?></div>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($buku['Penulis']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Availability Badge -->
                        <div class="absolute top-2.5 left-2.5">
                            <?php if ($is_available): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/90 text-white backdrop-blur-md shadow-sm">
                                    Tersedia (<?= (int)$buku['Stok']; ?>)
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/90 text-white backdrop-blur-md shadow-sm">
                                    Stok Habis
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Preview Action Button on Hover -->
                        <button type="button" onclick="openQuickView(<?= htmlspecialchars(json_encode($buku)); ?>)" class="absolute inset-0 bg-navy-950/75 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 text-white text-xs font-bold">
                            <span class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/40 transform scale-75 group-hover:scale-100 transition-transform">
                                <i class="fas fa-eye text-xs"></i>
                            </span>
                            <span>Detail Cepat</span>
                        </button>
                    </div>

                    <!-- Book Metadata -->
                    <div class="flex flex-col flex-grow justify-between">
                        <div>
                            <div class="text-[10px] uppercase font-bold tracking-wider text-blue-400 mb-1">
                                <?= htmlspecialchars($buku['Kategori'] ?: 'Umum'); ?>
                            </div>
                            <h3 class="text-sm font-bold text-white leading-snug line-clamp-2 mb-1 group-hover:text-blue-300 transition-colors">
                                <?= htmlspecialchars($buku['Judul']); ?>
                            </h3>
                            <p class="text-xs text-slate-400 mb-3 truncate">
                                <?= htmlspecialchars($buku['Penulis']); ?>
                            </p>
                        </div>

                        <!-- Action Button inside Card -->
                        <button type="button" onclick="openQuickView(<?= htmlspecialchars(json_encode($buku)); ?>)" class="w-full py-2 rounded-xl text-xs font-semibold text-slate-300 bg-white/5 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center gap-1.5">
                            <i class="fas fa-circle-info text-[10px]"></i>
                            <span>Lihat Sinopsis</span>
                        </button>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Bottom Teaser for Registration -->
            <div class="mt-14 text-center">
                <p class="text-xs text-slate-400 mb-3">Ingin meminjam buku di atas langsung hari ini?</p>
                <a href="register.php" class="inline-flex items-center gap-2 text-sm font-bold text-blue-400 hover:text-blue-300 border-b border-blue-400/40 hover:border-blue-300 pb-0.5 transition">
                    <span>Buat Akun Perpustakaan Gratis Dalam 1 Menit</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

        </div>
    </section>

    <!-- ============================================================
         ALUR PEMINJAMAN 3 LANGKAH (INTERACTIVE TIMELINE)
         ============================================================ -->
    <section id="alur" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-20 reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold uppercase tracking-widest mb-3">
                    Kemudahan Alur
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">
                    Tiga Langkah Menuju Literasi.
                </h2>
                <p class="text-slate-400 text-sm">
                    Sistem peminjaman terintegrasi tanpa antrean fisik dan bebas dokumen kertas.
                </p>
            </div>

            <!-- Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                
                <!-- Connector Line (Desktop) -->
                <div class="hidden md:block absolute top-14 left-1/6 right-1/6 h-[2px] bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-500 opacity-25 z-0"></div>

                <!-- Step 1 -->
                <div class="glass-card p-8 rounded-3xl reveal reveal-delay-1 relative z-10 text-center flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 p-[2px] mb-6 shadow-xl shadow-blue-500/20">
                        <div class="w-full h-full bg-navy-950 rounded-[14px] flex items-center justify-center text-blue-400 text-xl font-black">
                            01
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Pilih Buku Favorit</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        Cari judul, penulis, atau topik di katalog. Cek ketersediaan stok fisik di rak dan masukkan ke keranjang pilihan Anda.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card p-8 rounded-3xl reveal reveal-delay-2 relative z-10 text-center flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-500 p-[2px] mb-6 shadow-xl shadow-indigo-500/20">
                        <div class="w-full h-full bg-navy-950 rounded-[14px] flex items-center justify-center text-indigo-400 text-xl font-black">
                            02
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Konfirmasi Peminjaman</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        Ajukan peminjaman hingga 3 buku secara online. Sistem otomatis menerbitkan tanggal jatuh tempo 14 hari kerja.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card p-8 rounded-3xl reveal reveal-delay-3 relative z-10 text-center flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-[2px] mb-6 shadow-xl shadow-emerald-500/20">
                        <div class="w-full h-full bg-navy-950 rounded-[14px] flex items-center justify-center text-emerald-400 text-xl font-black">
                            03
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Nikmati & Beri Ulasan</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        Bawa pulang buku untuk dibaca. Setelah selesai dikembalikan, berikan rating bintang 1–5 dan ulasan untuk sesama pembaca.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         TESTIMONI ANGGOTA / WALL OF TRUST
         ============================================================ -->
    <section id="testimoni" class="py-24 relative z-10 bg-navy-900/40 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16 reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold uppercase tracking-widest mb-3">
                    Pengalaman Nyata
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">
                    Dipercaya Ribuan Pecinta Buku.
                </h2>
                <p class="text-slate-400 text-sm">
                    Inilah kata mereka tentang kemudahan sistem Digital Library.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Review 1 -->
                <div class="glass-card p-7 rounded-3xl reveal reveal-delay-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-300 text-sm leading-relaxed mb-6">
                            “Fitur cek stok realtime bener-bener ngebantu. Dulu sering ke perpus ternyata bukunya lagi dipinjam orang. Sekarang tinggal cek dari web, pas datang bukunya pasti ada!”
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-white/5">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white text-xs">
                            AS
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Aditya Saputra</div>
                            <div class="text-[11px] text-slate-400">Mahasiswa Teknik Informatika</div>
                        </div>
                    </div>
                </div>

                <!-- Review 2 -->
                <div class="glass-card p-7 rounded-3xl reveal reveal-delay-2 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-300 text-sm leading-relaxed mb-6">
                            “Tampilannya mewah dan modern banget! Kuota 3 buku dengan masa pinjam 14 hari pas banget buat ngabisin novel tebal. Ulasan dari anggota lain juga bikin gampang milih buku bagus.”
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-white/5">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-500 to-pink-600 flex items-center justify-center font-bold text-white text-xs">
                            NR
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Nadia Rahmadani</div>
                            <div class="text-[11px] text-slate-400">Pecinta Sastra & Fiksi</div>
                        </div>
                    </div>
                </div>

                <!-- Review 3 -->
                <div class="glass-card p-7 rounded-3xl reveal reveal-delay-3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-300 text-sm leading-relaxed mb-6">
                            “Sebagai pengajar, platform ini memudahkan saya mencari buku referensi kuliah. Gak perlu repot bawa kartu fisik atau isi buku tamu tebal.”
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-white/5">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center font-bold text-white text-xs">
                            BW
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Budi Wicaksono</div>
                            <div class="text-[11px] text-slate-400">Dosen & Peneliti</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         FAQ ACCORDION (INTERACTIVE)
         ============================================================ -->
    <section id="faq" class="py-24 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-16 reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-3">
                    Tanya Jawab
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">
                    Pertanyaan yang Sering Diajukan.
                </h2>
                <p class="text-slate-400 text-sm">
                    Punya pertanyaan seputar keanggotaan dan peminjaman? Temukan jawabannya di bawah.
                </p>
            </div>

            <!-- FAQ Items -->
            <div class="space-y-4">
                
                <!-- FAQ 1 -->
                <div class="glass-card rounded-2xl overflow-hidden reveal">
                    <button type="button" class="w-full px-6 py-5 text-left flex items-center justify-between text-sm font-bold text-white hover:text-blue-300 transition" onclick="toggleFaq(this)">
                        <span>Apakah pendaftaran anggota di Digital Library dipungut biaya?</span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div class="px-6 pb-5 text-xs sm:text-sm text-slate-400 leading-relaxed hidden">
                        Tidak ada biaya sama sekali. Seluruh anggota dapat mendaftar secara gratis dan langsung mendapatkan akses peminjaman katalog perpustakaan.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="glass-card rounded-2xl overflow-hidden reveal">
                    <button type="button" class="w-full px-6 py-5 text-left flex items-center justify-between text-sm font-bold text-white hover:text-blue-300 transition" onclick="toggleFaq(this)">
                        <span>Berapa batas maksimal buku yang dapat dipinjam secara bersamaan?</span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div class="px-6 pb-5 text-xs sm:text-sm text-slate-400 leading-relaxed hidden">
                        Setiap akun anggota memiliki kuota aktif maksimal 3 buku fisik secara bersamaan. Setelah Anda mengembalikan buku yang dipinjam, kuota Anda akan langsung kembali terisi otomatis.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="glass-card rounded-2xl overflow-hidden reveal">
                    <button type="button" class="w-full px-6 py-5 text-left flex items-center justify-between text-sm font-bold text-white hover:text-blue-300 transition" onclick="toggleFaq(this)">
                        <span>Berapa lama masa berlaku durasi peminjaman satu buku?</span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div class="px-6 pb-5 text-xs sm:text-sm text-slate-400 leading-relaxed hidden">
                        Durasi peminjaman standar adalah 14 hari kalender terhitung sejak tanggal persetujuan peminjaman. Tanggal jatuh tempo dapat dipantau di dashboard akun Anda.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="glass-card rounded-2xl overflow-hidden reveal">
                    <button type="button" class="w-full px-6 py-5 text-left flex items-center justify-between text-sm font-bold text-white hover:text-blue-300 transition" onclick="toggleFaq(this)">
                        <span>Bagaimana cara memberikan ulasan dan rating pada buku?</span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div class="px-6 pb-5 text-xs sm:text-sm text-slate-400 leading-relaxed hidden">
                        Setelah buku Anda kembalikan dan status peminjaman selesai, sistem akan menampilkan opsi "Beri Ulasan" di riwayat peminjaman akun Anda. Anda dapat memilih bintang 1–5 dan menuliskan ulasan Anda.
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         CTA PENUTUP (ULTRA-LUXURIOUS GRAND CARD)
         ============================================================ -->
    <section class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-3xl overflow-hidden p-8 sm:p-14 lg:p-16 border border-blue-500/30 shadow-2xl reveal">
                
                <!-- Background Deep Gradient & Lights -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900 via-indigo-950 to-navy-950 z-0"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/25 rounded-full blur-3xl pointer-events-none z-0"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none z-0"></div>

                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    
                    <div class="lg:col-span-8 text-left">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-bold uppercase tracking-wider backdrop-blur-md mb-4 border border-white/15">
                            <i class="fas fa-sparkles text-cyan-300"></i> Mulai Petualangan Literasi
                        </span>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-[1.15] mb-4">
                            Siap Menikmati Kemudahan Perpustakaan Modern?
                        </h2>
                        <p class="text-blue-100/80 text-sm sm:text-base leading-relaxed max-w-xl mb-8">
                            Daftarkan diri Anda hari ini. Temukan bacaan favorit Anda tanpa perlu mengisi formulir panjang atau mengantre di loket.
                        </p>
                        
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="register.php" class="px-7 py-3.5 rounded-xl bg-white text-navy-950 text-sm font-bold shadow-xl hover:bg-slate-100 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span>Daftar Gratis Sekarang</span>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                            <a href="login.php" class="px-6 py-3.5 rounded-xl bg-white/10 text-white border border-white/20 hover:bg-white/15 text-sm font-bold backdrop-blur-md transition">
                                <span>Masuk ke Akun</span>
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-4 hidden lg:flex justify-end">
                        <div class="glass-card p-6 rounded-2xl border border-white/20 text-white max-w-sm">
                            <i class="fas fa-quote-left text-blue-400 text-2xl mb-3 opacity-60"></i>
                            <p class="text-sm italic font-serif leading-relaxed mb-4 text-slate-200">
                                “Membaca adalah percakapan paling sunyi, dan paling bermakna dalam membuka cakrawala pemikiran.”
                            </p>
                            <div class="text-xs font-bold text-white">Pramoedya Ananta Toer</div>
                            <div class="text-[10px] text-blue-300">Sastrawan Indonesia</div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         FOOTER
         ============================================================ -->
    <footer class="border-t border-white/10 bg-navy-950/80 backdrop-blur-xl relative z-10 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                
                <!-- Brand Info -->
                <div class="flex items-center gap-3 mb-4">
                    <img src="logo/logo.png" alt="Digital Library" class="h-9 object-contain">
                <span class="text-base font-extrabold text-white tracking-tight">Digital<span class="text-blue-400">Library</span>
                </span>
                </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-md mb-6">
                        Platform sistem manajemen perpustakaan modern berbasis web, memberikan kemudahan akses katalog, peminjaman terstruktur, dan ulasan buku secara transparan dan efisien.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-emerald-400 font-semibold bg-emerald-500/10 px-3 py-1.5 rounded-lg w-fit border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                        <span>Seluruh Sistem Operasional Normal</span>
                    </div>
                </div>

                <!-- Navigasi Cepat -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Navigasi Halaman</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="#fitur" class="hover:text-blue-400 transition">Keunggulan Sistem</a></li>
                        <li><a href="#koleksi" class="hover:text-blue-400 transition">Katalog Pilihan</a></li>
                        <li><a href="#alur" class="hover:text-blue-400 transition">Alur Peminjaman</a></li>
                        <li><a href="#testimoni" class="hover:text-blue-400 transition">Ulasan Anggota</a></li>
                        <li><a href="#faq" class="hover:text-blue-400 transition">Pertanyaan Umum</a></li>
                    </ul>
                </div>

                <!-- Akses Akun -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Akses Pengguna</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="login.php" class="hover:text-blue-400 transition">Masuk Akun Member</a></li>
                        <li><a href="register.php" class="hover:text-blue-400 transition font-semibold text-blue-400">Daftar Akun Baru</a></li>
                        <li><a href="login.php" class="hover:text-blue-400 transition">Portal Administrator</a></li>
                        <li class="pt-2 text-[11px] text-slate-500">&copy; <?= date('Y'); ?> Digital Library &middot; All Rights Reserved.</li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Note -->
            <div class="pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-slate-500">
                <div>Dirancang dengan estetika modern, performa tinggi, dan standar kenyamanan literasi.</div>
                <div class="flex items-center gap-4">
                    <a href="#mainHeader" class="hover:text-slate-300 transition flex items-center gap-1">
                        <span>Kembali ke Atas</span>
                        <i class="fas fa-arrow-up text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============================================================
         QUICK VIEW MODAL (DETAIL SINOPSIS BUKU INTERAKTIF)
         ============================================================ -->
    <div id="quickViewModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-navy-950/80 backdrop-blur-md">
        <div class="glass-panel w-full max-w-2xl rounded-3xl overflow-hidden border border-white/20 shadow-2xl modal-enter" id="modalCard">
            <div class="relative p-6 sm:p-8">
                
                <!-- Close Button -->
                <button type="button" onclick="closeQuickView()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fas fa-times text-xs"></i>
                </button>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                    <!-- Book Cover -->
                    <div class="sm:col-span-5 aspect-[3/4] rounded-2xl overflow-hidden bg-navy-850 border border-white/10 shadow-lg relative">
                        <img id="modalCover" src="" alt="" class="w-full h-full object-cover">
                        <div id="modalFallback" class="hidden w-full h-full bg-gradient-to-br from-blue-700 to-navy-950 p-4 flex flex-col justify-between text-white">
                            <span id="modalCatFallback" class="text-[9px] uppercase tracking-widest font-bold text-cyan-300"></span>
                            <div id="modalTitleFallback" class="text-sm font-bold leading-tight"></div>
                            <span id="modalAuthorFallback" class="text-xs text-slate-300"></span>
                        </div>
                    </div>

                    <!-- Book Info -->
                    <div class="sm:col-span-7 flex flex-col justify-between">
                        <div>
                            <span id="modalCategory" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-500/30 mb-2"></span>
                            <h3 id="modalTitle" class="text-xl font-extrabold text-white leading-snug mb-1"></h3>
                            <p id="modalAuthor" class="text-xs text-slate-400 mb-4"></p>

                            <div class="grid grid-cols-2 gap-2 text-xs bg-navy-950/70 p-3 rounded-xl border border-white/5 mb-5">
                                <div>
                                    <span class="text-slate-500 block text-[10px]">Penerbit:</span>
                                    <span id="modalPublisher" class="font-semibold text-slate-200"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px]">Tahun Terbit:</span>
                                    <span id="modalYear" class="font-semibold text-slate-200"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px]">Nomor ISBN:</span>
                                    <span id="modalISBN" class="font-semibold text-slate-200"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px]">Stok Rak:</span>
                                    <span id="modalStock" class="font-bold text-emerald-400"></span>
                                </div>
                            </div>

                            <p class="text-xs text-slate-400 leading-relaxed mb-6">
                                Buku ini tersedia di koleksi fisik perpustakaan. Masuk ke akun Anda untuk langsung menambahkan ke keranjang dan mengajukan peminjaman 14 hari.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="login.php" class="btn-glow flex-1 py-3 rounded-xl text-xs font-bold text-white text-center flex items-center justify-center gap-2">
                                <i class="fas fa-hand-holding-hand"></i>
                                <span>Pinjam Buku Ini</span>
                            </a>
                            <button type="button" onclick="closeQuickView()" class="px-4 py-3 rounded-xl bg-white/5 text-slate-300 text-xs font-semibold hover:bg-white/10 transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================
         INTERACTIVE JAVASCRIPT: LUXURY ANIMATIONS, SPOTLIGHT & CANVAS
         ============================================================ -->
    <script>
    /* 1. Mouse Ambient Spotlight Effect */
    (function () {
        const spotlight = document.getElementById('cursorSpotlight');
        if (!spotlight) return;
        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let currentX = mouseX;
        let currentY = mouseY;

        window.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        }, { passive: true });

        function animateSpotlight() {
            currentX += (mouseX - currentX) * 0.08;
            currentY += (mouseY - currentY) * 0.08;
            spotlight.style.left = currentX + 'px';
            spotlight.style.top = currentY + 'px';
            requestAnimationFrame(animateSpotlight);
        }
        animateSpotlight();
    })();

    /* 2. Interactive 60fps Star/Particle Network Canvas */
    (function () {
        const canvas = document.getElementById('particleCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }, { passive: true });

        const particles = [];
        const particleCount = Math.min(Math.floor(width / 35), 45);

        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                radius: Math.random() * 1.5 + 0.5,
                alpha: Math.random() * 0.5 + 0.2
            });
        }

        function drawParticles() {
            ctx.clearRect(0, 0, width, height);

            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];
                p.x += p.vx;
                p.y += p.vy;

                if (p.x < 0) p.x = width;
                if (p.x > width) p.x = 0;
                if (p.y < 0) p.y = height;
                if (p.y > height) p.y = 0;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(147, 197, 253, ${p.alpha})`;
                ctx.fill();

                // Connect nearby particles with subtle glowing lines
                for (let j = i + 1; j < particles.length; j++) {
                    const p2 = particles[j];
                    const dx = p.x - p2.x;
                    const dy = p.y - p2.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < 110) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.strokeStyle = `rgba(96, 165, 250, ${(1 - dist / 110) * 0.12})`;
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(drawParticles);
        }
        drawParticles();
    })();

    /* 3. Dynamic 3D Tilt on Hover for Hero Book */
    (function () {
        const heroBook = document.getElementById('heroMainBook');
        if (!heroBook) return;

        heroBook.addEventListener('mousemove', (e) => {
            const rect = heroBook.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const rotateX = -(y / rect.height) * 22;
            const rotateY = (x / rect.width) * 22;

            heroBook.style.animation = 'none';
            heroBook.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
        });

        heroBook.addEventListener('mouseleave', () => {
            heroBook.style.transform = '';
            heroBook.style.animation = 'floatHero 7s ease-in-out infinite';
        });
    })();

    /* 4. Intersection Observer for Scroll Reveals */
    (function () {
        const reveals = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            reveals.forEach(el => el.classList.add('active'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        reveals.forEach(el => observer.observe(el));
    })();

    /* 5. Smooth Animated Number Counters */
    (function () {
        const counters = document.querySelectorAll('.counter-number');
        if (!counters.length) return;

        function animateCounter(el) {
            const target = parseInt(el.dataset.target || '0', 10);
            if (isNaN(target) || target <= 0) {
                el.textContent = '0';
                return;
            }

            const duration = 1600;
            const start = performance.now();

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out cubic
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(target * easeOut);

                el.textContent = current.toLocaleString('id-ID');

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            requestAnimationFrame(update);
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counters.forEach(c => observer.observe(c));
    })();

    /* 6. FAQ Accordion Toggle */
    function toggleFaq(button) {
        const content = button.nextElementSibling;
        const icon = button.querySelector('i');
        const isOpen = !content.classList.contains('hidden');

        // Close all other accordions
        document.querySelectorAll('#faq .hidden').forEach(el => {
            if (el !== content) el.classList.add('hidden');
        });
        document.querySelectorAll('#faq i').forEach(ic => {
            if (ic !== icon) ic.classList.remove('rotate-180');
        });

        if (isOpen) {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        } else {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        }
    }

    /* 7. Quick View Modal */
    function openQuickView(book) {
        const modal = document.getElementById('quickViewModal');
        const card = document.getElementById('modalCard');
        if (!modal || !book) return;

        document.getElementById('modalTitle').textContent = book.Judul || '-';
        document.getElementById('modalAuthor').textContent = 'Karya: ' + (book.Penulis || 'Tidak Diketahui');
        document.getElementById('modalCategory').textContent = book.Kategori || 'Umum';
        document.getElementById('modalPublisher').textContent = book.Penerbit || '-';
        document.getElementById('modalYear').textContent = book.TahunTerbit || '-';
        document.getElementById('modalISBN').textContent = book.ISBN || '-';
        document.getElementById('modalStock').textContent = (book.Stok || '0') + ' Eksemplar';

        const coverImg = document.getElementById('modalCover');
        const fallback = document.getElementById('modalFallback');

        if (book.Cover && book.Cover !== 'default.jpg') {
            coverImg.src = 'uploads/' + book.Cover;
            coverImg.classList.remove('hidden');
            fallback.classList.add('hidden');
        } else {
            coverImg.classList.add('hidden');
            fallback.classList.remove('hidden');
            document.getElementById('modalCatFallback').textContent = book.Kategori || 'Katalog';
            document.getElementById('modalTitleFallback').textContent = book.Judul || '';
            document.getElementById('modalAuthorFallback').textContent = book.Penulis || '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            card.classList.add('modal-active');
        }, 10);
    }

    function closeQuickView() {
        const modal = document.getElementById('quickViewModal');
        const card = document.getElementById('modalCard');
        if (!modal) return;

        card.classList.remove('modal-active');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    // Close modal on click backdrop
    document.getElementById('quickViewModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeQuickView();
    });

    // Close on Escape key
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeQuickView();
    });

    /* 8. Navbar Scroll Effect */
    window.addEventListener('scroll', () => {
        const header = document.getElementById('mainHeader');
        const container = document.getElementById('navContainer');
        if (window.scrollY > 30) {
            header.classList.add('py-2');
            header.classList.remove('py-4');
            container.classList.add('bg-navy-950/90', 'border-blue-500/20', 'shadow-blue-500/5');
        } else {
            header.classList.add('py-4');
            header.classList.remove('py-2');
            container.classList.remove('bg-navy-950/90', 'border-blue-500/20', 'shadow-blue-500/5');
        }
    }, { passive: true });
    </script>
</body>
</html>