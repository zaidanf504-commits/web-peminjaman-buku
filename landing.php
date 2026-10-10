<?php
session_start();

// Redirect jika user sudah login
if (isset($_SESSION['UserID'])) {
    header("Location: " . (($_SESSION['Role'] ?? '') === 'admin' ? 'admin_peminjaman.php' : 'index.php'));
    exit;
}

require 'koneksi.php';

// Fallback jika koneksi database gagal
if (!$koneksi || (isset($koneksi->connect_error) && $koneksi->connect_error)) {
    http_response_code(503);
    exit('
    <div style="font-family:system-ui;padding:3rem;text-align:center;color:#fff;background:#030303;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <h1 style="font-size:1.8rem;margin-bottom:0.5rem;color:#f87171;">Layanan Tidak Tersedia</h1>
        <p style="color:#9ca3af;max-width:400px;">Gagal terhubung ke database. Silakan periksa koneksi server Anda atau coba beberapa saat lagi.</p>
    </div>');
}

const UPLOAD_PATH = 'uploads/';

/**
 * Mengambil data statistik dan katalog buku dari database
 */
function fetchLandingData($koneksi): array
{
    $stats = [
        'total_buku'   => 0,
        'total_stok'   => 0,
        'total_user'   => 0,
        'total_pinjam' => 0
    ];
    $buku_showcase = [];

    // Query statistik agregat
    $sqlStats = "SELECT
        (SELECT COUNT(*) FROM buku) AS total_buku,
        (SELECT COALESCE(SUM(Stok),0) FROM buku) AS total_stok,
        (SELECT COUNT(*) FROM user) AS total_user,
        (SELECT COUNT(*) FROM peminjaman) AS total_pinjam";
        
    if ($res = $koneksi->query($sqlStats)) {
        if ($row = $res->fetch_assoc()) {
            foreach ($stats as $key => $val) {
                $stats[$key] = (int)($row[$key] ?? 0);
            }
        }
    }

    // Query katalog buku terbaru
    $stmt = $koneksi->prepare("SELECT BukuID, Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover FROM buku ORDER BY BukuID DESC LIMIT 8");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $buku_showcase[] = $row;
        }
        $stmt->close();
    }

    // Data cadangan jika database masih kosong
    $fallback = [
        ['BukuID' => 1, 'Judul' => 'Atomic Habits', 'Penulis' => 'James Clear', 'Kategori' => 'Pengembangan Diri', 'Stok' => 5, 'Cover' => null, 'TahunTerbit' => 2018, 'Penerbit' => 'Gramedia', 'ISBN' => '978-602-06-2631-4'],
        ['BukuID' => 2, 'Judul' => 'Filosofi Teras', 'Penulis' => 'Henry Manampiring', 'Kategori' => 'Filsafat', 'Stok' => 4, 'Cover' => null, 'TahunTerbit' => 2017, 'Penerbit' => 'Kompas', 'ISBN' => '978-602-412-518-9'],
        ['BukuID' => 3, 'Judul' => 'Janji', 'Penulis' => 'Tere Liye', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 3, 'Cover' => null, 'TahunTerbit' => 2021, 'Penerbit' => 'Republika', 'ISBN' => '978-623-96010-1-0'],
        ['BukuID' => 4, 'Judul' => 'Laut Bercerita', 'Penulis' => 'Leila S. Chudori', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 6, 'Cover' => null, 'TahunTerbit' => 2017, 'Penerbit' => 'KPG', 'ISBN' => '978-602-424-694-5'],
    ];

    if (empty($buku_showcase)) {
        $buku_showcase = $fallback;
    }

    return array_merge($stats, ['buku_showcase' => $buku_showcase]);
}

$data          = fetchLandingData($koneksi);
$total_buku    = $data['total_buku'];
$total_stok    = $data['total_stok'];
$total_user    = $data['total_user'];
$total_pinjam  = $data['total_pinjam'];
$buku_showcase = $data['buku_showcase'];

// Kategori unik untuk filter
$kategori_list = [];
foreach ($buku_showcase as $b) {
    $k = trim((string)($b['Kategori'] ?? ''));
    if ($k === '') $k = 'Umum';
    if (!in_array($k, $kategori_list, true)) {
        $kategori_list[] = $k;
    }
}
sort($kategori_list);

function esc($v): string { 
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); 
}

function bookCoverInner(array $buku): string
{
    $raw = (string)($buku['Cover'] ?? '');
    $hasFile = ($raw !== '' && $raw !== 'default.jpg' && file_exists(UPLOAD_PATH . $raw));
    $judul = esc($buku['Judul'] ?? 'Tanpa Judul');
    $penulis = esc($buku['Penulis'] ?? 'Anonim');
    $kat = esc(trim((string)($buku['Kategori'] ?? '')) === '' ? 'Umum' : $buku['Kategori']);

    if ($hasFile) {
        $src = UPLOAD_PATH . rawurlencode($raw);
        return '<img src="' . esc($src) . '" alt="' . $judul . '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out">';
    }

    return '<div class="w-full h-full flex flex-col justify-between p-5 bg-gradient-to-br from-neutral-900 via-neutral-800 to-black border border-white/10 group-hover:scale-[1.02] transition-transform duration-500">'
        . '<div class="flex items-center justify-between"><span class="text-[9px] font-bold uppercase tracking-[0.2em] text-cyan-400 line-clamp-1">' . $kat . '</span><i class="fas fa-book-open text-white/40 text-xs"></i></div>'
        . '<div><div class="text-[15px] font-serif italic text-white leading-tight line-clamp-3 font-serif-instrument">' . $judul . '</div>'
        . '<div class="mt-2 h-[1px] w-8 bg-cyan-500/50"></div>'
        . '<div class="text-[11px] text-neutral-400 mt-2 line-clamp-1">' . $penulis . '</div></div>'
        . '<div class="text-white/30 text-[8px] tracking-[0.25em] uppercase font-mono">Digital Library</div>'
        . '</div>';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Flowpath Library — Modern Knowledge Platform</title>
  <meta name="description" content="Platform perpustakaan digital modern dengan visual terintegrasi, katalog cerdas, dan pencatatan instan.">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <!-- Three.js for 3D -->
  <script type="importmap">
  {
    "imports": {
      "three": "https://unpkg.com/three@0.164.1/build/three.module.js",
      "three/addons/": "https://unpkg.com/three@0.164.1/examples/jsm/"
    }
  }
  </script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@300;400;500;600;700&display=swap');
    @import url('https://db.onlinewebfonts.com/c/08e020de1811ec4489f82d1247a42c09?family=Helvetica+Now+Text');

    * {
      box-sizing: border-box;
      font-family: "Helvetica Now Text", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      -webkit-font-smoothing: antialiased;
    }

    .font-serif-instrument {
      font-family: 'Instrument Serif', Georgia, serif;
    }

    /* Glassmorphism Liquid Glass Styles */
    .liquid-glass {
      background: rgba(255, 255, 255, 0.01);
      background-blend-mode: luminosity;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: none;
      box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.1);
      position: relative;
      overflow: hidden;
    }

    .liquid-glass::before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: inherit;
      padding: 1.4px;
      background: linear-gradient(180deg,
        rgba(255,255,255,0.45) 0%, rgba(255,255,255,0.15) 20%,
        rgba(255,255,255,0) 40%, rgba(255,255,255,0) 60%,
        rgba(255,255,255,0.15) 80%, rgba(255,255,255,0.45) 100%);
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
    }

    /* Animation Utilities */
    @keyframes dropdown-in {
      from { opacity: 0; transform: translateY(-4px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-dropdown { animation: dropdown-in 0.2s ease-out forwards; }
    .duration-400 { transition-duration: 400ms; }

    /* Text Reveal Animation - Staggered word/line reveal */
    .text-reveal {
      opacity: 0;
      transform: translateY(20px);
      display: inline-block;
      transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .text-reveal.revealed {
      opacity: 1;
      transform: translateY(0);
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #030303; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 999px; }

    /* Reveal Scroll Effect */
    .reveal-item {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal-item.revealed {
      opacity: 1;
      transform: translateY(0);
    }

    /* Infinite Marquee */
    .marquee-container {
      display: flex;
      overflow: hidden;
      user-select: none;
      mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
    }
    .marquee-track {
      display: flex;
      flex-shrink: 0;
      gap: 2rem;
      animation: marqueeScroll 30s linear infinite;
    }
    @keyframes marqueeScroll {
      from { transform: translateX(0); }
      to { transform: translateX(-100%); }
    }

    /* 3D Book Card Tilt Effect */
    .book-item {
      transform-style: preserve-3d;
      perspective: 1000px;
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s;
    }
    .book-item:hover {
      transform: translateY(-8px) rotateX(2deg) rotateY(-2deg);
      box-shadow: 
        0 20px 40px -15px rgba(0,0,0,0.5),
        0 8px 16px -8px rgba(6,182,212,0.15),
        inset 0 1px 1px rgba(255,255,255,0.1);
      border-color: rgba(6,182,212,0.3);
      z-index: 10;
    }
    .book-item .aspect-\[3\/4\] {
      transform-style: preserve-3d;
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .book-item:hover .aspect-\[3\/4\] {
      transform: translateZ(12px) scale(1.02);
    }
    .book-item .group-hover\:scale-105 {
      transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .book-item:hover .group-hover\:scale-105 {
      transform: scale(1.08) translateZ(4px);
    }

    /* Stat Card Shimmer */
    .stat-card {
      position: relative;
      overflow: hidden;
    }
    .stat-card::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(110deg, transparent 40%, rgba(6,182,212,0.08) 50%, transparent 60%);
      transform: translateX(-100%);
      transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
      pointer-events: none;
    }
    .stat-card:hover::before {
      transform: translateX(100%);
    }

    @media (prefers-reduced-motion: reduce) {
      .book-item,
      .book-item .aspect-\[3\/4\],
      .book-item .group-hover\:scale-105 {
        transition: none;
      }
      .book-item:hover {
        transform: translateY(-4px);
      }
      .stat-card::before {
        display: none;
      }
    }
  </style>
</head>
<body class="bg-[#030303] text-white overflow-x-hidden selection:bg-white selection:text-black">

  <!-- ==================== HERO SECTION (FULLSCREEN VIEWPORT) ==================== -->
  <section id="beranda" class="h-screen w-full overflow-hidden relative flex flex-col justify-between">
    
    <!-- Video Background & Dark Overlay -->
    <video 
      autoplay 
      loop 
      muted 
      playsinline 
      class="absolute inset-0 w-full h-full object-cover z-0"
      src="https://www.pexels.com/download/video/5363312/">
    </video>
    <div class="absolute inset-0 bg-black/40 z-0 pointer-events-none"></div>

    <!-- 3D Canvas Layer -->
    <div id="hero-canvas" class="absolute inset-0 z-5 pointer-events-none"></div>

    <!-- Header Navigation (Top, Responsive Padding) -->
    <header class="relative z-20 w-full px-5 sm:px-6 md:px-12 lg:px-16 py-4 sm:py-5 flex items-center justify-between">
      
      <!-- Logo: Inline Diamond SVG + Text -->
      <a href="#" class="flex items-center gap-3 group">
        <svg class="w-7 h-7 text-white" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 2L26 14L14 26L2 14L14 2Z" fill="white" fill-opacity="0.9"/>
          <path d="M14 6L22 14L14 22L6 14L14 6Z" fill="white" fill-opacity="0.5"/>
        </svg>
        <span class="text-white text-lg sm:text-xl font-medium tracking-tight">Digital Library</span>
      </a>

      <!-- Desktop Nav: setiap tautan mengarah ke section yang tersedia di landing page -->
      <nav class="hidden lg:flex items-center gap-8">
        <a href="#katalog" class="text-white/90 hover:text-white text-sm font-medium transition-colors">Produk</a>
        <a href="#statistik" class="text-white/90 hover:text-white text-sm font-medium transition-colors">Stastistik</a>
        <a href="#footer" class="text-white/90 hover:text-white text-sm font-medium transition-colors">Koleksi</a>
        <a href="#beranda" class="text-white/90 hover:text-white text-sm font-medium transition-colors">Beranda</a>
      </nav>

      <!-- Desktop CTA Buttons -->
      <div class="hidden lg:flex items-center gap-4">
        <a href="login.php" class="text-white/90 hover:text-white text-sm font-medium transition-colors">
          Log in
        </a>
        <a href="register.php" class="liquid-glass rounded-full px-5 py-2 text-white text-sm font-medium hover:bg-white/10 transition-colors">
          Try it free
        </a>
      </div>

      <!-- Mobile Menu Button -->
      <button id="mobileMenuBtn" aria-label="Toggle Navigation" class="lg:hidden text-white p-2 focus:outline-none z-30">
        <i id="mobileMenuIcon" class="fas fa-bars text-xl transition-all duration-300 transform"></i>
      </button>

      <!-- Mobile Menu Slide-down Drawer -->
      <div id="mobileMenu" class="hidden absolute top-full left-5 right-5 mt-2 bg-[#2C221C]/95 backdrop-blur-xl rounded-2xl p-6 transition-all duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] z-40 border border-white/10 shadow-2xl">
        <div class="flex flex-col gap-4">
          <div class="border-b border-white/10 pb-3">
            <span class="text-xs uppercase font-bold tracking-wider text-white/50 block mb-2">Navigasi</span>
            <a href="#beranda" class="block py-1.5 text-white/90 text-sm hover:text-white">Beranda</a>
            <a href="#katalog" class="block py-1.5 text-white/90 text-sm hover:text-white">Produk / Katalog Buku</a>
            <a href="#statistik" class="block py-1.5 text-white/90 text-sm hover:text-white">Solusi / Statistik</a>
          </div>
          
          <div class="border-b border-white/10 pb-3">
            <span class="text-xs uppercase font-bold tracking-wider text-white/50 block mb-2">Informasi</span>
            <a href="#footer" class="block py-1.5 text-white/90 text-sm hover:text-white">Tentang Digital Library</a>
          </div>

          <div class="pt-2 flex flex-col gap-2">
            <a href="login.php" class="w-full text-center py-2.5 text-white text-sm font-medium border border-white/20 rounded-xl">Log in</a>
            <a href="register.php" class="w-full text-center py-2.5 bg-white text-black text-sm font-semibold rounded-xl hover:bg-white/90">Try it free</a>
          </div>
        </div>
      </div>

    </header>

    <!-- Hero Content (Top-Aligned, Clean Spacing) -->
    <div class="relative z-10 flex-1 flex items-start justify-center pt-12 sm:pt-16 md:pt-20 lg:pt-24 px-4">
      <div class="text-center max-w-3xl mx-auto">
        
        <!-- Headline -->
        <h1 class="text-white text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl leading-[1.05] tracking-[-0.02em] font-medium">
          <span class="text-reveal">Buku adalah jendela</span><br>
          <span class="text-reveal" style="transition-delay: 0.08s;">dunia.</span> <span class="text-white/60 text-reveal" style="transition-delay: 0.16s;">Books are a window to</span><br>
          <span class="text-white/60 text-reveal" style="transition-delay: 0.24s;">the world.</span>
        </h1>

        <!-- Subheading -->
        <p class="text-white/80 text-sm sm:text-base md:text-lg leading-relaxed max-w-md mx-auto mt-6 sm:mt-8 font-normal text-reveal" style="transition-delay: 0.32s;">
          Temukan ribuan judul, pinjam dengan satu sentuhan, dan bangun kebiasaan membaca yang berkelanjutan.
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 mt-6 sm:mt-8 text-reveal" style="transition-delay: 0.4s;">
          <a href="register.php" class="px-5 sm:px-6 py-2.5 sm:py-3 bg-white text-gray-900 text-sm font-semibold rounded-full hover:bg-white/90 transition-all shadow-lg hover:scale-105 active:scale-95">
            Mulai Jelajahi
          </a>
          <a href="#katalog" class="px-5 sm:px-6 py-2.5 sm:py-3 liquid-glass rounded-full text-white text-sm font-semibold hover:bg-white/10 transition-all">
            Lihat Katalog
          </a>
        </div>

      </div>
    </div>

    <!-- Scroll Bottom Bar Helper -->
    <div class="relative z-10 pb-6 text-center">
      <a href="#statistik" class="text-white/40 hover:text-white text-xs uppercase tracking-widest transition-colors font-mono inline-flex items-center gap-2">
        <span>Scroll Down</span>
        <i class="fas fa-chevron-down text-[10px] animate-bounce"></i>
      </a>
    </div>

  </section>


  <!-- ==================== INFINITE MARQUEE TICKER ==================== -->
  <div class="relative z-20 py-4 bg-white/[0.02] border-y border-white/10 overflow-hidden">
    <div class="marquee-container">
      <div class="marquee-track">
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Sastra & Fiksi</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Pengembangan Diri</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Sains & Teknologi</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Filsafat Modern</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Bisnis & Ekonomi</span>
      </div>
      <div class="marquee-track" aria-hidden="true">
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Sastra & Fiksi</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Pengembangan Diri</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Sains & Teknologi</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Filsafat Modern</span>
        <span class="text-xs uppercase tracking-[0.25em] text-white/60 font-mono flex items-center gap-3"><i class="fas fa-star text-white/40 text-[10px]"></i> Bisnis & Ekonomi</span>
      </div>
    </div>
  </div>


  <!-- ==================== SECTION STATISTIK ==================== -->
  <section id="statistik" class="relative z-20 py-24 px-5 sm:px-8 max-w-6xl mx-auto">
    <div class="text-center mb-16 reveal-item">
      <span class="text-xs uppercase tracking-[0.25em] text-white/50 font-mono block mb-2">Statistik Live</span>
      <h2 class="font-serif-instrument text-4xl sm:text-5xl text-white">Ekosistem Literasi Terintegrasi</h2>
      <p class="text-white/60 text-sm max-w-md mx-auto mt-2">Pencatatan real-time untuk peminjaman, koleksi buku, dan pengguna aktif.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
      <?php
      $statsGrid = [
        ['label' => 'Judul Buku', 'value' => $total_buku, 'suffix' => '+', 'icon' => 'fa-book'],
        ['label' => 'Stok Fisik', 'value' => $total_stok, 'suffix' => ' Eks', 'icon' => 'fa-layer-group'],
        ['label' => 'Anggota Aktif', 'value' => $total_user, 'suffix' => ' User', 'icon' => 'fa-users'],
        ['label' => 'Total Transaksi', 'value' => $total_pinjam, 'suffix' => ' x', 'icon' => 'fa-arrow-right-arrow-left'],
      ];
      foreach ($statsGrid as $st):
      ?>
      <div class="stat-card liquid-glass rounded-2xl p-6 text-center border border-white/10 hover:border-white/30 transition-all reveal-item">
        <i class="fas <?= $st['icon']; ?> text-white/40 text-lg mb-3"></i>
        <div class="text-3xl sm:text-4xl font-semibold text-white tracking-tight mb-1">
          <span class="stat-counter" data-target="<?= (int)$st['value']; ?>">0</span><?= $st['suffix']; ?>
        </div>
        <div class="text-xs text-white/60 uppercase font-mono tracking-wider"><?= esc($st['label']); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>


  <!-- ==================== SECTION KATALOG BUKU ==================== -->
  <section id="katalog" class="relative z-20 py-24 px-5 sm:px-8 border-t border-white/10 bg-black/40">
    <div class="max-w-6xl mx-auto">
      
      <!-- Catalog Header & Search Filter Bar -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 reveal-item">
        <div>
          <span class="text-xs uppercase tracking-[0.25em] text-white/50 font-mono block mb-2">Eksplorasi Katalog</span>
          <h2 class="font-serif-instrument text-4xl sm:text-5xl text-white">Koleksi Terbaru</h2>
        </div>

        <!-- Filter Search Bar -->
        <div class="w-full md:w-80">
          <div class="liquid-glass rounded-full px-4 py-2.5 flex items-center gap-2 border border-white/15">
            <i class="fas fa-search text-white/40 text-xs"></i>
            <input 
              type="text" 
              id="catalogSearch" 
              placeholder="Cari judul atau penulis..." 
              class="bg-transparent text-white text-xs placeholder:text-white/40 outline-none w-full"
            />
          </div>
        </div>
      </div>

      <!-- Kategori Pills -->
      <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-8 reveal-item" id="kategoriFilter">
        <button class="filter-pill active liquid-glass px-4 py-2 rounded-full text-xs text-white bg-white/20 border-white/30 font-medium shrink-0" data-filter="all">
          Semua
        </button>
        <?php foreach ($kategori_list as $kat): ?>
        <button class="filter-pill liquid-glass px-4 py-2 rounded-full text-xs text-white/70 hover:text-white font-medium shrink-0" data-filter="<?= esc(mb_strtolower($kat)); ?>">
          <?= esc($kat); ?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Book Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5" id="bookGrid">
        <?php foreach ($buku_showcase as $buku):
          $stok = (int)($buku['Stok'] ?? 0);
          $katRaw = trim((string)($buku['Kategori'] ?? '')) === '' ? 'Umum' : $buku['Kategori'];
        ?>
        <div class="book-item reveal-item liquid-glass rounded-2xl overflow-hidden group cursor-pointer flex flex-col justify-between border border-white/10 hover:border-white/30 transition-all"
          data-title="<?= esc(mb_strtolower($buku['Judul'] . ' ' . $buku['Penulis'])); ?>"
          data-cat="<?= esc(mb_strtolower($katRaw)); ?>"
          data-judul="<?= esc($buku['Judul']); ?>"
          data-penulis="<?= esc($buku['Penulis']); ?>"
          data-kategori="<?= esc($katRaw); ?>"
          data-penerbit="<?= esc($buku['Penerbit'] ?? '-'); ?>"
          data-tahun="<?= esc($buku['TahunTerbit'] ?? '-'); ?>"
          data-isbn="<?= esc($buku['ISBN'] ?? '-'); ?>"
          data-stok="<?= $stok; ?>">
          
          <div class="aspect-[3/4] relative w-full overflow-hidden bg-black/60">
            <?= bookCoverInner($buku); ?>
            <div class="absolute top-3 right-3">
              <span class="liquid-glass px-2.5 py-1 rounded-full text-[10px] font-medium text-white/80 bg-black/50 border border-white/20">
                <?= $stok; ?> Stok
              </span>
            </div>
          </div>

          <div class="p-4 flex-1 flex flex-col justify-between border-t border-white/5">
            <div>
              <span class="text-[9px] uppercase font-bold tracking-widest text-white/40 block mb-1 font-mono"><?= esc($katRaw); ?></span>
              <h3 class="text-sm font-medium text-white group-hover:text-white/80 transition-colors line-clamp-2 leading-snug">
                <?= esc($buku['Judul']); ?>
              </h3>
              <p class="text-xs text-white/50 line-clamp-1 mt-1 font-light"><?= esc($buku['Penulis']); ?></p>
            </div>
          </div>

        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>


  <!-- ==================== FOOTER ==================== -->
  <footer id="footer" class="relative z-20 bg-black border-t border-white/10 py-12 px-5 sm:px-8">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6 text-xs text-white/50 font-mono">
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-white" viewBox="0 0 28 28" fill="none">
          <path d="M14 2L26 14L14 26L2 14L14 2Z" fill="white" fill-opacity="0.9"/>
        </svg>
        <span class="text-white font-medium tracking-tight text-sm">Digital Library</span>
        <span>&copy; <?= date('Y'); ?> Digital Library. All rights reserved.</span>
      </div>
      <div class="flex items-center gap-6">
        <a href="login.php" class="hover:text-white transition-colors">Log In</a>
        <a href="register.php" class="hover:text-white transition-colors">Daftar Akun</a>
        <a href="#katalog" class="hover:text-white transition-colors">Katalog</a>
      </div>
    </div>
  </footer>


  <!-- ==================== JAVASCRIPT ==================== -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      
      // 1. Mobile Menu Toggle
      const mobileBtn = document.getElementById('mobileMenuBtn');
      const mobileMenu = document.getElementById('mobileMenu');
      const mobileIcon = document.getElementById('mobileMenuIcon');

      if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function () {
          const isOpen = !mobileMenu.classList.contains('hidden');
          if (isOpen) {
            mobileMenu.classList.add('hidden');
            mobileIcon.className = 'fas fa-bars text-xl';
          } else {
            mobileMenu.classList.remove('hidden');
            mobileIcon.className = 'fas fa-xmark text-xl';
          }
        });

        mobileMenu.querySelectorAll('a[href^="#"]').forEach(link => {
          link.addEventListener('click', function () {
            mobileMenu.classList.add('hidden');
            mobileIcon.className = 'fas fa-bars text-xl';
          });
        });
      }

      // Hero Text Reveal - Staggered animation on load
      const heroTextElements = document.querySelectorAll('#beranda .text-reveal');
      heroTextElements.forEach((el, i) => {
        setTimeout(() => el.classList.add('revealed'), 100 + i * 80);
      });

      // 2. Scroll Reveal Observer
      const revealItems = document.querySelectorAll('.reveal-item');
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('revealed');
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.1 });
        revealItems.forEach(el => observer.observe(el));
      } else {
        revealItems.forEach(el => el.classList.add('revealed'));
      }

      // 3. Counter Animation
      const counters = document.querySelectorAll('.stat-counter');
      counters.forEach(counter => {
        const target = parseInt(counter.dataset.target || '0', 10);
        let current = 0;
        const step = Math.max(1, Math.floor(target / 40));
        const timer = setInterval(() => {
          current += step;
          if (current >= target) {
            counter.textContent = target.toLocaleString('id-ID');
            clearInterval(timer);
          } else {
            counter.textContent = current.toLocaleString('id-ID');
          }
        }, 30);
      });

      // 4. Catalog Live Search & Kategori Filter
      const catalogSearch = document.getElementById('catalogSearch');
      const filterBtns = document.querySelectorAll('#kategoriFilter .filter-pill');
      const bookItems = document.querySelectorAll('#bookGrid .book-item');
      let activeFilter = 'all';

      function applyCatalogFilter() {
        const query = (catalogSearch ? catalogSearch.value : '').toLowerCase().trim();
        bookItems.forEach(item => {
          const cat = item.dataset.cat || '';
          const title = item.dataset.title || '';
          const matchCat = (activeFilter === 'all') || (cat === activeFilter);
          const matchQuery = !query || (title.indexOf(query) !== -1);

          if (matchCat && matchQuery) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      }

      filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
          filterBtns.forEach(b => {
            b.classList.remove('active', 'bg-white/20', 'border-white/30', 'text-white');
            b.classList.add('text-white/70');
          });
          btn.classList.add('active', 'bg-white/20', 'border-white/30', 'text-white');
          btn.classList.remove('text-white/70');
          activeFilter = btn.dataset.filter;
          applyCatalogFilter();
        });
      });

      if (catalogSearch) {
        catalogSearch.addEventListener('input', applyCatalogFilter);
      }

      // Book Card Mouse Glow Effect
      const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (!prefersReduced) {
        bookItems.forEach(card => {
          const cover = card.querySelector('.aspect-\\[3\\/4\\]');
          if (!cover) return;

          card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const cx = rect.width / 2;
            const cy = rect.height / 2;
            const dx = (x - cx) / cx;
            const dy = (y - cy) / cy;

            // Subtle glow following mouse
            const glowX = 50 + dx * 30;
            const glowY = 50 + dy * 30;
            cover.style.background = `radial-gradient(ellipse ${glowX}% ${glowY}% at ${glowX}% ${glowY}%, rgba(6,182,212,0.08) 0%, transparent 60%), var(--card-bg, transparent)`;
          });

          card.addEventListener('mouseleave', () => {
            cover.style.background = '';
          });
        });
      }

      // 5. Three.js 3D Hero Background
      initHero3D();
    });

    // Three.js 3D Scene - Subtle floating geometry
    async function initHero3D() {
      const container = document.getElementById('hero-canvas');
      if (!container) return;

      try {
        const { default: THREE } = await import('three');

        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 100);
        camera.position.set(0, 0, 35);

        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setClearColor(0x000000, 0);
        container.appendChild(renderer.domElement);

        // Color palette - dark theme with cyan accents
        const colors = [
          new THREE.Color(0x06b6d4), // cyan-500
          new THREE.Color(0x0891b2), // cyan-600
          new THREE.Color(0x155e75), // cyan-800
          new THREE.Color(0xffffff), // white
        ];

        // Geometry: Low-poly book-like shapes + floating particles
        const geometries = [
          new THREE.BoxGeometry(1.2, 1.8, 0.3, 2, 2, 1),  // book
          new THREE.BoxGeometry(1, 1.5, 0.25, 2, 2, 1),   // small book
          new THREE.BoxGeometry(1.4, 2, 0.35, 2, 2, 1),   // large book
          new THREE.OctahedronGeometry(0.6, 0),           // crystal
          new THREE.TetrahedronGeometry(0.5, 0),          // pyramid
        ];

        const materials = colors.map(c => new THREE.MeshPhysicalMaterial({
          color: c,
          metalness: 0.15,
          roughness: 0.85,
          transmission: 0.1,
          thickness: 0.5,
          transparent: true,
          opacity: 0.7,
          side: THREE.DoubleSide,
        }));

        // Add subtle wireframe material for variety
        const wireMaterial = new THREE.MeshBasicMaterial({
          color: 0x06b6d4,
          wireframe: true,
          transparent: true,
          opacity: 0.12,
        });

        const meshes = [];
        const meshCount = 28;

        for (let i = 0; i < meshCount; i++) {
          const geo = geometries[Math.floor(Math.random() * geometries.length)];
          const isWire = Math.random() < 0.15;
          const mat = isWire ? wireMaterial : materials[Math.floor(Math.random() * materials.length)];
          
          const mesh = new THREE.Mesh(geo, mat);
          
          // Position in a spherical cloud
          const radius = 12 + Math.random() * 18;
          const theta = Math.random() * Math.PI * 2;
          const phi = Math.acos(2 * Math.random() - 1);
          
          mesh.position.set(
            radius * Math.sin(phi) * Math.cos(theta),
            radius * Math.sin(phi) * Math.sin(theta) * 0.6,
            radius * Math.cos(phi) - 20
          );
          
          // Random rotation
          mesh.rotation.set(
            Math.random() * Math.PI,
            Math.random() * Math.PI,
            Math.random() * Math.PI
          );
          
          // Animation properties
          mesh.userData = {
            basePos: mesh.position.clone(),
            rotSpeed: new THREE.Vector3(
              (Math.random() - 0.5) * 0.0008,
              (Math.random() - 0.5) * 0.0012,
              (Math.random() - 0.5) * 0.0005
            ),
            floatOffset: Math.random() * Math.PI * 2,
            floatSpeed: 0.0003 + Math.random() * 0.0004,
            floatAmplitude: 0.3 + Math.random() * 0.5,
            delay: Math.random() * 1000,
          };
          
          scene.add(mesh);
          meshes.push(mesh);
        }

        // Floating particles (smaller, more numerous)
        const particleCount = 180;
        const particleGeo = new THREE.BufferGeometry();
        const particlePositions = new Float32Array(particleCount * 3);
        const particleSizes = new Float32Array(particleCount);
        const particleAlphas = new Float32Array(particleCount);
        const particlePhases = new Float32Array(particleCount);

        for (let i = 0; i < particleCount; i++) {
          const radius = 8 + Math.random() * 28;
          const theta = Math.random() * Math.PI * 2;
          const phi = Math.acos(2 * Math.random() - 1);
          
          particlePositions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
          particlePositions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta) * 0.5;
          particlePositions[i * 3 + 2] = radius * Math.cos(phi) - 20;
          particleSizes[i] = 0.5 + Math.random() * 1.5;
          particleAlphas[i] = 0.15 + Math.random() * 0.35;
          particlePhases[i] = Math.random() * Math.PI * 2;
        }

        particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
        particleGeo.setAttribute('size', new THREE.BufferAttribute(particleSizes, 1));
        particleGeo.setAttribute('alpha', new THREE.BufferAttribute(particleAlphas, 1));
        particleGeo.setAttribute('phase', new THREE.BufferAttribute(particlePhases, 1));

        const particleMat = new THREE.PointsMaterial({
          color: 0x06b6d4,
          size: 1,
          transparent: true,
          opacity: 1,
          vertexColors: false,
          sizeAttenuation: true,
          depthWrite: false,
        });

        const particles = new THREE.Points(particleGeo, particleMat);
        scene.add(particles);

        // Subtle ambient light
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);

        // Directional light for rim highlights
        const dirLight = new THREE.DirectionalLight(0x06b6d4, 0.3);
        dirLight.position.set(5, 10, 8);
        scene.add(dirLight);

        const dirLight2 = new THREE.DirectionalLight(0xffffff, 0.2);
        dirLight2.position.set(-5, -5, -5);
        scene.add(dirLight2);

        // Mouse parallax
        let mouseX = 0, mouseY = 0;
        let targetX = 0, targetY = 0;

        document.addEventListener('mousemove', (e) => {
          mouseX = (e.clientX / window.innerWidth - 0.5) * 1.2;
          mouseY = (e.clientY / window.innerHeight - 0.5) * 1.2;
        });

        // Touch support
        document.addEventListener('touchmove', (e) => {
          if (e.touches[0]) {
            mouseX = (e.touches[0].clientX / window.innerWidth - 0.5) * 1.2;
            mouseY = (e.touches[0].clientY / window.innerHeight - 0.5) * 1.2;
          }
        }, { passive: true });

        // Reduce motion
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Animation loop
        let time = 0;
        function animate() {
          requestAnimationFrame(animate);
          time += 0.016;

          if (!prefersReduced) {
            // Smooth mouse follow
            targetX += (mouseX - targetX) * 0.02;
            targetY += (mouseY - targetY) * 0.02;

            // Animate meshes
            meshes.forEach((mesh, i) => {
              const ud = mesh.userData;
              const t = time + ud.delay * 0.001;

              // Gentle floating
              mesh.position.y = ud.basePos.y + Math.sin(t * ud.floatSpeed) * ud.floatAmplitude;
              mesh.position.x = ud.basePos.x + Math.cos(t * ud.floatSpeed * 0.7) * ud.floatAmplitude * 0.5;

              // Continuous rotation
              mesh.rotation.x += ud.rotSpeed.x;
              mesh.rotation.y += ud.rotSpeed.y;
              mesh.rotation.z += ud.rotSpeed.z;

              // Subtle mouse parallax
              mesh.position.x += targetX * (i % 3 + 1) * 0.15;
              mesh.position.y -= targetY * (i % 3 + 1) * 0.15;
            });

            // Animate particles
            const posAttr = particles.geometry.attributes.position;
            const phaseAttr = particles.geometry.attributes.phase;
            
            for (let i = 0; i < particleCount; i++) {
              const phase = phaseAttr.getX(i);
              const t = time + phase;
              
              posAttr.setY(i, posAttr.getY(i) + Math.sin(t * 0.0008) * 0.02);
              posAttr.setX(i, posAttr.getX(i) + Math.cos(t * 0.0006) * 0.015);
              posAttr.setZ(i, posAttr.getZ(i) + Math.sin(t * 0.0005) * 0.01);
              
              // Mouse influence on particles
              posAttr.setX(i, posAttr.getX(i) + targetX * 0.3);
              posAttr.setY(i, posAttr.getY(i) - targetY * 0.3);
            }
            posAttr.needsUpdate = true;

            // Slow camera drift
            camera.position.x += (targetX * 0.8 - camera.position.x) * 0.008;
            camera.position.y += (-targetY * 0.8 - camera.position.y) * 0.008;
            camera.lookAt(0, 0, -20);
          }

          renderer.render(scene, camera);
        }

        animate();

        // Handle resize
        window.addEventListener('resize', () => {
          camera.aspect = window.innerWidth / window.innerHeight;
          camera.updateProjectionMatrix();
          renderer.setSize(window.innerWidth, window.innerHeight);
          renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        });

        // Performance: pause when tab hidden
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) {
            renderer.setAnimationLoop(null);
          } else {
            renderer.setAnimationLoop(animate);
          }
        });

      } catch (e) {
        console.warn('3D scene failed to initialize:', e);
        // Fallback: subtle CSS animation on container
        container.style.background = 'radial-gradient(ellipse at center, rgba(6,182,212,0.03) 0%, transparent 70%)';
      }
    }
  </script>
</body>
</html>
