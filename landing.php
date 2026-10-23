<?php
session_start();

// Redirect jika user sudah login
if (isset($_SESSION['UserID'])) {
    header("Location: " . (($_SESSION['Role'] ?? '') === 'admin' ? 'admin_peminjaman.php' : 'index.php'));
    exit;
}

require 'koneksi.php';

// Fallback jika koneksi database gagal (Palet Dark Blueprint #070D1E & Cobalt Brand #0052CC)
if (!$koneksi || (isset($koneksi->connect_error) && $koneksi->connect_error)) {
    http_response_code(503);
    exit('
    <div style="font-family:\'Space Mono\',monospace;padding:3rem;text-align:center;color:#FFFFFF;background:#070D1E;background-image:radial-gradient(circle at 50% 35%, rgba(29,78,216,0.16), transparent 65%),linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);background-size:100% 100%, 36px 36px, 36px 36px;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;border:1px solid rgba(255,255,255,0.1);">
        <div style="padding:0.35rem 0.85rem;border:1px solid rgba(147,197,253,0.35);background:#0F172A;border-radius:6px;font-size:0.75rem;letter-spacing:0.2em;color:#93C5FD;text-transform:uppercase;margin-bottom:1.25rem;">
            [ SYS_ERR // 503_SERVICE_UNAVAILABLE ]
        </div>
        <h1 style="font-size:2.15rem;margin-bottom:0.75rem;font-weight:400;font-family:\'Newsreader\',Georgia,serif;color:#FFFFFF;letter-spacing:-0.01em;">
            Digital Library — BIBLIOTHECA Tidak Tersedia
        </h1>
        <p style="color:#CBD5E1;max-width:460px;font-size:0.875rem;line-height:1.7;font-family:\'Plus Jakarta Sans\',\'Inter\',sans-serif;margin-bottom:1.75rem;">
            Gagal terhubung ke pangkalan data utama perpustakaan. Silakan periksa konfigurasi server basis data Anda atau muat ulang halaman beberapa saat lagi.
        </p>
        <a href="landing.php" style="display:inline-block;padding:0.75rem 1.5rem;background:#0052CC;color:#FFFFFF;text-decoration:none;font-family:\'Plus Jakarta Sans\',\'Inter\',sans-serif;font-size:0.75rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;border-radius:6px;border:1px solid #1D4ED8;">
            Muat Ulang Koneksi
        </a>
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
        'total_pinjam' => 0,
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
        ['BukuID' => 1, 'Judul' => 'Atomic Habits', 'Penulis' => 'James Clear', 'Kategori' => 'Pengembangan Diri', 'Stok' => 5, 'Cover' => null, 'TahunTerbit' => 2018, 'Penerbit' => 'Gramedia Pustaka', 'ISBN' => '978-602-06-2631-4'],
        ['BukuID' => 2, 'Judul' => 'Filosofi Teras', 'Penulis' => 'Henry Manampiring', 'Kategori' => 'Filsafat', 'Stok' => 4, 'Cover' => null, 'TahunTerbit' => 2018, 'Penerbit' => 'Kompas Penerbit', 'ISBN' => '978-602-412-518-9'],
        ['BukuID' => 3, 'Judul' => 'Janji', 'Penulis' => 'Tere Liye', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 3, 'Cover' => null, 'TahunTerbit' => 2021, 'Penerbit' => 'Sabak Grip', 'ISBN' => '978-623-96010-1-0'],
        ['BukuID' => 4, 'Judul' => 'Laut Bercerita', 'Penulis' => 'Leila S. Chudori', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 6, 'Cover' => null, 'TahunTerbit' => 2017, 'Penerbit' => 'KPG', 'ISBN' => '978-602-424-694-5'],
        ['BukuID' => 5, 'Judul' => 'Bumi Manusia', 'Penulis' => 'Pramoedya Ananta Toer', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 2, 'Cover' => null, 'TahunTerbit' => 1980, 'Penerbit' => 'Lentera Dipantara', 'ISBN' => '978-979-97312-3-4'],
        ['BukuID' => 6, 'Judul' => 'Sapiens: Riwayat Singkat', 'Penulis' => 'Yuval Noah Harari', 'Kategori' => 'Sejarah & Sains', 'Stok' => 7, 'Cover' => null, 'TahunTerbit' => 2014, 'Penerbit' => 'KPG', 'ISBN' => '978-602-424-416-3'],
        ['BukuID' => 7, 'Judul' => 'Madilog', 'Penulis' => 'Tan Malaka', 'Kategori' => 'Filsafat', 'Stok' => 0, 'Cover' => null, 'TahunTerbit' => 1943, 'Penerbit' => 'Narasi', 'ISBN' => '978-979-168-372-2'],
        ['BukuID' => 8, 'Judul' => 'Cantik Itu Luka', 'Penulis' => 'Eka Kurniawan', 'Kategori' => 'Sastra & Fiksi', 'Stok' => 5, 'Cover' => null, 'TahunTerbit' => 2002, 'Penerbit' => 'Gramedia Pustaka', 'ISBN' => '978-602-03-1258-3'],
    ];

    if (empty($buku_showcase)) {
        $buku_showcase = $fallback;
        if ($stats['total_buku'] === 0) {
            $stats = ['total_buku' => 1420, 'total_stok' => 4890, 'total_user' => 850, 'total_pinjam' => 12340];
        }
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
    if ($k === '') {
        $k = 'Umum';
    }
    if (!in_array($k, $kategori_list, true)) {
        $kategori_list[] = $k;
    }
}
sort($kategori_list);

function esc($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Render sampul buku dengan palet Dark Blueprint (#070D1E, #0F172A, #172554, #1E293B)
 * serta aksen Gold Foil (#D4AF37) dan Light Blue (#93C5FD) tanpa mengubah kontrak fungsi.
 */
function bookCoverInner(array $buku, int $index): string
{
    $raw     = (string)($buku['Cover'] ?? '');
    $hasFile = ($raw !== '' && $raw !== 'default.jpg' && file_exists(UPLOAD_PATH . $raw));
    $judul   = esc($buku['Judul'] ?? 'Tanpa Judul');
    $penulis = esc($buku['Penulis'] ?? 'Anonim');
    $kat     = esc(trim((string)($buku['Kategori'] ?? '')) === '' ? 'Umum' : $buku['Kategori']);
    $tahunRaw = trim((string)($buku['TahunTerbit'] ?? ''));
    $tahun   = $tahunRaw !== '' ? esc($tahunRaw) : 'ARSIP';

    $palettes = [
        ['bg' => '#070D1E', 'accent' => '#D4AF37', 'sub' => '#93C5FD', 'border' => '#172554'],
        ['bg' => '#0F172A', 'accent' => '#93C5FD', 'sub' => '#D4AF37', 'border' => '#1E293B'],
        ['bg' => '#172554', 'accent' => '#D4AF37', 'sub' => '#CBD5E1', 'border' => '#1D4ED8'],
        ['bg' => '#1E293B', 'accent' => '#93C5FD', 'sub' => '#D4AF37', 'border' => '#0F172A'],
    ];
    $p      = $palettes[$index % count($palettes)];
    $volNum = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);

    if ($hasFile) {
        $src = UPLOAD_PATH . rawurlencode($raw);
        return '<img src="' . esc($src) . '" alt="Sampul ' . $judul . '" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 ease-out">';
    }

    return '<div class="w-full h-full flex flex-col justify-between p-5 relative select-none overflow-hidden" style="background-color:' . $p['bg'] . '; border: 1px solid ' . $p['border'] . ';">'
        . '<div class="absolute inset-2.5 pointer-events-none" style="border: 1px solid rgba(212, 175, 55, 0.28);"></div>'
        . '<div class="absolute left-3.5 top-0 bottom-0 w-[1px]" style="background: rgba(255, 255, 255, 0.12);"></div>'
        . '<div class="absolute left-0 top-0 bottom-0 w-3.5" style="background: linear-gradient(90deg, rgba(7,13,30,0.65) 0%, rgba(255,255,255,0.04) 85%, transparent 100%);"></div>'
        . '<div class="pl-3.5 pr-1 pt-1 flex items-center justify-between border-b pb-2.5 relative z-10" style="border-color: rgba(255,255,255,0.1);">'
        . '<span class="font-mono text-[9px] uppercase tracking-[0.16em] truncate max-w-[125px]" style="color:' . $p['accent'] . ';">' . $kat . '</span>'
        . '<span class="font-mono text-[9px] tnum" style="color:' . $p['sub'] . ';">' . $tahun . '</span>'
        . '</div>'
        . '<div class="pl-3.5 pr-1 my-auto py-3 relative z-10">'
        . '<div class="w-6 h-[1px] mb-3" style="background-color:' . $p['accent'] . ';"></div>'
        . '<h4 class="font-editorial text-lg sm:text-xl text-white leading-[1.2] line-clamp-3 italic font-normal tracking-tight">' . $judul . '</h4>'
        . '<p class="text-[10px] text-[#CBD5E1] mt-2.5 font-sans tracking-[0.12em] uppercase line-clamp-1">' . $penulis . '</p>'
        . '</div>'
        . '<div class="pl-3.5 pr-1 pt-2.5 border-t flex items-center justify-between text-[9px] font-mono uppercase tracking-[0.18em] relative z-10 tnum" style="border-color: rgba(255,255,255,0.1); color: #CBD5E1;">'
        . '<span>BIBLIOTHECA</span>'
        . '<span style="color:' . $p['accent'] . ';">VOL.' . $volNum . '</span>'
        . '</div>'
        . '</div>';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Digital Library — BIBLIOTHECA | Arsip &amp; Sirkulasi Literatur</title>
  <meta name="description" content="Digital Library — BIBLIOTHECA. Sistem informasi arsip akademik dan sirkulasi peminjaman literatur fisik dengan eksplorasi buku 3D interaktif.">

  <!-- Progressive Enhancement Flag: Menjamin konten tetap terlihat apabila JavaScript gagal dimuat -->
  <script>document.documentElement.classList.add('js-enabled');</script>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts: Newsreader, Plus Jakarta Sans, Inter, Space Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

  <!-- Three.js Importmap -->
  <script type="importmap">
  {
    "imports": {
      "three": "https://unpkg.com/three@0.164.1/build/three.module.js"
    }
  }
  </script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            blueprint: {
              950: '#070D1E',
              900: '#0F172A',
              800: '#172554',
              700: '#1E293B',
            },
            cobalt: {
              DEFAULT: '#0052CC',
              interaction: '#1D4ED8',
              light: '#93C5FD',
            },
            editorial: {
              bg: '#F4F5F7',
              card: '#FFFFFF',
              primary: '#0F172A',
              ink: '#172B4D',
              secondary: '#42526E',
              border: '#DFE1E6',
            },
            gold: {
              DEFAULT: '#D4AF37',
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', '"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
            editorial: ['"Newsreader"', 'Georgia', '"Times New Roman"', 'serif'],
            mono: ['"Space Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
          }
        }
      }
    }
  </script>

  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    html, body {
      background-color: #070D1E;
      color: #FFFFFF;
      overflow-x: hidden;
      font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif;
    }

    /* Tabular numerals wajib untuk angka statistik dan indeks */
    .tnum {
      font-variant-numeric: tabular-nums;
      font-feature-settings: "tnum" 1, "zero" 1;
    }

    /* Latar Blueprint Arsitektural dengan Radial Glow Cobalt Berintensitas Rendah */
    .blueprint-stage-surface {
      background-color: #070D1E;
      background-image:
        radial-gradient(circle at 74% 34%, rgba(29, 78, 216, 0.14), transparent 54%),
        radial-gradient(circle at 18% 76%, rgba(0, 82, 204, 0.08), transparent 48%),
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
      background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
    }

    /* Status Stok sesuai Spesifikasi Identitas Visual */
    .stock-badge-available {
      background-color: #E3FCEF;
      color: #006644;
      border: 1px solid #ABF5D1;
    }
    .stock-badge-unavailable {
      background-color: #FFEBE6;
      color: #BF2600;
      border: 1px solid #FFBDAD;
    }

    /* Panggung WebGL 3D */
    #webgl-stage {
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100vh;
      z-index: 1;
      pointer-events: none;
    }

    /* Fallback Buku Arsitektural Statis (Aktif saat WebGL nonaktif / mode statis) */
    #webgl-static-fallback {
      transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Progress Bar Gulir di Puncak Layar */
    #scroll-progress {
      transform-origin: left center;
      transform: scaleX(0);
      will-change: transform;
    }

    /* Scroll Reveal dengan Kurva Transisi Utama cubic-bezier(0.16, 1, 0.3, 1) */
    .reveal-up {
      opacity: 1;
      transform: translateY(0);
    }
    .js-enabled .reveal-up {
      opacity: 0;
      transform: translateY(22px);
      transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .js-enabled .reveal-up.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* Interaksi Hover Kartu Katalog: Prioritaskan Border, Underline, dan Panah Kecil */
    .book-card {
      border-radius: 8px;
      transition: border-color 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .book-card:hover,
    .book-card:focus-visible {
      border-color: #0052CC;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px -8px rgba(15, 23, 42, 0.12);
    }
    .book-card-title {
      text-decoration: underline;
      text-decoration-color: transparent;
      text-underline-offset: 4px;
      transition: color 0.2s ease, text-decoration-color 0.2s ease;
    }
    .book-card:hover .book-card-title,
    .book-card:focus-visible .book-card-title {
      color: #0052CC;
      text-decoration-color: #0052CC;
    }
    .book-card-arrow {
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .book-card:hover .book-card-arrow,
    .book-card:focus-visible .book-card-arrow {
      transform: translateX(4px);
    }

    /* Efek Punggung Buku Halus pada Sampul 3:4 */
    .book-spine-surface {
      box-shadow:
        inset 5px 0 10px -3px rgba(7, 13, 30, 0.65),
        inset -1px 0 2px rgba(255, 255, 255, 0.14),
        0 6px 14px -6px rgba(15, 23, 42, 0.28);
    }

    /* Slide-Over Book Inspection Drawer */
    #inspectionDrawer {
      transform: translateX(100%);
      transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1);
      will-change: transform;
    }
    #inspectionDrawer.is-open {
      transform: translateX(0);
    }
    #drawerBackdrop {
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.32s cubic-bezier(0.16, 1, 0.3, 1);
    }
    #drawerBackdrop.is-open {
      opacity: 1;
      pointer-events: auto;
    }

    /* Focus Indicator Aksesibilitas */
    :focus-visible {
      outline: 2px solid #93C5FD;
      outline-offset: 2px;
    }
    #katalog :focus-visible,
    #inspectionDrawer :focus-visible {
      outline: 2px solid #0052CC;
      outline-offset: 2px;
    }

    /* Hormati prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
      html {
        scroll-behavior: auto !important;
      }
      .js-enabled .reveal-up {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
      }
      .book-card,
      .book-card-arrow,
      #inspectionDrawer,
      #drawerBackdrop {
        transition-duration: 0.01ms !important;
      }
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #070D1E; }
    ::-webkit-scrollbar-thumb { background: #1E293B; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #0052CC; }
  </style>
</head>
<body class="blueprint-stage-surface selection:bg-[#0052CC] selection:text-white">

  <!-- Skip Link Aksesibilitas Keyboard -->
  <a href="#katalog" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:px-4 focus:py-2 focus:rounded-md focus:bg-[#0052CC] focus:text-white focus:font-mono focus:text-xs">
    Langsung ke Katalog Buku
  </a>

  <!-- ==================== TOP SCROLL PROGRESS BAR ==================== -->
  <div class="fixed top-0 left-0 right-0 h-[2px] bg-[#1E293B] z-[60]" aria-hidden="true">
    <div id="scroll-progress" class="h-full bg-[#0052CC] w-full"></div>
  </div>

  <!-- ==================== PANGGUNG WEBGL 3D & FALLBACK STATIS ==================== -->
  <div id="webgl-stage" aria-hidden="true">
    <!-- Komposisi Fallback Buku Arsitektural HTML/CSS (Tampil saat Reduced Motion / WebGL Fallback) -->
    <div id="webgl-static-fallback" class="hidden w-full h-full items-center justify-end pr-8 lg:pr-20 pointer-events-none">
      <div class="w-64 sm:w-72 aspect-[3/4] rounded-[6px] bg-[#0F172A] border border-[#172554] p-6 flex flex-col justify-between relative shadow-2xl">
        <div class="absolute inset-3 border border-[#D4AF37]/40 pointer-events-none"></div>
        <div class="flex items-center justify-between border-b border-white/10 pb-3 font-mono text-[10px] text-[#93C5FD] tracking-[0.18em]">
          <span>VOL. I</span>
          <span>ID // ARCHIVE</span>
        </div>
        <div class="my-auto py-4">
          <div class="w-8 h-[1px] bg-[#D4AF37] mb-3"></div>
          <div class="font-editorial italic text-2xl text-white tracking-tight">BIBLIOTHECA</div>
          <div class="font-mono text-[10px] text-[#D4AF37] tracking-[0.22em] mt-1">ARCHIVE EDITION</div>
        </div>
        <div class="border-t border-white/10 pt-3 flex items-center justify-between font-mono text-[9px] text-[#CBD5E1] tracking-[0.16em]">
          <span>STATIC BLUEPRINT</span>
          <span class="text-[#D4AF37]">CODEX 01</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Kontrol Mode Kualitas Visual & Telemetri Tahap Scroll (Kanan Bawah Desktop) -->
  <div class="fixed bottom-4 right-4 z-30 hidden md:flex items-center gap-2 bg-[#0F172A]/95 px-3 py-1.5 rounded-[6px] border border-white/10">
    <span id="scene-phase-label" class="font-mono text-[10px] uppercase tracking-[0.14em] text-[#CBD5E1] tnum pr-2 border-r border-white/10">
      STAGE 01 // HERO TOME
    </span>
    <label for="qualityModeSelect" class="sr-only">Mode Kualitas Visual 3D</label>
    <select id="qualityModeSelect" class="bg-transparent font-mono text-[10px] uppercase tracking-[0.12em] text-[#93C5FD] focus:outline-none cursor-pointer">
      <option value="standard" class="bg-[#0F172A] text-white">RENDER: STANDAR</option>
      <option value="high" class="bg-[#0F172A] text-white">RENDER: TINGGI</option>
      <option value="reduced" class="bg-[#0F172A] text-white">RENDER: STATIS</option>
    </select>
  </div>

  <!-- ==================== 09. HEADER NAVIGASI ==================== -->
  <header id="mainHeader" class="fixed top-0 left-0 right-0 z-40 bg-[#070D1E]/90 backdrop-blur-sm border-b border-white/10">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 h-16 sm:h-20 flex items-center justify-between gap-4">

      <!-- Identitas Brand BIBLIOTHECA -->
      <a href="#beranda" class="flex items-center gap-3.5 group shrink-0">
        <img src="logo/logo.png" alt="Logo Digital Library" class="w-9 h-9 object-contain" />
        <div class="flex flex-col">
          <div class="flex items-center gap-2">
            <span class="font-sans text-sm font-bold tracking-tight text-white leading-none">Digital Library</span>
            <span class="font-mono text-[10px] tracking-[0.16em] text-[#93C5FD] uppercase hidden sm:inline">// DIGITAL LIBRARY</span>
          </div>
        </div>
      </a>

      <!-- Navigasi Utama -->
      <nav aria-label="Navigasi Utama" class="hidden lg:flex items-center gap-7">
        <a href="#beranda" class="font-mono text-xs uppercase tracking-[0.14em] text-[#CBD5E1] hover:text-white transition-colors">
          <span class="text-[#93C5FD]">01.</span> Beranda
        </a>
        <a href="#cara-kerja" class="font-mono text-xs uppercase tracking-[0.14em] text-[#CBD5E1] hover:text-white transition-colors">
          <span class="text-[#93C5FD]">02.</span> Sirkulasi
        </a>
        <a href="#statistik" class="font-mono text-xs uppercase tracking-[0.14em] text-[#CBD5E1] hover:text-white transition-colors">
          <span class="text-[#93C5FD]">03.</span> Indeks Data
        </a>
        <a href="#katalog" class="font-mono text-xs uppercase tracking-[0.14em] text-[#CBD5E1] hover:text-white transition-colors">
          <span class="text-[#93C5FD]">04.</span> Katalog
        </a>
      </nav>

      <!-- CTA Masuk & Daftar -->
      <div class="hidden lg:flex items-center gap-3 shrink-0">
        <a href="login.php" class="px-4 py-2 rounded-[6px] border border-white/15 font-mono text-xs uppercase tracking-wider text-white hover:border-[#93C5FD] transition-colors">
          Masuk
        </a>
        <a href="register.php" class="px-5 py-2.5 rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white font-sans text-xs font-semibold tracking-wide transition-colors">
          Daftar
        </a>
      </div>

      <!-- Tombol Menu Mobile -->
      <button id="mobileMenuBtn" type="button" aria-expanded="false" aria-controls="mobileMenu" aria-label="Buka Menu Navigasi" class="lg:hidden px-3.5 py-2 rounded-[6px] bg-[#0F172A] border border-white/15 font-mono text-[11px] uppercase tracking-widest text-white">
        MENU
      </button>
    </div>

    <!-- Drawer Navigasi Mobile -->
    <nav id="mobileMenu" aria-label="Navigasi Mobile" class="hidden lg:hidden bg-[#070D1E] border-b border-white/10 px-5 py-6">
      <div class="flex flex-col gap-2 font-mono text-xs uppercase tracking-widest">
        <div class="pb-3 mb-1 border-b border-white/10 flex items-center justify-between text-[10px] text-[#CBD5E1] tnum">
          <span>ID // ARCHIVE</span>
          <span class="text-[#D4AF37]">MAKS 3 BUKU / 14 HARI</span>
        </div>
        <a href="#beranda" class="py-2.5 text-white border-b border-white/10 flex justify-between">
          <span>01. Beranda</span><span class="text-[#93C5FD]">&rarr;</span>
        </a>
        <a href="#cara-kerja" class="py-2.5 text-white border-b border-white/10 flex justify-between">
          <span>02. Sirkulasi</span><span class="text-[#93C5FD]">&rarr;</span>
        </a>
        <a href="#statistik" class="py-2.5 text-white border-b border-white/10 flex justify-between">
          <span>03. Indeks Data</span><span class="text-[#93C5FD]">&rarr;</span>
        </a>
        <a href="#katalog" class="py-2.5 text-white border-b border-white/10 flex justify-between">
          <span>04. Katalog</span><span class="text-[#93C5FD]">&rarr;</span>
        </a>
        <div class="pt-4 grid grid-cols-2 gap-3 font-sans">
          <a href="login.php" class="py-3 text-center rounded-[6px] border border-white/15 text-white text-xs font-semibold">
            Masuk
          </a>
          <a href="register.php" class="py-3 text-center rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white text-xs font-semibold">
            Daftar
          </a>
        </div>
      </div>
    </nav>
  </header>


  <!-- ==================== MAIN CONTENT WRAPPER ==================== -->
  <main class="relative z-10">

    <!-- ==================== STAGE 1: HERO SECTION (0–25%) ==================== -->
    <section id="beranda" data-stage-section="hero" class="min-h-screen pt-24 sm:pt-28 pb-14 px-5 sm:px-8 max-w-7xl mx-auto flex flex-col justify-between">

      <!-- Grid Asimetris Hero: Kiri Tipografi & CTA, Kanan Panggung Buku 3D -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center my-auto py-10 lg:py-16">

        <!-- Kolom Kiri: Tipografi & CTA -->
        <div class="lg:col-span-7 flex flex-col gap-4 lg:gap-6 max-w-2xl">

          <h1 class="font-editorial text-4xl sm:text-6xl lg:text-[4.15rem] text-white leading-[1.06] tracking-[-0.015em] font-normal reveal-up" style="transition-delay: 60ms;">
            Arsitektur literatur dan <span class="italic font-normal text-[#93C5FD]">pencatatan sirkulasi</span> pustaka.
          </h1>

          <p class="font-sans text-[#CBD5E1] text-base sm:text-lg leading-relaxed max-w-xl font-normal reveal-up" style="transition-delay: 120ms;">
            <strong class="text-white font-semibold">Digital Library — BIBLIOTHECA</strong> mengintegrasikan katalog bibliografi fisik, pemantauan ketersediaan stok eksemplar dari pangkalan data, dan manajemen peminjaman anggota dalam satu ruang arsip terstruktur.
          </p>

          <!-- Tombol Aksi Utama (Tidak Tertutup Canvas & Aksesibel via Keyboard) -->
          <div class="flex flex-wrap items-center gap-4 pt-2 reveal-up" style="transition-delay: 180ms;">
            <a href="#katalog" class="px-6 py-3.5 rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white font-sans text-sm font-semibold tracking-wide transition-colors inline-flex items-center gap-3">
              <span>Telusuri Katalog</span>
              <span class="font-mono text-xs text-[#93C5FD]">[04] &darr;</span>
            </a>
            <a href="login.php" class="px-6 py-3.5 rounded-[6px] bg-[#0F172A] border border-white/15 text-white font-sans text-sm font-medium hover:border-[#93C5FD] transition-colors inline-flex items-center gap-2.5">
              <span>Masuk Akun</span>
              <span class="font-mono text-xs text-[#D4AF37]">&rarr;</span>
            </a>
            <a href="register.php" class="px-5 py-3.5 rounded-[6px] border border-white/10 text-[#CBD5E1] hover:text-white hover:border-white/25 font-mono text-xs uppercase tracking-wider transition-colors">
              Daftar Anggota
            </a>
          </div>

          <!-- Ringkasan Parameter Sistem dengan Kode Arsip -->
          <div class="pt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl border-t border-white/10 reveal-up tnum" style="transition-delay: 240ms;">
            <div class="p-3.5 rounded-[6px] bg-[#0F172A] border border-white/10">
              <div class="font-mono text-[10px] tracking-[0.18em] text-[#93C5FD] uppercase">IDX-01 // CATALOG</div>
              <div class="font-sans text-xs font-semibold text-white mt-1">Inspeksi Bibliografi</div>
              <div class="font-sans text-[11px] text-[#CBD5E1] mt-0.5">Detail penerbit, tahun terbit, ISBN, dan klasifikasi rak.</div>
            </div>
            <div class="p-3.5 rounded-[6px] bg-[#0F172A] border border-white/10">
              <div class="font-mono text-[10px] tracking-[0.18em] text-[#D4AF37] uppercase">IDX-02 // QUOTA</div>
              <div class="font-sans text-xs font-semibold text-white mt-1">Maks 3 Buku / 14 Hari</div>
              <div class="font-sans text-[11px] text-[#CBD5E1] mt-0.5">Ketentuan standar peminjaman pada akun anggota.</div>
            </div>
            <div class="p-3.5 rounded-[6px] bg-[#0F172A] border border-white/10">
              <div class="font-mono text-[10px] tracking-[0.18em] text-[#93C5FD] uppercase">IDX-03 // STOCK</div>
              <div class="font-sans text-xs font-semibold text-white mt-1">Status Stok Terdata</div>
              <div class="font-sans text-[11px] text-[#CBD5E1] mt-0.5">Indikator ketersediaan eksemplar langsung dari tabel buku.</div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Ruang Panggung Buku Utama 3D & Keterangan Instalasi -->
        <div class="lg:col-span-5 min-h-[280px] sm:min-h-[380px] lg:min-h-[500px] relative flex flex-col justify-end items-start lg:items-end pointer-events-none">
          <div class="bg-[#0F172A]/90 border border-white/15 rounded-[8px] p-4 max-w-xs reveal-up">
            <div class="flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.18em] pb-2 mb-2 border-b border-white/10">
              <span class="text-[#D4AF37]">VOL. I // HERO TOME</span>
              <span class="text-[#93C5FD]">ID // ARCHIVE</span>
            </div>
            <p class="font-sans text-xs text-[#CBD5E1] leading-relaxed">
              Instalasi buku hardcover <em>BIBLIOTHECA — ARCHIVE</em> merespons progres gulir halaman dari pembukaan lembaran hingga penutupan sampul.
            </p>
            <div class="mt-3 pt-2 border-t border-white/10 flex items-center justify-between font-mono text-[10px] text-[#CBD5E1] uppercase tracking-widest tnum">
              <span>PROGRES DOKUMEN</span>
              <span id="liveScrollPct" class="text-white">00%</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Baris Navigasi Bawah Hero -->
      <div class="flex items-center justify-between pt-5 border-t border-white/10 font-mono text-[11px] text-[#CBD5E1] uppercase tracking-[0.18em]">
        <span>01 // BERANDA ARSIP</span>
        <a href="#cara-kerja" class="text-[#93C5FD] hover:text-white transition-colors inline-flex items-center gap-2">
          <span>02. PROTOKOL SIRKULASI</span>
          <span>&darr;</span>
        </a>
      </div>

    </section>


    <!-- ==================== STAGE 2: CIRCULATION SECTION (25–55%) ==================== -->
    <section id="cara-kerja" data-stage-section="circulation" class="py-24 sm:py-28 px-5 sm:px-8 max-w-7xl mx-auto border-t border-white/10">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">

        <!-- Kolom Kiri Sticky: Ringkasan Protokol Sirkulasi -->
        <div class="lg:col-span-5 lg:sticky lg:top-28 space-y-5 reveal-up bg-[#0F172A] p-7 rounded-[8px] border border-white/10">
          <div class="flex items-center justify-between font-mono text-[11px] uppercase tracking-[0.18em] text-[#93C5FD] pb-3 border-b border-white/10">
            <span>02 // PROTOKOL SIRKULASI</span>
            <span class="text-[#D4AF37]">VOL. I</span>
          </div>
          <h2 class="font-editorial text-3xl sm:text-5xl text-white leading-[1.1] font-normal">
            Alur peminjaman yang terukur dan terdokumentasi.
          </h2>
          <p class="font-sans text-[#CBD5E1] text-sm sm:text-base leading-relaxed">
            Setiap tahapan dirancang agar anggota dapat memeriksa metadata buku di katalog publik sebelum melakukan peminjaman melalui portal akun terautentikasi.
          </p>
          <div class="p-4 rounded-[6px] bg-[#070D1E] border border-white/10 font-mono text-[11px] text-[#CBD5E1] space-y-2 tnum">
            <div class="text-[#D4AF37] uppercase tracking-wider">ATURAN PEMINJAMAN TERVERIFIKASI:</div>
            <div class="flex justify-between border-b border-white/5 pb-1.5">
              <span>Batas Pinjam Aktif:</span>
              <span class="text-white">Maksimal 3 Buku</span>
            </div>
            <div class="flex justify-between border-b border-white/5 pb-1.5">
              <span>Durasi Peminjaman:</span>
              <span class="text-white">14 Hari Kalender</span>
            </div>
            <div class="flex justify-between">
              <span>Akses Transaksi:</span>
              <span class="text-[#93C5FD]">Wajib Login Anggota</span>
            </div>
          </div>
          <div class="pt-1">
            <a href="register.php" class="inline-flex items-center gap-2.5 font-mono text-xs uppercase tracking-[0.16em] text-[#D4AF37] hover:text-white transition-colors">
              <span>Daftar Akun Anggota</span>
              <span>&rarr;</span>
            </a>
          </div>
        </div>

        <!-- Kolom Kanan: Tiga Tahapan Sirkulasi -->
        <div class="lg:col-span-7 space-y-6">

          <!-- Tahap 01 -->
          <div class="p-7 sm:p-8 bg-[#0F172A] rounded-[8px] border border-white/10 hover:border-[#0052CC] transition-colors reveal-up">
            <div class="flex flex-wrap items-center justify-between gap-2 font-mono text-xs mb-5 pb-4 border-b border-white/10 tnum">
              <span class="px-2.5 py-1 rounded-[6px] bg-[#172554] text-[#93C5FD] border border-[#1D4ED8]/40">IDX-01 // AUTH</span>
              <span class="text-[#CBD5E1] uppercase tracking-[0.16em]">OTENTIKASI ANGGOTA</span>
            </div>
            <h3 class="font-editorial text-2xl sm:text-3xl text-white mb-3 font-normal">
              Masuk atau Registrasi Akun Pustaka
            </h3>
            <p class="font-sans text-[#CBD5E1] text-sm sm:text-base leading-relaxed">
              Gunakan halaman <a href="login.php" class="text-[#93C5FD] underline underline-offset-4 hover:text-white">login.php</a> untuk masuk ke dashboard anggota atau <a href="register.php" class="text-[#93C5FD] underline underline-offset-4 hover:text-white">register.php</a> untuk mendaftarkan keanggotaan baru. Sesi yang aktif secara otomatis mengarahkan pengguna ke ruang kerja yang sesuai.
            </p>
          </div>

          <!-- Tahap 02 -->
          <div class="p-7 sm:p-8 bg-[#0F172A] rounded-[8px] border border-white/10 hover:border-[#0052CC] transition-colors reveal-up">
            <div class="flex flex-wrap items-center justify-between gap-2 font-mono text-xs mb-5 pb-4 border-b border-white/10 tnum">
              <span class="px-2.5 py-1 rounded-[6px] bg-[#172554] text-[#D4AF37] border border-[#D4AF37]/40">IDX-02 // INSPECT</span>
              <span class="text-[#CBD5E1] uppercase tracking-[0.16em]">INSPEKSI METADATA &amp; STOK</span>
            </div>
            <h3 class="font-editorial text-2xl sm:text-3xl text-white mb-3 font-normal">
              Periksa Ketersediaan dan Bibliografi Buku
            </h3>
            <p class="font-sans text-[#CBD5E1] text-sm sm:text-base leading-relaxed">
              Cari koleksi menggunakan pintasan tombol <kbd class="font-mono text-xs text-[#93C5FD] bg-[#070D1E] px-1.5 py-0.5 rounded border border-white/15">/</kbd> atau saring berdasarkan kategori. Pilih kartu buku untuk membuka panel inspeksi yang memuat penerbit, tahun terbit, ISBN, dan jumlah stok aktual.
            </p>
          </div>

          <!-- Tahap 03 -->
          <div class="p-7 sm:p-8 bg-[#0F172A] rounded-[8px] border border-white/10 hover:border-[#0052CC] transition-colors reveal-up">
            <div class="flex flex-wrap items-center justify-between gap-2 font-mono text-xs mb-5 pb-4 border-b border-white/10 tnum">
              <span class="px-2.5 py-1 rounded-[6px] bg-[#172554] text-[#93C5FD] border border-[#1D4ED8]/40">IDX-03 // BORROW</span>
              <span class="text-[#CBD5E1] uppercase tracking-[0.16em]">SIRKULASI 14 HARI</span>
            </div>
            <h3 class="font-editorial text-2xl sm:text-3xl text-white mb-3 font-normal">
              Ajukan Peminjaman Melalui Dashboard
            </h3>
            <p class="font-sans text-[#CBD5E1] text-sm sm:text-base leading-relaxed">
              Setelah masuk melalui portal anggota, lakukan peminjaman pada buku yang berstatus tersedia dengan batas kuota maksimal <strong class="text-white font-semibold">3 buku aktif</strong> dan masa pinjam <strong class="text-white font-semibold">14 hari</strong>.
            </p>
          </div>

        </div>
      </div>
    </section>


    <!-- ==================== STAGE 3: ARCHIVE STATISTICS LEDGER (55–80%) ==================== -->
    <section id="statistik" data-stage-section="statistics" class="py-24 px-5 sm:px-8 max-w-7xl mx-auto">
      <div class="bg-[#0F172A]/95 rounded-[8px] border border-white/10 p-7 sm:p-12">

        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 pb-6 border-b border-white/10 gap-4 reveal-up">
          <div>
            <span class="font-mono text-xs uppercase tracking-[0.2em] text-[#93C5FD] block mb-2">03 // INDEKS DATA PUSTAKA</span>
            <h2 class="font-editorial text-3xl sm:text-5xl text-white font-normal">Ledger Statistik Arsip</h2>
          </div>
          <div class="font-mono text-[11px] text-[#CBD5E1] max-w-sm md:text-right tnum">
            <span class="text-[#D4AF37]">ID // ARCHIVE:</span> Data agregat bersumber langsung dari tabel <code class="text-white">buku</code>, <code class="text-white">user</code>, dan <code class="text-white">peminjaman</code>.
          </div>
        </div>

        <!-- Empat Indeks Statistik Aktual (Tanpa Persentase Kapasitas Fiktif & Ramah Pembaca Layar) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <?php
          $statsLedger = [
              [
                  'code'  => 'IDX-01',
                  'label' => 'Total Buku',
                  'value' => $total_buku,
                  'unit'  => 'JUDUL TERDATA',
                  'desc'  => 'Jumlah entri judul buku di dalam katalog perpustakaan.',
              ],
              [
                  'code'  => 'IDX-02',
                  'label' => 'Total Stok',
                  'value' => $total_stok,
                  'unit'  => 'EKSEMPLAR FISIK',
                  'desc'  => 'Akumulasi seluruh unit eksemplar pada tabel buku.',
              ],
              [
                  'code'  => 'IDX-03',
                  'label' => 'Total Pengguna',
                  'value' => $total_user,
                  'unit'  => 'AKUN TERDAFTAR',
                  'desc'  => 'Jumlah pengguna yang tercatat pada sistem perpustakaan.',
              ],
              [
                  'code'  => 'IDX-04',
                  'label' => 'Total Peminjaman',
                  'value' => $total_pinjam,
                  'unit'  => 'REKAMAN TRANSAKSI',
                  'desc'  => 'Jumlah seluruh riwayat transaksi pada tabel peminjaman.',
              ],
          ];
          foreach ($statsLedger as $idx => $st):
              $formattedVal = number_format((int)$st['value'], 0, ',', '.');
          ?>
          <div class="p-6 rounded-[8px] bg-[#070D1E] border border-white/10 flex flex-col justify-between reveal-up" style="transition-delay: <?= $idx * 60; ?>ms;">
            <div>
              <div class="flex items-center justify-between font-mono text-[11px] text-[#CBD5E1] pb-3 mb-4 border-b border-white/10 tnum">
                <span class="text-[#D4AF37] font-bold"><?= esc($st['code']); ?></span>
                <span class="px-2 py-0.5 rounded-[4px] bg-[#172554] text-[#93C5FD] text-[10px]"><?= esc($st['unit']); ?></span>
              </div>

              <!-- Nilai Statistik: Aksesibel langsung oleh Screen Reader & tetap tampil utuh bila JS nonaktif -->
              <div class="font-mono text-3xl sm:text-4xl font-bold text-white tracking-tight mb-2 tnum" aria-label="<?= esc($st['label'] . ': ' . $formattedVal); ?>">
                <span class="sr-only"><?= esc($st['label'] . ': ' . $formattedVal); ?></span>
                <span class="stat-counter" aria-hidden="true" data-target="<?= (int)$st['value']; ?>"><?= $formattedVal; ?></span>
              </div>

              <h3 class="font-sans text-sm text-white font-semibold"><?= esc($st['label']); ?></h3>
              <p class="font-sans text-xs text-[#CBD5E1] mt-1.5 leading-relaxed"><?= esc($st['desc']); ?></p>
            </div>

            <div class="mt-6 pt-3 border-t border-white/10 flex items-center justify-between font-mono text-[10px] text-[#93C5FD] uppercase tracking-wider tnum">
              <span>SUMBER: SQL QUERY</span>
              <span>VERIFIED</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>


    <!-- ==================== STAGE 4A: CATALOG SECTION (LIGHT EDITORIAL SURFACE) ==================== -->
    <section id="katalog" data-stage-section="catalog" class="relative z-20 bg-[#F4F5F7] text-[#0F172A] border-y border-[#DFE1E6] py-24 px-5 sm:px-8">
      <div class="max-w-7xl mx-auto">

        <!-- Header Katalog Editorial Terang -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 pb-8 border-b border-[#DFE1E6] mb-8 reveal-up">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-[6px] bg-white border border-[#DFE1E6] font-mono text-[11px] uppercase tracking-[0.16em] text-[#0052CC] mb-3 tnum">
              <span>04 // KATALOG KOLEKSI</span>
              <span>&bull;</span>
              <span>ID // ARCHIVE</span>
            </div>
            <h2 class="font-editorial text-4xl sm:text-5xl text-[#172B4D] font-normal tracking-tight">
              Katalog &amp; Inspeksi Koleksi Buku
            </h2>
            <p class="font-sans text-sm sm:text-base text-[#42526E] mt-2 max-w-2xl">
              Telusuri koleksi berdasarkan judul, penulis, atau kategori. Pilih salah satu kartu buku untuk memeriksa detail bibliografi dan status stok pada panel inspeksi.
            </p>
          </div>

          <!-- Kolom Pencarian Langsung dengan Shortcut (/) -->
          <div class="w-full lg:w-[420px] shrink-0">
            <label for="catalogSearch" class="block font-mono text-[10px] uppercase tracking-[0.16em] text-[#42526E] mb-2">
              CARI KOLEKSI (TEKAN <kbd class="px-1.5 py-0.5 bg-white border border-[#DFE1E6] rounded text-[#0F172A]">/</kbd> UNTUK FOKUS)
            </label>
            <div class="bg-white border border-[#DFE1E6] rounded-[8px] px-3.5 py-2.5 flex items-center gap-3 focus-within:border-[#0052CC] transition-colors">
              <span class="font-mono text-[11px] text-[#42526E] select-none">[CARI]</span>
              <input
                type="search"
                id="catalogSearch"
                autocomplete="off"
                placeholder="Judul, penulis, atau kategori..."
                class="bg-transparent text-[#0F172A] text-sm placeholder:text-[#42526E]/70 focus:outline-none w-full font-sans"
              />
              <button id="clearSearch" type="button" class="hidden px-2.5 py-1 rounded-[4px] bg-[#F4F5F7] hover:bg-[#DFE1E6] text-[#172B4D] font-mono text-[10px] uppercase tracking-wider transition-colors">
                HAPUS
              </button>
            </div>
          </div>
        </div>

        <!-- Baris Filter Kategori & Jumlah Hasil Relevan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 reveal-up">
          <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0" id="kategoriFilter" role="group" aria-label="Filter Kategori Buku">
            <button type="button" aria-pressed="true" class="filter-pill active px-4 py-2 rounded-[6px] font-mono text-xs uppercase tracking-wider bg-[#0052CC] text-white border border-[#0052CC] font-medium shrink-0 transition-colors tnum" data-filter="all">
              Semua Kategori (<?= count($buku_showcase); ?>)
            </button>
            <?php foreach ($kategori_list as $kat): ?>
            <button type="button" aria-pressed="false" class="filter-pill px-4 py-2 rounded-[6px] font-mono text-xs uppercase tracking-wider bg-white text-[#42526E] hover:text-[#0F172A] hover:border-[#0052CC] border border-[#DFE1E6] shrink-0 transition-colors" data-filter="<?= esc(mb_strtolower($kat)); ?>">
              <?= esc($kat); ?>
            </button>
            <?php endforeach; ?>
          </div>

          <div class="font-mono text-xs text-[#42526E] shrink-0 tnum" id="catalogResultCount" aria-live="polite">
            Menampilkan <strong class="text-[#0F172A]"><?= count($buku_showcase); ?></strong> dari <?= count($buku_showcase); ?> buku
          </div>
        </div>

        <!-- Grid Kartu Buku Katalog -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="bookGrid">
          <?php foreach ($buku_showcase as $idx => $buku):
              $bukuId      = (int)($buku['BukuID'] ?? ($idx + 1));
              $judulRaw    = trim((string)($buku['Judul'] ?? 'Tanpa Judul'));
              $penulisRaw  = trim((string)($buku['Penulis'] ?? ''));
              $penerbitRaw = trim((string)($buku['Penerbit'] ?? ''));
              $tahunRaw    = trim((string)($buku['TahunTerbit'] ?? ''));
              $isbnRaw     = trim((string)($buku['ISBN'] ?? ''));
              $katRaw      = trim((string)($buku['Kategori'] ?? '')) === '' ? 'Umum' : trim((string)$buku['Kategori']);
              $stok        = (int)($buku['Stok'] ?? 0);
              $tersedia    = $stok > 0;
              $kodeArsip   = 'IDX-' . str_pad((string)$bukuId, 2, '0', STR_PAD_LEFT);
              $searchBlob  = mb_strtolower($judulRaw . ' ' . $penulisRaw . ' ' . $katRaw);
          ?>
          <article
            tabindex="0"
            role="button"
            aria-haspopup="dialog"
            aria-label="Inspeksi detail buku <?= esc($judulRaw); ?><?= $penulisRaw !== '' ? ' karya ' . esc($penulisRaw) : ''; ?>, status <?= $tersedia ? 'Tersedia ' . $stok . ' stok' : 'Stok habis'; ?>"
            class="book-card bg-white border border-[#DFE1E6] p-4 flex flex-col justify-between cursor-pointer reveal-up"
            data-book-index="<?= (int)$idx; ?>"
            data-id="<?= $bukuId; ?>"
            data-kode="<?= esc($kodeArsip); ?>"
            data-search="<?= esc($searchBlob); ?>"
            data-cat="<?= esc(mb_strtolower($katRaw)); ?>">

            <div>
              <!-- Header Kartu: Kategori & Status Stok Aktual (Dibedakan melalui Teks Simbol + Warna) -->
              <div class="flex items-center justify-between gap-2 mb-3">
                <span class="font-mono text-[10px] uppercase tracking-wider text-[#42526E] truncate max-w-[130px]" title="<?= esc($katRaw); ?>">
                  <?= esc($katRaw); ?>
                </span>
                <?php if ($tersedia): ?>
                  <span class="stock-badge-available px-2.5 py-0.5 rounded-[4px] font-mono text-[10px] font-bold uppercase tracking-wider tnum shrink-0">
                    [TERSEDIA &bull; <?= $stok; ?>]
                  </span>
                <?php else: ?>
                  <span class="stock-badge-unavailable px-2.5 py-0.5 rounded-[4px] font-mono text-[10px] font-bold uppercase tracking-wider tnum shrink-0">
                    [HABIS &bull; 0]
                  </span>
                <?php endif; ?>
              </div>

              <!-- Sampul Rasio 3:4 dengan Efek Punggung Buku -->
              <div class="book-cover-slot aspect-[3/4] w-full overflow-hidden rounded-[6px] bg-[#070D1E] book-spine-surface mb-4 relative">
                <?= bookCoverInner($buku, $idx); ?>
              </div>

              <!-- Indeks Arsip, Judul & Penulis -->
              <div class="font-mono text-[10px] text-[#42526E] uppercase tracking-widest mb-1 tnum">
                <?= esc($kodeArsip); ?><?= $tahunRaw !== '' ? ' &bull; ' . esc($tahunRaw) : ''; ?>
              </div>
              <h3 class="book-card-title font-editorial text-xl text-[#172B4D] line-clamp-2 leading-snug font-normal">
                <?= esc($judulRaw); ?>
              </h3>
              <?php if ($penulisRaw !== ''): ?>
              <p class="font-sans text-xs text-[#42526E] mt-1 line-clamp-1">
                <?= esc($penulisRaw); ?>
              </p>
              <?php endif; ?>
            </div>

            <!-- Footer Kartu -->
            <div class="pt-3.5 mt-4 border-t border-[#DFE1E6] flex items-center justify-between font-mono text-[10px] uppercase tracking-wider text-[#172B4D]">
              <span>Inspeksi Buku</span>
              <span class="book-card-arrow font-bold text-[#0052CC]">&rarr;</span>
            </div>

          </article>
          <?php endforeach; ?>
        </div>

        <!-- Empty State Ketika Hasil Filter/Pencarian Kosong -->
        <div id="emptyCatalogState" class="hidden py-16 px-6 text-center bg-white rounded-[8px] border border-[#DFE1E6] my-6">
          <div class="font-mono text-xs uppercase tracking-[0.2em] text-[#0052CC] mb-2">[ ID // ARCHIVE_EMPTY ]</div>
          <h3 class="font-editorial text-2xl text-[#172B4D] mb-2 font-normal">Tidak ada buku yang cocok dengan kriteria pencarian.</h3>
          <p class="text-xs text-[#42526E] font-mono uppercase tracking-widest mb-6">Periksa ejaan kata kunci atau atur ulang filter kategori.</p>
          <button type="button" id="resetCatalogFilterBtn" class="px-5 py-2.5 rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white font-sans text-xs font-semibold uppercase tracking-wider transition-colors">
            Reset Pencarian &amp; Filter
          </button>
        </div>

      </div>
    </section>


    <!-- ==================== STAGE 4B: CLOSING ARCHIVE & FOOTER (80–100%) ==================== -->
    <section data-stage-section="closing" class="py-24 sm:py-28 px-5 sm:px-8 max-w-7xl mx-auto relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">

        <div class="lg:col-span-7 bg-[#0F172A]/95 rounded-[8px] border border-white/10 p-8 sm:p-12 reveal-up">
          <div class="inline-flex items-center gap-2 font-mono text-[11px] uppercase tracking-[0.2em] text-[#D4AF37] mb-4">
            <span>VOL. I // PORTAL ANGGOTA</span>
          </div>
          <h2 class="font-editorial text-3xl sm:text-5xl text-white leading-[1.1] font-normal mb-5">
            Lanjutkan ke portal sirkulasi <span class="italic text-[#93C5FD]">BIBLIOTHECA</span>.
          </h2>
          <p class="font-sans text-[#CBD5E1] text-sm sm:text-base leading-relaxed mb-8">
            Pada tahap akhir halaman ini, instalasi buku 3D menutup sampul depannya untuk menampilkan identitas <strong class="text-white font-semibold">BIBLIOTHECA — ARCHIVE</strong>. Masuk ke akun Anda untuk meminjam literatur atau daftar sebagai anggota baru.
          </p>
          <div class="flex flex-wrap items-center gap-4">
            <a href="login.php" class="px-6 py-3.5 rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white font-sans text-xs font-semibold uppercase tracking-wider transition-colors">
              Masuk ke Akun (login.php) &rarr;
            </a>
            <a href="register.php" class="px-6 py-3.5 rounded-[6px] bg-[#070D1E] border border-white/15 hover:border-[#93C5FD] text-white font-sans text-xs font-semibold uppercase tracking-wider transition-colors">
              Daftar Anggota (register.php)
            </a>
          </div>
        </div>

        <div class="lg:col-span-5 flex flex-col justify-end lg:items-end pointer-events-none">
          <div class="bg-[#0F172A]/90 border border-white/15 rounded-[8px] p-5 max-w-xs reveal-up">
            <div class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#D4AF37] mb-1.5">
              STAGE 04 // ARCHIVE POSE
            </div>
            <p class="font-sans text-xs text-[#CBD5E1] leading-relaxed">
              Sampul hardcover menutup secara bertahap memperlihatkan tipografi serif <em>BIBLIOTHECA — ARCHIVE</em> berbingkai emas.
            </p>
          </div>
        </div>

      </div>
    </section>

  </main>


  <!-- ==================== FOOTER ==================== -->
  <footer id="footer" class="relative z-20 bg-[#070D1E] border-t border-white/10 py-12 px-5 sm:px-8">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 font-mono text-xs text-[#CBD5E1]">
      <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
        <span class="text-white font-bold tracking-wider">Digital Library — BIBLIOTHECA</span>
        <span class="hidden sm:inline text-white/20">|</span>
        <span class="tnum">&copy; <?= date('Y'); ?> Sistem Arsip &amp; Sirkulasi Pustaka.</span>
      </div>
      <div class="flex flex-wrap items-center gap-6 uppercase tracking-wider text-[11px]">
        <a href="#beranda" class="hover:text-white transition-colors">01. Beranda</a>
        <a href="#cara-kerja" class="hover:text-white transition-colors">02. Sirkulasi</a>
        <a href="#statistik" class="hover:text-white transition-colors">03. Indeks Data</a>
        <a href="#katalog" class="hover:text-white transition-colors">04. Katalog</a>
        <a href="login.php" class="text-[#93C5FD] hover:text-white transition-colors">Masuk</a>
        <a href="register.php" class="text-[#D4AF37] hover:text-white transition-colors">Daftar</a>
      </div>
    </div>
  </footer>


  <!-- ==================== 12. SLIDE-OVER BOOK INSPECTION DRAWER ==================== -->
  <div id="drawerBackdrop" class="fixed inset-0 z-50 bg-[#070D1E]/75 backdrop-blur-xs" aria-hidden="true"></div>

  <aside
    id="inspectionDrawer"
    aria-labelledby="drawerJudul"
    aria-modal="true"
    role="dialog"
     aria-hidden="true"
    tabindex="-1"
    class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white text-[#0F172A] border-l border-[#DFE1E6] shadow-2xl flex flex-col justify-between overflow-y-auto">

    <div>
      <!-- Header Panel Detail -->
      <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-xs px-6 py-4 border-b border-[#DFE1E6] flex items-center justify-between gap-3">
        <div>
          <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#0052CC] block tnum" id="drawerKodeArsip">IDX-01 // INSPEKSI BUKU</span>
          <h4 class="font-sans text-xs font-bold uppercase tracking-wider text-[#172B4D]">Detail Bibliografi &amp; Stok</h4>
        </div>
        <button id="closeDrawerBtn" type="button" aria-label="Tutup Panel Detail Buku" class="px-3 py-1.5 rounded-[6px] bg-[#F4F5F7] hover:bg-[#DFE1E6] text-[#0F172A] font-mono text-xs uppercase tracking-wider transition-colors">
          TUTUP [ESC]
        </button>
      </div>

      <!-- Isi Panel Detail -->
      <div class="p-6 space-y-6">

        <!-- Sampul Besar & Identitas Utama -->
        <div class="flex flex-col sm:flex-row gap-5 items-start bg-[#F4F5F7] p-4 rounded-[8px] border border-[#DFE1E6]">
          <div id="drawerCoverPreview" class="w-32 sm:w-28 aspect-[3/4] rounded-[6px] overflow-hidden shrink-0 book-spine-surface bg-[#070D1E]"></div>
          <div class="flex-1 min-w-0">
            <div class="inline-block px-2.5 py-0.5 rounded-[4px] bg-white border border-[#DFE1E6] font-mono text-[10px] uppercase tracking-wider text-[#0052CC] mb-2" id="drawerKategori">
              KATEGORI
            </div>
            <h3 class="font-editorial text-2xl text-[#172B4D] leading-snug font-normal break-words" id="drawerJudul">
              Judul Buku
            </h3>
            <p class="font-sans text-xs text-[#42526E] mt-1 font-medium" id="drawerPenulisRow">
              Oleh <span id="drawerPenulis">-</span>
            </p>

            <!-- Status Stok -->
            <div class="mt-4">
              <div id="drawerStokBox" class="stock-badge-available px-3 py-1.5 rounded-[6px] font-mono text-xs font-bold uppercase tracking-wider tnum inline-flex items-center gap-2">
                <span id="drawerStokText">[TERSEDIA &bull; 0 EKSEMPLAR]</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabel Metadata (Hanya menampilkan baris metadata yang tersedia) -->
        <div class="border border-[#DFE1E6] rounded-[8px] overflow-hidden">
          <div class="bg-[#F4F5F7] px-4 py-2.5 border-b border-[#DFE1E6] font-mono text-[10px] uppercase tracking-[0.16em] text-[#42526E]">
            METADATA KATALOG
          </div>
          <dl class="divide-y divide-[#DFE1E6] font-mono text-xs tnum">
            <div id="drawerRowKategori" class="px-4 py-3 flex justify-between gap-4">
              <dt class="text-[#42526E] uppercase">Kategori</dt>
              <dd class="text-[#0052CC] font-bold text-right" id="drawerKategoriMeta">-</dd>
            </div>
            <div id="drawerRowPenerbit" class="px-4 py-3 flex justify-between gap-4">
              <dt class="text-[#42526E] uppercase">Penerbit</dt>
              <dd class="text-[#0F172A] font-bold text-right" id="drawerPenerbit">-</dd>
            </div>
            <div id="drawerRowTahun" class="px-4 py-3 flex justify-between gap-4">
              <dt class="text-[#42526E] uppercase">Tahun Terbit</dt>
              <dd class="text-[#0F172A] font-bold text-right" id="drawerTahun">-</dd>
            </div>
            <div id="drawerRowIsbn" class="px-4 py-3 flex justify-between gap-4">
              <dt class="text-[#42526E] uppercase">ISBN</dt>
              <dd class="text-[#0F172A] font-bold text-right" id="drawerIsbn">-</dd>
            </div>
          </dl>
        </div>

        <!-- Aturan Peminjaman Terverifikasi -->
        <div class="p-4 rounded-[8px] bg-[#F4F5F7] border border-[#DFE1E6] space-y-2">
          <div class="flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.15em] text-[#172B4D] font-bold">
            <span>ATURAN PEMINJAMAN</span>
            <span class="text-[#0052CC]">TERVERIFIKASI</span>
          </div>
          <p class="font-sans text-xs text-[#42526E] leading-relaxed">
            Peminjaman dilakukan melalui akun anggota dengan batas maksimal <strong class="text-[#0F172A]">3 buku aktif</strong> secara bersamaan dan masa pinjam <strong class="text-[#0F172A]">14 hari kalender</strong>.
          </p>
        </div>

      </div>
    </div>

    <!-- Footer CTA Slide-Over Panel -->
    <div class="sticky bottom-0 z-20 bg-white border-t border-[#DFE1E6] p-6 space-y-2.5">
      <a id="drawerPinjamCta" href="login.php" class="w-full py-3.5 px-5 rounded-[6px] bg-[#0052CC] hover:bg-[#1D4ED8] text-white font-sans text-xs font-semibold uppercase tracking-wider transition-colors flex items-center justify-center gap-2">
        <span>Pinjam Buku Ini (Masuk)</span>
        <span class="font-mono">&rarr;</span>
      </a>
      <button id="closeDrawerSecondary" type="button" class="w-full py-2.5 px-4 rounded-[6px] bg-[#F4F5F7] hover:bg-[#DFE1E6] text-[#42526E] font-mono text-xs uppercase tracking-wider transition-colors">
        Kembali ke Katalog
      </button>
    </div>

  </aside>


  <!-- Serialisasi JSON Aman untuk Katalog & Instalasi Buku 3D -->
  <script id="catalogArchiveJson" type="application/json">
    <?= json_encode(array_values(array_map(function ($b, $i) {
        $kat = trim((string)($b['Kategori'] ?? ''));
        return [
            'index'    => (int)$i,
            'id'       => (int)($b['BukuID'] ?? ($i + 1)),
            'kode'     => 'IDX-' . str_pad((string)((int)($b['BukuID'] ?? ($i + 1))), 2, '0', STR_PAD_LEFT),
            'judul'    => trim((string)($b['Judul'] ?? 'Tanpa Judul')),
            'penulis'  => trim((string)($b['Penulis'] ?? '')),
            'penerbit' => trim((string)($b['Penerbit'] ?? '')),
            'kategori' => $kat === '' ? 'Umum' : $kat,
            'tahun'    => trim((string)($b['TahunTerbit'] ?? '')),
            'isbn'     => trim((string)($b['ISBN'] ?? '')),
            'stok'     => (int)($b['Stok'] ?? 0),
        ];
    }, $buku_showcase, array_keys($buku_showcase))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
  </script>


  <!-- ==================== LOGIKA UI/UX & INSTALASI WEBGL 3D THREE.JS ==================== -->
  <script type="module">
    import * as THREE from 'three';

    // Parse data katalog yang telah diserialisasi dengan aman dari PHP
    let catalogData = [];
    try {
      const rawJson = document.getElementById('catalogArchiveJson')?.textContent;
      if (rawJson) {
        catalogData = JSON.parse(rawJson);
      }
    } catch (e) {
      catalogData = [];
    }

    const prefersReducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    document.addEventListener('DOMContentLoaded', () => {
      initUIInteractions(catalogData);
      initArchivalBookInstallation3D(catalogData);
    });

    /* ============================================================================
     * 1. INTERAKSI UI, FILTER KATALOG, SLIDE-OVER DRAWER & AKSESIBILITAS
     * ============================================================================ */
    function initUIInteractions(books) {
      // Navigasi Mobile
      const mobileBtn = document.getElementById('mobileMenuBtn');
      const mobileMenu = document.getElementById('mobileMenu');

      if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', () => {
          const isHidden = mobileMenu.classList.contains('hidden');
          mobileMenu.classList.toggle('hidden', !isHidden);
          mobileBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
          mobileBtn.textContent = isHidden ? 'TUTUP' : 'MENU';
        });

        mobileMenu.querySelectorAll('a').forEach(link => {
          link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileBtn.setAttribute('aria-expanded', 'false');
            mobileBtn.textContent = 'MENU';
          });
        });
      }

      // Scroll Reveal dengan IntersectionObserver
      const revealEls = document.querySelectorAll('.reveal-up');
      if (prefersReducedMotionQuery.matches || !('IntersectionObserver' in window)) {
        revealEls.forEach(el => el.classList.add('is-visible'));
      } else {
        const revealObserver = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              revealObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.12, rootMargin: '0px 0px -32px 0px' });
        revealEls.forEach(el => revealObserver.observe(el));
      }

      // Counter Statistik (Nilai asli sudah ada di DOM & sr-only; animasi hanya efek visual)
      const counters = document.querySelectorAll('.stat-counter');
      if (!prefersReducedMotionQuery.matches && 'IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseInt(el.dataset.target || '0', 10);
            const duration = 1200;
            const start = performance.now();

            function tick(now) {
              const progress = Math.min((now - start) / duration, 1);
              const eased = 1 - Math.pow(1 - progress, 4);
              el.textContent = Math.floor(eased * target).toLocaleString('id-ID');
              if (progress < 1) {
                requestAnimationFrame(tick);
              } else {
                el.textContent = target.toLocaleString('id-ID');
              }
            }
            requestAnimationFrame(tick);
            counterObserver.unobserve(el);
          });
        }, { threshold: 0.35 });
        counters.forEach(c => counterObserver.observe(c));
      }

      // Pencarian & Filter Kategori Katalog
      const searchInput = document.getElementById('catalogSearch');
      const clearBtn = document.getElementById('clearSearch');
      const resetFilterBtn = document.getElementById('resetCatalogFilterBtn');
      const filterPills = document.querySelectorAll('#kategoriFilter .filter-pill');
      const bookCards = document.querySelectorAll('#bookGrid .book-card');
      const emptyState = document.getElementById('emptyCatalogState');
      const resultCountEl = document.getElementById('catalogResultCount');
      const totalCards = bookCards.length;
      let currentCat = 'all';

      function filterCatalog() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        if (clearBtn) {
          clearBtn.classList.toggle('hidden', q.length === 0);
        }

        let visibleCount = 0;
        bookCards.forEach((card) => {
          const matchCat = currentCat === 'all' || card.dataset.cat === currentCat;
          const matchQuery = !q || (card.dataset.search || '').includes(q);
          const show = matchCat && matchQuery;

          card.classList.toggle('hidden', !show);
          card.classList.toggle('flex', show);
          if (show) {
            visibleCount++;
          }
        });

        if (emptyState) {
          emptyState.classList.toggle('hidden', visibleCount > 0);
        }
        if (resultCountEl) {
          resultCountEl.innerHTML = '';
          const prefix = document.createTextNode('Menampilkan ');
          const strong = document.createElement('strong');
          strong.className = 'text-[#0F172A]';
          strong.textContent = String(visibleCount);
          const suffix = document.createTextNode(` dari ${totalCards} buku`);
          resultCountEl.append(prefix, strong, suffix);
        }
      }

      function setActiveCategoryPill(targetFilter) {
        currentCat = targetFilter;
        filterPills.forEach(p => {
          const isMatch = (p.dataset.filter || 'all') === targetFilter;
          p.classList.toggle('active', isMatch);
          p.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
          p.classList.toggle('bg-[#0052CC]', isMatch);
          p.classList.toggle('text-white', isMatch);
          p.classList.toggle('border-[#0052CC]', isMatch);
          p.classList.toggle('bg-white', !isMatch);
          p.classList.toggle('text-[#42526E]', !isMatch);
          p.classList.toggle('border-[#DFE1E6]', !isMatch);
        });
        filterCatalog();
      }

      filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
          setActiveCategoryPill(pill.dataset.filter || 'all');
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', filterCatalog);
      }
      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
          }
          filterCatalog();
        });
      }
      if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
          if (searchInput) {
            searchInput.value = '';
          }
          setActiveCategoryPill('all');
        });
      }

      // Slide-Over Book Inspection Drawer dengan Pengelolaan Fokus Keyboard
      const drawer = document.getElementById('inspectionDrawer');
      const backdrop = document.getElementById('drawerBackdrop');
      const closeBtnPrimary = document.getElementById('closeDrawerBtn');
      const closeBtnSecondary = document.getElementById('closeDrawerSecondary');
      let lastFocusedCard = null;

      function setOptionalRow(rowId, valueId, valueText) {
        const rowEl = document.getElementById(rowId);
        const valEl = document.getElementById(valueId);
        const clean = (valueText || '').trim();
        if (!rowEl || !valEl) return;
        if (clean !== '' && clean !== '-') {
          rowEl.classList.remove('hidden');
          rowEl.classList.add('flex');
          valEl.textContent = clean;
        } else {
          rowEl.classList.add('hidden');
          rowEl.classList.remove('flex');
          valEl.textContent = '';
        }
      }

      function openInspectionDrawer(card) {
        if (!drawer || !backdrop) return;
        lastFocusedCard = card;

        const bookIdx = parseInt(card.dataset.bookIndex || '0', 10);
        const book = books[bookIdx] || {};

        const kode = book.kode || card.dataset.kode || 'IDX-01';
        const kategori = (book.kategori || 'Umum').trim();
        const judul = (book.judul || 'Tanpa Judul').trim();
        const penulis = (book.penulis || '').trim();
        const penerbit = (book.penerbit || '').trim();
        const tahun = (book.tahun || '').trim();
        const isbn = (book.isbn || '').trim();
        const stok = Number.isFinite(book.stok) ? book.stok : 0;

        document.getElementById('drawerKodeArsip').textContent = `${kode} // INSPEKSI BUKU`;
        document.getElementById('drawerKategori').textContent = kategori;
        document.getElementById('drawerJudul').textContent = judul;

        const penulisRow = document.getElementById('drawerPenulisRow');
        const penulisSpan = document.getElementById('drawerPenulis');
        if (penulis !== '') {
          penulisRow.classList.remove('hidden');
          penulisSpan.textContent = penulis;
        } else {
          penulisRow.classList.add('hidden');
        }

        setOptionalRow('drawerRowKategori', 'drawerKategoriMeta', kategori);
        setOptionalRow('drawerRowPenerbit', 'drawerPenerbit', penerbit);
        setOptionalRow('drawerRowTahun', 'drawerTahun', tahun);
        setOptionalRow('drawerRowIsbn', 'drawerIsbn', isbn);

        // Salin visual sampul buku dari kartu ke panel detail
        const coverSlot = card.querySelector('.book-cover-slot');
        const drawerCover = document.getElementById('drawerCoverPreview');
        if (coverSlot && drawerCover) {
          drawerCover.replaceChildren();
          Array.from(coverSlot.childNodes).forEach(node => {
            drawerCover.appendChild(node.cloneNode(true));
          });
        }

        // Status Stok
        const stokBox = document.getElementById('drawerStokBox');
        const stokText = document.getElementById('drawerStokText');
        if (stok > 0) {
          stokBox.className = 'stock-badge-available px-3 py-1.5 rounded-[6px] font-mono text-xs font-bold uppercase tracking-wider tnum inline-flex items-center gap-2';
          stokText.textContent = `[TERSEDIA • ${stok} EKSEMPLAR]`;
        } else {
          stokBox.className = 'stock-badge-unavailable px-3 py-1.5 rounded-[6px] font-mono text-xs font-bold uppercase tracking-wider tnum inline-flex items-center gap-2';
          stokText.textContent = '[STOK HABIS • 0 EKSEMPLAR]';
        }

        // Kunci scroll halaman belakang tanpa menggeser layout
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        document.body.style.paddingRight = scrollbarWidth > 0 ? `${scrollbarWidth}px` : '';
        document.body.style.overflow = 'hidden';

        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.classList.add('is-open');

        requestAnimationFrame(() => {
          closeBtnPrimary?.focus();
        });
      }

      function closeInspectionDrawer() {
        if (!drawer || !backdrop || !drawer.classList.contains('is-open')) return;
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        backdrop.classList.remove('is-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        if (lastFocusedCard && typeof lastFocusedCard.focus === 'function') {
          lastFocusedCard.focus();
          lastFocusedCard = null;
        }
      }

      bookCards.forEach(card => {
        card.addEventListener('click', () => openInspectionDrawer(card));
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openInspectionDrawer(card);
          }
        });
      });

      closeBtnPrimary?.addEventListener('click', closeInspectionDrawer);
      closeBtnSecondary?.addEventListener('click', closeInspectionDrawer);
      backdrop?.addEventListener('click', closeInspectionDrawer);

      // Keyboard Shortcut '/' & Focus Trap untuk Slide-Over Drawer
      window.addEventListener('keydown', (e) => {
        const activeEl = document.activeElement;
        const isEditable = activeEl && (
          ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName) ||
          activeEl.isContentEditable
        );

        if (e.key === '/' && !isEditable && !drawer?.classList.contains('is-open')) {
          e.preventDefault();
          searchInput?.focus();
          searchInput?.scrollIntoView({ behavior: ' prefersReducedMotionQuery.matches ? "auto" : "smooth"', block: 'center' });
          return;
        }

        if (e.key === 'Escape') {
          if (drawer?.classList.contains('is-open')) {
            e.preventDefault();
            closeInspectionDrawer();
          } else if (activeEl === searchInput && searchInput.value !== '') {
            searchInput.value = '';
            filterCatalog();
          }
          return;
        }

        // Focus Trap saat Slide-Over Drawer terbuka
        if (e.key === 'Tab' && drawer?.classList.contains('is-open')) {
          const focusable = drawer.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
          if (focusable.length === 0) return;
          const first = focusable[0];
          const last = focusable[focusable.length - 1];

          if (e.shiftKey && activeEl === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && activeEl === last) {
            e.preventDefault();
            first.focus();
          }
        }
      });
    }


    /* ============================================================================
     * 2. INSTALASI BUKU 3D WEBGL & KOREOGRAFI SCROLL 4-TAHAP
     * ============================================================================ */
    function initArchivalBookInstallation3D(books) {
      const container = document.getElementById('webgl-stage');
      const staticFallback = document.getElementById('webgl-static-fallback');
      const qualitySelect = document.getElementById('qualityModeSelect');
      const progressBar = document.getElementById('scroll-progress');
      const phaseLabel = document.getElementById('scene-phase-label');
      const liveScrollPct = document.getElementById('liveScrollPct');

      if (!container) return;

      // Deteksi kapabilitas perangkat & prefers-reduced-motion untuk menentukan mode awal
      const isLowPowerDevice = (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 4);
      let currentQuality = prefersReducedMotionQuery.matches
        ? 'reduced'
        : (isLowPowerDevice ? 'standard' : 'standard');

      if (qualitySelect) {
        qualitySelect.value = currentQuality;
      }

      // Coba inisialisasi WebGLRenderer tunggal dengan fallback aman
      let renderer = null;
      try {
        renderer = new THREE.WebGLRenderer({
          alpha: true,
          antialias: currentQuality !== 'reduced',
          powerPreference: 'high-performance'
        });
      } catch (err) {
        renderer = null;
      }

      if (!renderer) {
        if (staticFallback) {
          staticFallback.classList.remove('hidden');
          staticFallback.classList.add('flex');
        }
        return;
      }

      renderer.shadowMap.enabled = true;
      renderer.shadowMap.type = THREE.PCFSoftShadowMap;
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure = 1.12;
      container.appendChild(renderer.domElement);

      const scene = new THREE.Scene();
      scene.fog = new THREE.FogExp2(0x070D1E, 0.034);

      const camera = new THREE.PerspectiveCamera(38, window.innerWidth / window.innerHeight, 0.1, 80);
      camera.position.set(0, 1.15, 8.8);

      // --- PENCAHAYAAN TERUKUR (Dark Blueprint Cobalt + Gold Foil) ---
      const ambientLight = new THREE.AmbientLight(0xCBD5E1, 0.62);
      scene.add(ambientLight);

      const keyLight = new THREE.DirectionalLight(0xFFFBEB, 2.15);
      keyLight.position.set(4.2, 6.2, 5.2);
      keyLight.castShadow = true;
      keyLight.shadow.mapSize.width = 1024;
      keyLight.shadow.mapSize.height = 1024;
      keyLight.shadow.camera.near = 1;
      keyLight.shadow.camera.far = 18;
      scene.add(keyLight);

      const cobaltRimLight = new THREE.PointLight(0x0052CC, 5.8, 16);
      cobaltRimLight.position.set(-4.0, 2.4, -2.6);
      scene.add(cobaltRimLight);

      const lightBlueFill = new THREE.PointLight(0x93C5FD, 2.8, 13);
      lightBlueFill.position.set(2.2, -1.8, 3.4);
      scene.add(lightBlueFill);

      // --- GENERATOR TEKSTUR PROSEDURAL CANVAS 2D ---
      const createdTextures = [];
      const createdGeometries = [];
      const createdMaterials = [];

      function trackTex(t) { createdTextures.push(t); return t; }
      function trackGeo(g) { createdGeometries.push(g); return g; }
      function trackMat(m) { createdMaterials.push(m); return m; }

      function createHeroCoverTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 1024;
        canvas.height = 1408;
        const ctx = canvas.getContext('2d');

        // Permukaan Sampul Dark Blueprint (#070D1E -> #0F172A -> #172554)
        const grad = ctx.createLinearGradient(0, 0, 1024, 1408);
        grad.addColorStop(0, '#070D1E');
        grad.addColorStop(0.55, '#0F172A');
        grad.addColorStop(1, '#172554');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 1024, 1408);

        // Tekstur Serat Linen/Buckram Prosedural Halus
        ctx.strokeStyle = 'rgba(147, 197, 253, 0.035)';
        ctx.lineWidth = 1;
        for (let x = 0; x < 1024; x += 8) {
          ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, 1408); ctx.stroke();
        }
        for (let y = 0; y < 1408; y += 8) {
          ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(1024, y); ctx.stroke();
        }

        // Bingkai Emas Tipis dan Presisi (#D4AF37)
        ctx.strokeStyle = '#D4AF37';
        ctx.lineWidth = 5;
        ctx.strokeRect(58, 58, 908, 1292);

        ctx.strokeStyle = 'rgba(212, 175, 55, 0.45)';
        ctx.lineWidth = 2;
        ctx.strokeRect(76, 76, 872, 1256);

        // Sudut Arsitektural Presisi
        const corners = [
          [58, 58, 1, 1],
          [966, 58, -1, 1],
          [58, 1350, 1, -1],
          [966, 1350, -1, -1]
        ];
        ctx.strokeStyle = '#D4AF37';
        ctx.lineWidth = 3;
        corners.forEach(([cx, cy, sx, sy]) => {
          ctx.beginPath();
          ctx.moveTo(cx + sx * 44, cy + sy * 18);
          ctx.lineTo(cx + sx * 18, cy + sy * 18);
          ctx.lineTo(cx + sx * 18, cy + sy * 44);
          ctx.stroke();
        });

        // Kode Indeks Atas
        ctx.fillStyle = '#93C5FD';
        ctx.font = '700 22px "Space Mono", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('ID // ARCHIVE • VOL. I', 512, 168);

        ctx.strokeStyle = 'rgba(212, 175, 55, 0.55)';
        ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.moveTo(320, 200); ctx.lineTo(704, 200); ctx.stroke();

        // Tipografi Serif BIBLIOTHECA — ARCHIVE
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '400 82px "Newsreader", Georgia, serif';
        ctx.fillText('BIBLIOTHECA', 512, 336);

        ctx.fillStyle = '#D4AF37';
        ctx.font = '400 32px "Newsreader", Georgia, serif';
        ctx.fillText('— ARCHIVE —', 512, 402);

        // Diagram Geometri Arsitektural Buku di Tengah Sampul
        ctx.save();
        ctx.translate(512, 745);
        ctx.strokeStyle = '#D4AF37';
        ctx.lineWidth = 2.5;
        ctx.beginPath(); ctx.arc(0, 0, 165, 0, Math.PI * 2); ctx.stroke();

        ctx.strokeStyle = 'rgba(147, 197, 253, 0.45)';
        ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.arc(0, 0, 126, 0, Math.PI * 2); ctx.stroke();
        ctx.strokeRect(-90, -90, 180, 180);

        for (let i = 0; i < 8; i++) {
          const ang = (i / 8) * Math.PI * 2;
          ctx.strokeStyle = i % 2 === 0 ? '#D4AF37' : 'rgba(212, 175, 55, 0.35)';
          ctx.beginPath();
          ctx.moveTo(Math.cos(ang) * 70, Math.sin(ang) * 70);
          ctx.lineTo(Math.cos(ang) * 165, Math.sin(ang) * 165);
          ctx.stroke();
        }
        ctx.restore();

        // Keterangan Kaki Sampul
        ctx.fillStyle = '#CBD5E1';
        ctx.font = '400 20px "Space Mono", monospace';
        ctx.fillText('DIGITAL LIBRARY LEDGER & CATALOG', 512, 1185);

        ctx.fillStyle = '#D4AF37';
        ctx.font = '700 22px "Space Mono", monospace';
        ctx.fillText('IDX-01 // EDITION ' + new Date().getFullYear(), 512, 1240);

        const tex = new THREE.CanvasTexture(canvas);
        tex.anisotropy = 4;
        return trackTex(tex);
      }

      function createPageEdgeStriationTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#EBE6DC';
        ctx.fillRect(0, 0, 256, 256);

        // Garis-garis halus berlapis untuk memperlihatkan ketebalan lembaran halaman dari sisi
        for (let y = 0; y < 256; y += 2) {
          ctx.fillStyle = (y % 4 === 0) ? '#D8D1C5' : '#F4F1EA';
          ctx.fillRect(0, y, 256, 1);
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        tex.repeat.set(2, 6);
        return trackTex(tex);
      }

      function createArchivalPageTexture(pageSide = 'left') {
        const canvas = document.createElement('canvas');
        canvas.width = 768;
        canvas.height = 1024;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#F6F4EE';
        ctx.fillRect(0, 0, 768, 1024);

        // Bayangan lipatan tengah buku (Gutter Shadow)
        const gutterGrad = ctx.createLinearGradient(
          pageSide === 'left' ? 670 : 0, 0,
          pageSide === 'left' ? 768 : 98, 0
        );
        if (pageSide === 'left') {
          gutterGrad.addColorStop(0, 'rgba(15, 23, 42, 0)');
          gutterGrad.addColorStop(1, 'rgba(15, 23, 42, 0.24)');
          ctx.fillStyle = gutterGrad;
          ctx.fillRect(670, 0, 98, 1024);
        } else {
          gutterGrad.addColorStop(0, 'rgba(15, 23, 42, 0.24)');
          gutterGrad.addColorStop(1, 'rgba(15, 23, 42, 0)');
          ctx.fillStyle = gutterGrad;
          ctx.fillRect(0, 0, 98, 1024);
        }

        // Garis tepi halaman editorial
        ctx.strokeStyle = 'rgba(23, 43, 77, 0.16)';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(60, 68, 648, 888);

        ctx.fillStyle = '#42526E';
        ctx.font = '700 15px "Space Mono", monospace';
        ctx.fillText(pageSide === 'left' ? 'BIBLIOTHECA — ARCHIVE // IDX-01' : 'VOL. I // SIRKULASI LITERATUR', 88, 108);

        // Inisial Serif & Baris Teks Editorial
        ctx.fillStyle = '#D4AF37';
        ctx.fillRect(88, 140, 72, 72);
        ctx.fillStyle = '#070D1E';
        ctx.font = 'italic 700 48px "Newsreader", Georgia, serif';
        ctx.fillText('B', 108, 192);

        ctx.fillStyle = '#172B4D';
        for (let y = 148; y < 510; y += 24) {
          const startX = y < 228 ? 182 : 88;
          const lineW = (y % 72 === 0) ? 410 : 568 - (startX - 88);
          ctx.fillRect(startX, y, lineW, 4);
        }

        // Kotak Blueprint Arsitektural di Dalam Halaman
        ctx.strokeStyle = '#0052CC';
        ctx.lineWidth = 2;
        ctx.strokeRect(88, 548, 592, 212);
        ctx.beginPath();
        ctx.arc(214, 654, 62, 0, Math.PI * 2);
        ctx.stroke();

        ctx.strokeStyle = '#D4AF37';
        ctx.beginPath();
        ctx.arc(214, 654, 38, 0, Math.PI * 2);
        ctx.stroke();

        ctx.fillStyle = '#42526E';
        for (let y = 584; y < 735; y += 22) {
          ctx.fillRect(312, y, 332, 4);
        }

        ctx.fillStyle = '#172B4D';
        for (let y = 800; y < 905; y += 24) {
          ctx.fillRect(88, y, y === 896 ? 330 : 584, 4);
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.anisotropy = 4;
        return trackTex(tex);
      }

      function createCompanionCoverTexture(item, idx) {
        const canvas = document.createElement('canvas');
        canvas.width = 384;
        canvas.height = 540;
        const ctx = canvas.getContext('2d');

        const bgColors = ['#070D1E', '#0F172A', '#172554', '#1E293B'];
        ctx.fillStyle = bgColors[idx % bgColors.length];
        ctx.fillRect(0, 0, 384, 540);

        ctx.strokeStyle = idx % 2 === 0 ? '#D4AF37' : '#93C5FD';
        ctx.lineWidth = 3;
        ctx.strokeRect(22, 22, 340, 496);

        ctx.fillStyle = '#D4AF37';
        ctx.font = '700 14px "Space Mono", monospace';
        ctx.fillText(`VOL. 0${idx + 1} // ${(item.kategori || 'ARSIP').toUpperCase().slice(0, 14)}`, 42, 66);

        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'italic 400 28px "Newsreader", Georgia, serif';
        const words = (item.judul || `Arsip Vol. ${idx + 1}`).split(' ');
        let line = '';
        let y = 185;
        words.forEach(w => {
          if ((line + w).length > 16 && y < 340) {
            ctx.fillText(line.trim(), 42, y);
            line = w + ' ';
            y += 36;
          } else {
            line += w + ' ';
          }
        });
        if (line.trim()) {
          ctx.fillText(line.trim().slice(0, 20), 42, y);
        }

        ctx.fillStyle = '#CBD5E1';
        ctx.font = '400 15px "Plus Jakarta Sans", sans-serif';
        ctx.fillText((item.penulis || 'Koleksi Arsip').toUpperCase().slice(0, 22), 42, 468);

        return trackTex(new THREE.CanvasTexture(canvas));
      }

      // --- MATERIAL 3D ---
      const heroCoverTex = createHeroCoverTexture();
      const pageEdgeTex = createPageEdgeStriationTexture();
      const leftPageTex = createArchivalPageTexture('left');
      const rightPageTex = createArchivalPageTexture('right');

      const navyBoardMat = trackMat(new THREE.MeshStandardMaterial({
        color: 0x0F172A,
        roughness: 0.44,
        metalness: 0.14
      }));

      const spineLeatherMat = trackMat(new THREE.MeshStandardMaterial({
        color: 0x070D1E,
        roughness: 0.38,
        metalness: 0.18
      }));

      const frontCoverMat = trackMat(new THREE.MeshStandardMaterial({
        map: heroCoverTex,
        roughness: 0.34,
        metalness: 0.20
      }));

      const goldFoilMat = trackMat(new THREE.MeshStandardMaterial({
        color: 0xD4AF37,
        roughness: 0.26,
        metalness: 0.82
      }));

      const pageEdgeLayeredMat = trackMat(new THREE.MeshStandardMaterial({
        map: pageEdgeTex,
        roughness: 0.58,
        metalness: 0.04
      }));

      const leftPageSurfaceMat = trackMat(new THREE.MeshStandardMaterial({
        map: leftPageTex,
        roughness: 0.46,
        side: THREE.DoubleSide
      }));

      const rightPageSurfaceMat = trackMat(new THREE.MeshStandardMaterial({
        map: rightPageTex,
        roughness: 0.46,
        side: THREE.DoubleSide
      }));

      // --- KONSTRUKSI PANGGUNG & THE HERO TOME ---
      const stageRoot = new THREE.Group();
      scene.add(stageRoot);

      const heroTome = new THREE.Group();
      stageRoot.add(heroTome);

      // 1. Punggung Buku Struktural (Spine) dengan Sabuk Tulang (Raised Bands) & Headcaps
      const spineGroup = new THREE.Group();
      const spineCoreGeo = trackGeo(new THREE.BoxGeometry(0.36, 2.52, 0.18));
      const spineMesh = new THREE.Mesh(spineCoreGeo, spineLeatherMat);
      spineMesh.castShadow = true;
      spineMesh.receiveShadow = true;
      spineGroup.add(spineMesh);

      const ribGeo = trackGeo(new THREE.BoxGeometry(0.38, 0.038, 0.20));
      for (let r = -0.96; r <= 0.96; r += 0.384) {
        const rib = new THREE.Mesh(ribGeo, goldFoilMat);
        rib.position.y = r;
        spineGroup.add(rib);
      }

      // Headband / Tailband struktural di ujung atas & bawah punggung buku
      const headBandGeo = trackGeo(new THREE.BoxGeometry(0.30, 0.05, 0.16));
      const headBandTop = new THREE.Mesh(headBandGeo, goldFoilMat);
      headBandTop.position.set(0, 1.24, 0.02);
      const headBandBottom = new THREE.Mesh(headBandGeo, goldFoilMat);
      headBandBottom.position.set(0, -1.24, 0.02);
      spineGroup.add(headBandTop, headBandBottom);
      heroTome.add(spineGroup);

      // 2. Sampul Depan (Engsel Kiri) & Blok Halaman Berlapis Kiri
      const leftSidePivot = new THREE.Group();
      leftSidePivot.position.set(-0.16, 0, 0);
      heroTome.add(leftSidePivot);

      const coverBoardGeo = trackGeo(new THREE.BoxGeometry(1.76, 2.50, 0.065));
      const leftCoverMaterials = [
        goldFoilMat,
        navyBoardMat,
        navyBoardMat,
        navyBoardMat,
        navyBoardMat,
        frontCoverMat // Sisi luar sampul depan menampilkan BIBLIOTHECA — ARCHIVE saat buku menutup
      ];
      const leftCoverBoard = new THREE.Mesh(coverBoardGeo, leftCoverMaterials);
      leftCoverBoard.position.set(-0.88, 0, -0.045);
      leftCoverBoard.castShadow = true;
      leftCoverBoard.receiveShadow = true;
      leftSidePivot.add(leftCoverBoard);

      const pageBlockGeo = trackGeo(new THREE.BoxGeometry(1.64, 2.36, 0.14));
      const leftPageBlock = new THREE.Mesh(
        pageBlockGeo,
        [pageEdgeLayeredMat, pageEdgeLayeredMat, pageEdgeLayeredMat, pageEdgeLayeredMat, leftPageSurfaceMat, pageEdgeLayeredMat]
      );
      leftPageBlock.position.set(-0.83, 0, 0.055);
      leftPageBlock.receiveShadow = true;
      leftSidePivot.add(leftPageBlock);

      // 3. Sampul Belakang (Engsel Kanan) & Blok Halaman Berlapis Kanan
      const rightSidePivot = new THREE.Group();
      rightSidePivot.position.set(0.16, 0, 0);
      heroTome.add(rightSidePivot);

      const rightCoverBoard = new THREE.Mesh(coverBoardGeo, navyBoardMat);
      rightCoverBoard.position.set(0.88, 0, -0.045);
      rightCoverBoard.castShadow = true;
      rightCoverBoard.receiveShadow = true;
      rightSidePivot.add(rightCoverBoard);

      const rightPageBlock = new THREE.Mesh(
        pageBlockGeo,
        [pageEdgeLayeredMat, pageEdgeLayeredMat, pageEdgeLayeredMat, pageEdgeLayeredMat, rightPageSurfaceMat, pageEdgeLayeredMat]
      );
      rightPageBlock.position.set(0.83, 0, 0.055);
      rightPageBlock.receiveShadow = true;
      rightSidePivot.add(rightPageBlock);

      // 4. Bookmark Ribbon (Pita Pembatas Buku Emas)
      const ribbonGeo = trackGeo(new THREE.PlaneGeometry(0.075, 1.38, 2, 14));
      const ribbonPos = ribbonGeo.attributes.position;
      for (let i = 0; i < ribbonPos.count; i++) {
        const ry = ribbonPos.getY(i);
        ribbonPos.setZ(i, Math.sin(ry * 2.8) * 0.055);
      }
      ribbonGeo.computeVertexNormals();
      const bookmarkRibbon = new THREE.Mesh(ribbonGeo, goldFoilMat);
      bookmarkRibbon.position.set(0, -1.52, 0.07);
      heroTome.add(bookmarkRibbon);

      // 5. Halaman Melengkung (PlaneGeometry dengan Deformasi Vertex Terkontrol & Analitis)
      const dynamicPages = [];
      const pageWidth = 1.60;
      const pageHeight = 2.32;
      const totalSheets = 3;

      for (let p = 0; p < totalSheets; p++) {
        const pGeo = trackGeo(new THREE.PlaneGeometry(pageWidth, pageHeight, 24, 8));
        pGeo.translate(pageWidth / 2, 0, 0);
        const pMesh = new THREE.Mesh(pGeo, p % 2 === 0 ? rightPageSurfaceMat : leftPageSurfaceMat);
        pMesh.position.set(0, 0, 0.128 + p * 0.007);
        pMesh.castShadow = true;
        heroTome.add(pMesh);

        dynamicPages.push({
          mesh: pMesh,
          geo: pGeo,
          baseX: Float32Array.from(pGeo.attributes.position.array.filter((_, idx) => idx % 3 === 0)),
          baseY: Float32Array.from(pGeo.attributes.position.array.filter((_, idx) => idx % 3 === 1)),
          phaseOffset: p / totalSheets
        });
      }

      // 6. Buku Arsip Pendamping (7 Buku dengan Variasi Proporsi, Ketebalan & Judul Katalog Aktual)
      const constellationGroup = new THREE.Group();
      stageRoot.add(constellationGroup);

      const companionBooks = [];
      const companionSpecs = [
        { w: 0.58, h: 0.82, d: 0.11 },
        { w: 0.64, h: 0.92, d: 0.14 },
        { w: 0.54, h: 0.78, d: 0.10 },
        { w: 0.68, h: 0.96, d: 0.16 },
        { w: 0.60, h: 0.86, d: 0.12 },
        { w: 0.62, h: 0.90, d: 0.15 },
        { w: 0.56, h: 0.80, d: 0.11 }
      ];

      companionSpecs.forEach((spec, i) => {
        const item = books[i] || {
          judul: `Codex Arsip Vol. ${i + 1}`,
          penulis: 'Arsip Ilustratif',
          kategori: 'Arsip'
        };

        const cGroup = new THREE.Group();
        const cTex = createCompanionCoverTexture(item, i);
        const cCoverMat = trackMat(new THREE.MeshStandardMaterial({
          map: cTex,
          roughness: 0.44,
          metalness: 0.14
        }));

        const cGeo = trackGeo(new THREE.BoxGeometry(spec.w, spec.h, spec.d));
        const cMesh = new THREE.Mesh(
          cGeo,
          [pageEdgeLayeredMat, goldFoilMat, pageEdgeLayeredMat, pageEdgeLayeredMat, cCoverMat, navyBoardMat]
        );
        cMesh.castShadow = true;
        cGroup.add(cMesh);

        const total = companionSpecs.length;
        const angle = (i / total) * Math.PI * 2;
        const radius = 3.15 + (i % 2) * 0.48;
        const spiralY = (i - (total - 1) / 2) * 0.44;

        cGroup.position.set(Math.cos(angle) * radius, spiralY, Math.sin(angle) * radius);
        cGroup.userData = {
          index: i,
          baseAngle: angle,
          baseRadius: radius,
          spiralY: spiralY,
          orbitSpeed: 0.12 + (i % 3) * 0.03
        };

        constellationGroup.add(cGroup);
        companionBooks.push(cGroup);
      });

      // 7. Partikel Debu Cahaya Biru (#93C5FD) & Emas (#D4AF37) Terukur
      const maxParticles = 96;
      const pGeo = trackGeo(new THREE.BufferGeometry());
      const pPositions = new Float32Array(maxParticles * 3);
      const pColors = new Float32Array(maxParticles * 3);

      const colorBlue = new THREE.Color(0x93C5FD);
      const colorGold = new THREE.Color(0xD4AF37);

      for (let i = 0; i < maxParticles; i++) {
        pPositions[i * 3] = (Math.random() - 0.5) * 13;
        pPositions[i * 3 + 1] = (Math.random() - 0.5) * 8;
        pPositions[i * 3 + 2] = (Math.random() - 0.5) * 9;

        const c = i % 3 === 0 ? colorGold : colorBlue;
        pColors[i * 3] = c.r;
        pColors[i * 3 + 1] = c.g;
        pColors[i * 3 + 2] = c.b;
      }
      pGeo.setAttribute('position', new THREE.BufferAttribute(pPositions, 3));
      pGeo.setAttribute('color', new THREE.BufferAttribute(pColors, 3));

      const pMat = trackMat(new THREE.PointsMaterial({
        size: 0.048,
        vertexColors: true,
        transparent: true,
        opacity: 0.55
      }));
      const archiveDust = new THREE.Points(pGeo, pMat);
      stageRoot.add(archiveDust);

      // --- PENGATURAN MODE KUALITAS VISUAL & GRACEFUL DEGRADATION ---
      function applyQualitySettings(mode) {
        currentQuality = mode;
        const width = window.innerWidth;
        const height = window.innerHeight;

        if (mode === 'high') {
          renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2.0));
          renderer.shadowMap.enabled = true;
          pGeo.setDrawRange(0, maxParticles);
          archiveDust.visible = true;
        } else if (mode === 'standard') {
          renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
          renderer.shadowMap.enabled = true;
          pGeo.setDrawRange(0, Math.floor(maxParticles * 0.55));
          archiveDust.visible = true;
        } else {
          // Mode Reduced / Statis
          renderer.setPixelRatio(1.0);
          renderer.shadowMap.enabled = false;
          archiveDust.visible = false;
        }
        renderer.setSize(width, height);
      }

      applyQualitySettings(currentQuality);

      if (qualitySelect) {
        qualitySelect.addEventListener('change', (e) => {
          applyQualitySettings(e.target.value);
          renderSingleFrame();
        });
      }

      prefersReducedMotionQuery.addEventListener('change', (e) => {
        const nextMode = e.matches ? 'reduced' : 'standard';
        if (qualitySelect) qualitySelect.value = nextMode;
        applyQualitySettings(nextMode);
      });

      // --- PEMETAAN SCROLL BERDASARKAN POSISI SECTION AKTUAL (BEBAS REFLOW PER-FRAME) ---
      let sectionAnchors = {
        hero: 0,
        circulation: 0.25,
        statistics: 0.55,
        catalog: 0.80,
        maxScroll: 1
      };

      function measureSectionPositions() {
        const docHeight = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
        const getTop = (id) => {
          const el = document.getElementById(id);
          if (!el) return 0;
          const rect = el.getBoundingClientRect();
          return Math.max(0, rect.top + window.scrollY - window.innerHeight * 0.28);
        };

        sectionAnchors = {
          hero: 0,
          circulation: Math.min(docHeight, getTop('cara-kerja')),
          statistics: Math.min(docHeight, getTop('statistik')),
          catalog: Math.min(docHeight, getTop('katalog')),
          maxScroll: docHeight
        };
      }

      //konversi scrollY aktual menjadi progres koreografi ternormalisasi [0..1]
      // Stage 1 (Hero): 0.00 - 0.25
      // Stage 2 (Circulation): 0.25 - 0.55
      // Stage 3 (Statistics): 0.55 - 0.80
      // Stage 4 (Catalog & Footer): 0.80 - 1.00
      function computeStageProgressFromScroll(scrollY) {
        const { circulation, statistics, catalog, maxScroll } = sectionAnchors;
        if (scrollY <= circulation) {
          const t = circulation > 0 ? scrollY / circulation : 0;
          return t * 0.25;
        }
        if (scrollY <= statistics) {
          const span = Math.max(1, statistics - circulation);
          const t = (scrollY - circulation) / span;
          return 0.25 + t * 0.30;
        }
        if (scrollY <= catalog) {
          const span = Math.max(1, catalog - statistics);
          const t = (scrollY - statistics) / span;
          return 0.55 + t * 0.25;
        }
        const span = Math.max(1, maxScroll - catalog);
        const t = Math.min(1, Math.max(0, (scrollY - catalog) / span));
        return 0.80 + t * 0.20;
      }

      let targetStageProgress = 0;
      let smoothStageProgress = 0;
      let docScrollRatio = 0;
      let pointerTargetX = 0;
      let pointerTargetY = 0;
      let pointerCurrentX = 0;
      let pointerCurrentY = 0;
      let lastPointerMoveTime = 0;
      const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

      function onScrollPassive() {
        const y = window.scrollY;
        docScrollRatio = Math.min(1, Math.max(0, y / Math.max(1, sectionAnchors.maxScroll)));
        targetStageProgress = computeStageProgressFromScroll(y);

        if (progressBar) {
          progressBar.style.transform = `scaleX(${docScrollRatio})`;
        }
        if (liveScrollPct) {
          const pct = Math.round(docScrollRatio * 100).toString().padStart(2, '0');
          liveScrollPct.textContent = `${pct}%`;
        }
      }

      measureSectionPositions();
      onScrollPassive();
      window.addEventListener('scroll', onScrollPassive, { passive: true });

      if (!isTouchDevice) {
        window.addEventListener('pointermove', (e) => {
          // Batasi rentang parallax pointer agar halus & terkontrol
          pointerTargetX = Math.max(-0.65, Math.min(0.65, (e.clientX / window.innerWidth - 0.5) * 1.3));
          pointerTargetY = Math.max(-0.55, Math.min(0.55, (e.clientY / window.innerHeight - 0.5) * 1.1));
          lastPointerMoveTime = performance.now();
        }, { passive: true });
      }

      // ResizeObserver untuk menjaga proporsi tanpa membaca DOM berulang di setiap frame
      const resizeObserver = new ResizeObserver(() => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        applyQualitySettings(currentQuality);
        measureSectionPositions();
        onScrollPassive();
      });
      resizeObserver.observe(document.body);

      // --- INTERPOLASI & UPDATE SCENE (FRAME-RATE INDEPENDENT DAMPING) ---
      const clamp01 = (v) => Math.min(1, Math.max(0, v));
      const lerp = (a, b, t) => a + (b - a) * t;
      const damp = (a, b, lambda, dt) => lerp(a, b, 1 - Math.exp(-lambda * dt));
      const smoothstep = (min, max, val) => {
        const x = clamp01((val - min) / (max - min));
        return x * x * (3 - 2 * x);
      };

      let elapsedTime = 0;
      let lastFrameTime = performance.now();
      let lastStageCode = '';

      function updateSceneChoreography(dt, now) {
        const isReduced = currentQuality === 'reduced';
        const effectiveDt = Math.min(dt, 0.1);

        if (!isReduced) {
          elapsedTime += effectiveDt;
        }

        // Jika pointer berhenti bergerak lebih dari 1.8 detik, kembalikan ke pose dasar secara halus
        if (!isTouchDevice && (now - lastPointerMoveTime > 1800)) {
          pointerTargetX = damp(pointerTargetX, 0, 2.5, effectiveDt);
          pointerTargetY = damp(pointerTargetY, 0, 2.5, effectiveDt);
        }

        const prevProgress = smoothStageProgress;
        smoothStageProgress = isReduced
          ? targetStageProgress
          : damp(smoothStageProgress, targetStageProgress, 6.5, effectiveDt);

        const scrollSpeed = Math.min(1.2, Math.abs(smoothStageProgress - prevProgress) / Math.max(0.001, effectiveDt) * 1.6);

        pointerCurrentX = isReduced ? 0 : damp(pointerCurrentX, pointerTargetX, 5.0, effectiveDt);
        pointerCurrentY = isReduced ? 0 : damp(pointerCurrentY, pointerTargetY, 5.0, effectiveDt);

        const s = smoothStageProgress;
        const isDesktop = window.innerWidth >= 1024;
        const isTablet = window.innerWidth >= 640 && window.innerWidth < 1024;

        // Bobot transisi halus antar 4 Tahap Scroll
        const wStage2 = smoothstep(0.18, 0.34, s) * (1 - smoothstep(0.48, 0.60, s));
        const wStage3 = smoothstep(0.48, 0.62, s) * (1 - smoothstep(0.75, 0.85, s));
        const wStage4 = smoothstep(0.75, 0.94, s);

        // Update label indikator tahap hanya ketika tahap berubah
        let nextStageCode = 'STAGE 01 // HERO TOME';
        if (s >= 0.25 && s < 0.55) {
          nextStageCode = 'STAGE 02 // CIRCULATION ISO';
        } else if (s >= 0.55 && s < 0.80) {
          nextStageCode = 'STAGE 03 // ARCHIVE RING';
        } else if (s >= 0.80) {
          nextStageCode = 'STAGE 04 // CLOSED ARCHIVE';
        }
        if (phaseLabel && nextStageCode !== lastStageCode) {
          phaseLabel.textContent = nextStageCode;
          lastStageCode = nextStageCode;
        }

        // Penyesuaian skala responsif untuk layar kecil
        const targetScale = isDesktop ? 1.0 : (isTablet ? 0.84 : 0.68);
        stageRoot.scale.setScalar(damp(stageRoot.scale.x, targetScale, 6.0, effectiveDt));

        // Posisi Buku Utama berdasarkan Tahap Scroll
        let targetTomeX = isDesktop ? 1.95 : 0;
        if (s >= 0.25 && s < 0.55) {
          targetTomeX = isDesktop ? 1.65 : 0;
        } else if (s >= 0.55 && s < 0.80) {
          targetTomeX = 0;
        } else if (s >= 0.80) {
          targetTomeX = isDesktop ? 2.05 : 0;
        }

        const floatOffset = isReduced ? 0 : Math.sin(elapsedTime * 1.25) * 0.065;
        const targetTomeY = floatOffset + wStage2 * 0.18 - wStage4 * 0.12;

        heroTome.position.x = damp(heroTome.position.x, targetTomeX, 5.5, effectiveDt);
        heroTome.position.y = damp(heroTome.position.y, targetTomeY, 5.5, effectiveDt);

        // Sudut Buka/Tutup Sampul Buku Utama:
        // Stage 1: Terbuka tiga perempat | Stage 2: Terbuka lebih lebar | Stage 4: Menutup sampul depan
        const openAngle = lerp(0.22, 0.05, wStage2);
        const targetLeftPivotY = lerp(openAngle, Math.PI - 0.035, wStage4);
        const targetRightPivotY = lerp(-openAngle, 0.02, wStage4);

        leftSidePivot.rotation.y = damp(leftSidePivot.rotation.y, targetLeftPivotY, 6.5, effectiveDt);
        rightSidePivot.rotation.y = damp(rightSidePivot.rotation.y, targetRightPivotY, 6.5, effectiveDt);

        // Rotasi Tiga Perempat -> Top-Down Isometric -> Ring -> Pose Akhir Tertutup Elegan
        const targetRotX = -0.32 + wStage2 * 0.68 - wStage4 * 0.22 + pointerCurrentY * 0.14;
        const targetRotY = -0.36 + wStage2 * 0.42 + wStage3 * 0.30 - wStage4 * 1.12 + pointerCurrentX * 0.20;
        const targetRotZ = 0.06 - wStage2 * 0.18 + wStage4 * 0.04;

        heroTome.rotation.x = damp(heroTome.rotation.x, targetRotX, 5.8, effectiveDt);
        heroTome.rotation.y = damp(heroTome.rotation.y, targetRotY, 5.8, effectiveDt);
        heroTome.rotation.z = damp(heroTome.rotation.z, targetRotZ, 5.8, effectiveDt);

        // Gerakan Halus Bookmark Ribbon
        bookmarkRibbon.rotation.z = isReduced ? 0 : Math.sin(elapsedTime * 1.6) * 0.05;

        // Deformasi Vertex Halaman Melengkung (Stabil, Terbatas & Bebas Flip)
        const pageVisScale = 1 - smoothstep(0.74, 0.88, s);
        dynamicPages.forEach((pg) => {
          const isPageVisible = pageVisScale > 0.02;
          pg.mesh.visible = isPageVisible;
          if (!isPageVisible) return;

          pg.mesh.scale.setScalar(pageVisScale);

          // Siklus halaman yang stabil dan terkontrol
          const baseCycle = isReduced
            ? (s * 1.8 + pg.phaseOffset) % 1
            : (elapsedTime * (0.14 + wStage2 * 0.12) + s * 2.4 + pg.phaseOffset) % 1;

          // Batasi sudut dari 0.08*PI hingga 0.92*PI agar permukaan tidak pernah terbalik
          const safeCycle = 0.08 + baseCycle * 0.84;
          const turnAngle = safeCycle * Math.PI;
          const sinTurn = Math.sin(turnAngle);
          const cosTurn = Math.cos(turnAngle);

          // Amplitudo lengkungan halaman terkontrol
          const archAmplitude = (0.19 + Math.min(0.10, scrollSpeed * 0.08)) * Math.sin(safeCycle * Math.PI);

          const posAttr = pg.geo.attributes.position;
          const normAttr = pg.geo.attributes.normal;
          const baseXArr = pg.baseX;
          const baseYArr = pg.baseY;

          for (let i = 0; i < posAttr.count; i++) {
            const bx = baseXArr[i];
            const by = baseYArr[i];
            const normX = bx / pageWidth; // [0..1]

            const arch = Math.sin(normX * Math.PI) * archAmplitude;
            const rx = cosTurn * bx - sinTurn * arch;
            const rz = sinTurn * bx + cosTurn * arch;

            posAttr.setXYZ(i, rx, by, rz);

            // Turunan analitis untuk normal permukaan yang mulus tanpa biaya computeVertexNormals penuh
            const dArchDx = (Math.PI / pageWidth) * Math.cos(normX * Math.PI) * archAmplitude;
            const tx = cosTurn - sinTurn * dArchDx;
            const tz = sinTurn + cosTurn * dArchDx;
            const invLen = 1 / Math.hypot(tx, tz);
            normAttr.setXYZ(i, -tz * invLen, 0, tx * invLen);
          }
          posAttr.needsUpdate = true;
          normAttr.needsUpdate = true;
        });

        // Koreografi 7 Buku Pendamping (Spiral -> Komposisi Sirkulasi -> Cincin Indeks)
        const totalComp = companionBooks.length;
        companionBooks.forEach((b) => {
          const ud = b.userData;
          const i = ud.index;

          const orbitAngle = ud.baseAngle + (isReduced ? 0 : elapsedTime * ud.orbitSpeed) + s * 1.4;
          const spiralX = Math.cos(orbitAngle) * ud.baseRadius;
          const spiralY = ud.spiralY + (isReduced ? 0 : Math.sin(elapsedTime * 1.1 + i) * 0.14);
          const spiralZ = Math.sin(orbitAngle) * ud.baseRadius;

          // Stage 2: Komposisi Arsitektural Sirkulasi di sisi kiri/belakang
          const circX = (i - (totalComp - 1) / 2) * 0.98 - (isDesktop ? 0.8 : 0);
          const circY = ((i % 2 === 0) ? 0.55 : -0.55) + (isReduced ? 0 : Math.sin(elapsedTime + i) * 0.12);
          const circZ = -1.35 + (i % 3) * 0.35;

          // Stage 3: Susunan Cincin Indeks Presisi mengelilingi panggung tanpa menutupi teks utama
          const ringAngle = (i / totalComp) * Math.PI * 2 + (isReduced ? 0 : elapsedTime * 0.18);
          const ringRadius = isDesktop ? 3.85 : 3.1;
          const ringX = Math.cos(ringAngle) * ringRadius;
          const ringY = Math.sin(ringAngle) * 1.55;
          const ringZ = Math.sin(ringAngle) * 1.65 - 0.8;

          let tx = lerp(spiralX, circX, wStage2);
          let ty = lerp(spiralY, circY, wStage2);
          let tz = lerp(spiralZ, circZ, wStage2);

          tx = lerp(tx, ringX, wStage3);
          ty = lerp(ty, ringY, wStage3);
          tz = lerp(tz, ringZ, wStage3);

          b.position.x = damp(b.position.x, tx, 5.5, effectiveDt);
          b.position.y = damp(b.position.y, ty, 5.5, effectiveDt);
          b.position.z = damp(b.position.z, tz, 5.5, effectiveDt);

          b.rotation.y = -orbitAngle + wStage2 * 0.5;
          b.rotation.x = lerp(0.22, 0.04, wStage3);
        });

        // Pencahayaan Cobalt menonjolkan siluet objek di Stage 3
        cobaltRimLight.intensity = lerp(5.5, 10.5, wStage3);

        if (!isReduced) {
          archiveDust.rotation.y = elapsedTime * 0.025 + s * 0.45;
        }

        // Koreografi Kamera 4-Tahap
        const targetCamX = pointerCurrentX * 0.38 - wStage2 * 0.45;
        const targetCamY = lerp(1.15, 3.05, wStage2) - wStage4 * 0.30 - pointerCurrentY * 0.28;
        const targetCamZ = lerp(8.8, 6.5, wStage2) + wStage3 * 0.55 + wStage4 * 1.15;

        camera.position.x = damp(camera.position.x, targetCamX, 5.2, effectiveDt);
        camera.position.y = damp(camera.position.y, targetCamY, 5.2, effectiveDt);
        camera.position.z = damp(camera.position.z, targetCamZ, 5.2, effectiveDt);
        camera.lookAt(heroTome.position.x * 0.42, heroTome.position.y * 0.28, 0);
      }

      function renderSingleFrame() {
        const now = performance.now();
        updateSceneChoreography(0.016, now);
        renderer.render(scene, camera);
      }

      // --- PENJADWALAN ANIMASI TUNGGAL (HENTIKAN SAAT TAB TIDAK AKTIF ATAU STAGE TIDAK TERLIHAT) ---
      let rafId = null;
      let isTabVisible = !document.hidden;
      let isStageVisible = true;

      function updateLoopState() {
        const shouldRun = isTabVisible && isStageVisible;
        if (shouldRun && rafId === null) {
          lastFrameTime = performance.now();
          rafId = requestAnimationFrame(tickLoop);
        } else if (!shouldRun && rafId !== null) {
          cancelAnimationFrame(rafId);
          rafId = null;
        }
      }

      function tickLoop(now) {
        rafId = null;
        const dt = (now - lastFrameTime) / 1000;
        lastFrameTime = now;

        updateSceneChoreography(dt, now);
        renderer.render(scene, camera);

        if (isTabVisible && isStageVisible) {
          rafId = requestAnimationFrame(tickLoop);
        }
      }

      document.addEventListener('visibilitychange', () => {
        isTabVisible = !document.hidden;
        updateLoopState();
      });

      if ('IntersectionObserver' in window) {
        const stageObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            isStageVisible = entry.isIntersecting;
            updateLoopState();
          });
        }, { threshold: 0.01 });
        stageObserver.observe(container);
      }

      // Mulai animation loop utama tunggal
      updateLoopState();

      // Pembersihan resource WebGL & listener saat halaman ditutup
      window.addEventListener('pagehide', () => {
        if (rafId !== null) {
          cancelAnimationFrame(rafId);
          rafId = null;
        }
        resizeObserver.disconnect();
        createdGeometries.forEach(g => g.dispose());
        createdMaterials.forEach(m => m.dispose());
        createdTextures.forEach(t => t.dispose());
        renderer.dispose();
      });
    }
  </script>
</body>
</html>
