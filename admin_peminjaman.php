<?php
session_start();

if (!isset($_SESSION['UserID']) || ($_SESSION['Role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

require 'koneksi.php';

// ============================================================
// Halaman aktif
// ============================================================
$page = $_GET['page'] ?? 'buku';
if (!in_array($page, ['buku', 'peminjaman', 'user'])) $page = 'buku';

$success_msg = '';
$error_msg   = '';

// ============================================================
// PROSES TAMBAH BUKU
// ============================================================
if (isset($_POST['tambah_buku'])) {
    $judul    = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $penulis  = mysqli_real_escape_string($koneksi, trim($_POST['penulis'] ?? ''));
    $penerbit = mysqli_real_escape_string($koneksi, trim($_POST['penerbit'] ?? ''));
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? ''));
    $stok     = max(0, (int)($_POST['stok'] ?? 0));
    $isbn     = mysqli_real_escape_string($koneksi, trim($_POST['isbn'] ?? ''));
    $tahun    = (int)($_POST['tahun'] ?? 0);

    if ($judul === '' || $penulis === '' || $kategori === '') {
        $error_msg = "Judul, penulis, dan kategori wajib diisi.";
    } else {
        $cover = 'default.jpg';
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
            $file_tmp  = $_FILES['cover']['tmp_name'];
            $file_name = basename($_FILES['cover']['name']);
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $cover = 'cover_' . time() . '.' . $file_ext;
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                move_uploaded_file($file_tmp, 'uploads/' . $cover);
            }
        }

        $insert = mysqli_query($koneksi,
            "INSERT INTO buku (Judul, Penulis, Penerbit, Kategori, Stok, ISBN, TahunTerbit, Cover)
             VALUES ('$judul', '$penulis', '$penerbit', '$kategori', $stok, '$isbn', $tahun, '$cover')");

        if ($insert) {
            $success_msg = "Buku \"" . htmlspecialchars($judul) . "\" berhasil ditambahkan.";
        } else {
            $error_msg = "Gagal menambahkan buku: " . mysqli_error($koneksi);
        }
    }
}

// ============================================================
// PROSES UPDATE BUKU
// ============================================================
if (isset($_POST['update_buku'])) {
    $buku_id  = (int)($_POST['buku_id'] ?? 0);
    $judul    = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $penulis  = mysqli_real_escape_string($koneksi, trim($_POST['penulis'] ?? ''));
    $penerbit = mysqli_real_escape_string($koneksi, trim($_POST['penerbit'] ?? ''));
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? ''));
    $stok     = max(0, (int)($_POST['stok'] ?? 0));
    $isbn     = mysqli_real_escape_string($koneksi, trim($_POST['isbn'] ?? ''));
    $tahun    = (int)($_POST['tahun'] ?? 0);

    if ($buku_id <= 0) {
        $error_msg = "ID buku tidak valid.";
    } elseif ($judul === '' || $penulis === '' || $kategori === '') {
        $error_msg = "Judul, penulis, dan kategori wajib diisi.";
    } else {
        // Ambil cover lama
        $q_old = mysqli_query($koneksi, "SELECT Cover FROM buku WHERE BukuID = $buku_id LIMIT 1");
        $old   = $q_old ? mysqli_fetch_assoc($q_old) : null;

        if (!$old) {
            $error_msg = "Buku tidak ditemukan di database.";
        } else {
            $cover = $old['Cover'];

            // Kalau upload cover baru
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
                $file_tmp  = $_FILES['cover']['tmp_name'];
                $file_name = basename($_FILES['cover']['name']);
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $new_cover = 'cover_' . time() . '.' . $file_ext;
                    if (!is_dir('uploads')) mkdir('uploads', 0755, true);

                    if (move_uploaded_file($file_tmp, 'uploads/' . $new_cover)) {
                        // Hapus cover lama (kecuali default)
                        if ($cover !== 'default.jpg' && file_exists('uploads/' . $cover)) {
                            @unlink('uploads/' . $cover);
                        }
                        $cover = $new_cover;
                    }
                }
            }

            $update = mysqli_query($koneksi,
                "UPDATE buku SET
                    Judul = '$judul',
                    Penulis = '$penulis',
                    Penerbit = '$penerbit',
                    Kategori = '$kategori',
                    Stok = $stok,
                    ISBN = '$isbn',
                    TahunTerbit = $tahun,
                    Cover = '$cover'
                 WHERE BukuID = $buku_id");

            if ($update) {
                $success_msg = "Buku \"" . htmlspecialchars($judul) . "\" berhasil diperbarui.";
            } else {
                $error_msg = "Gagal memperbarui buku: " . mysqli_error($koneksi);
            }
        }
    }
}

// ============================================================
// HAPUS BUKU
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus_buku' && isset($_GET['id'])) {
    $id_buku = (int)$_GET['id'];
    $r = mysqli_query($koneksi, "SELECT Cover FROM buku WHERE BukuID = $id_buku");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        if ($row['Cover'] !== 'default.jpg' && file_exists('uploads/' . $row['Cover'])) {
            @unlink('uploads/' . $row['Cover']);
        }
    }
    mysqli_query($koneksi, "DELETE FROM buku WHERE BukuID = $id_buku");
    $_SESSION['admin_success'] = "Buku berhasil dihapus.";
    header("Location: admin_peminjaman.php?page=buku");
    exit;
}

// ============================================================
// HAPUS USER
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus_user' && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    if ($id_user !== (int)$_SESSION['UserID']) {
        mysqli_query($koneksi, "DELETE FROM user WHERE UserID = $id_user");
        $_SESSION['admin_success'] = "User berhasil dihapus.";
    } else {
        $_SESSION['admin_error'] = "Tidak dapat menghapus akun Anda sendiri.";
    }
    header("Location: admin_peminjaman.php?page=user");
    exit;
}

// Ambil pesan dari session
if (isset($_SESSION['admin_success'])) {
    $success_msg = $_SESSION['admin_success'];
    unset($_SESSION['admin_success']);
}
if (isset($_SESSION['admin_error'])) {
    $error_msg = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Digital Library</title>
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
        .btn-danger {
            background: #fff; color: var(--danger);
            border: 1px solid var(--danger-line);
        }
        .btn-danger:hover { background: var(--danger-bg); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #005238; }

        /* Input */
        .field {
            width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            color: var(--ink);
            font-size: 13px;
            padding: 8px 10px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 2px rgba(0,82,204,.14);
        }
        .field::placeholder { color: #A5ADBA; }

        .label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

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
            white-space: nowrap;
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

        /* Modal */
        .modal-backdrop { background: rgba(9, 30, 66, .54); }
        .modal {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 12px 24px -6px rgba(9,30,66,.18);
            width: 100%;
            overflow: hidden;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* Focus */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Cover preview */
        .cover-thumb {
            width: 44px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background: var(--brand-light);
            border: 1px solid var(--line);
            flex-shrink: 0;
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
            Administrasi
        </div>

        <nav class="flex-1 px-2 space-y-0.5">
            <a href="admin_peminjaman.php?page=buku"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition"
               style="<?= $page==='buku'
                    ? 'background: var(--brand); color: #fff;'
                    : 'color: var(--ink-2);' ?>"
               onmouseover="<?= $page==='buku' ? '' : "this.style.background='#F4F5F7'" ?>"
               onmouseout="<?= $page==='buku' ? '' : "this.style.background='transparent'" ?>">
                <i class="fas fa-book w-4 text-center text-[12px]"></i>
                <span>Kelola Buku</span>
            </a>
            <a href="admin_peminjaman.php?page=peminjaman"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition"
               style="<?= $page==='peminjaman'
                    ? 'background: var(--brand); color: #fff;'
                    : 'color: var(--ink-2);' ?>"
               onmouseover="<?= $page==='peminjaman' ? '' : "this.style.background='#F4F5F7'" ?>"
               onmouseout="<?= $page==='peminjaman' ? '' : "this.style.background='transparent'" ?>">
                <i class="fas fa-book-open-reader w-4 text-center text-[12px]"></i>
                <span>Kelola Peminjaman</span>
            </a>
            <a href="admin_peminjaman.php?page=user"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium transition"
               style="<?= $page==='user'
                    ? 'background: var(--brand); color: #fff;'
                    : 'color: var(--ink-2);' ?>"
               onmouseover="<?= $page==='user' ? '' : "this.style.background='#F4F5F7'" ?>"
               onmouseout="<?= $page==='user' ? '' : "this.style.background='transparent'" ?>">
                <i class="fas fa-users w-4 text-center text-[12px]"></i>
                <span>Kontrol User</span>
            </a>
        </nav>

        <div class="border-t border-[#EBECF0] p-3">
            <div class="flex items-center gap-3 px-2 py-2 mb-1">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['NamaLengkap'] ?? 'Admin'); ?>&background=DEEBFF&color=0052CC&bold=true"
                     alt="" class="w-8 h-8 rounded-full flex-shrink-0">
                <div class="min-w-0">
                    <div class="text-[13px] font-semibold truncate" style="color: var(--ink);">
                        <?= htmlspecialchars($_SESSION['NamaLengkap'] ?? 'Admin'); ?>
                    </div>
                    <div class="text-[11px] truncate" style="color: var(--brand); font-weight: 600;">
                        Administrator
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
                <span style="color: var(--muted);">Admin</span>
                <span style="color: var(--line);">/</span>
                <span class="font-medium" style="color: var(--ink);">
                    <?= $page==='buku' ? 'Kelola Buku' : ($page==='peminjaman' ? 'Kelola Peminjaman' : 'Kontrol User'); ?>
                </span>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-[11px] font-semibold"
                      style="background: var(--brand-light); border-color: #B3D4FF; color: var(--brand);">
                    <i class="fas fa-shield-halved text-[10px]"></i>
                    Admin
                </span>
                <div class="w-px h-5" style="background: var(--line);"></div>
                <span class="text-[12px] font-medium hidden sm:inline" style="color: var(--ink-2);">
                    <?= htmlspecialchars($_SESSION['NamaLengkap'] ?? 'Admin'); ?>
                </span>
            </div>
        </header>

        <!-- Scroll area -->
        <div class="flex-1 overflow-y-auto min-h-0">
            <div class="p-6 max-w-[1200px] space-y-5">

                <!-- Notifikasi -->
                <?php if (!empty($success_msg)): ?>
                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                         style="background: var(--success-bg); border-color: var(--success-line); color: var(--success);">
                        <i class="fas fa-circle-check mt-0.5 text-[12px]"></i>
                        <span class="font-medium"><?= htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-md border text-[13px]"
                         style="background: var(--danger-bg); border-color: var(--danger-line); color: var(--danger);">
                        <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                        <span class="font-medium"><?= htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Header halaman -->
                <div>
                    <h1 class="text-[20px] font-semibold tracking-tight leading-tight" style="color: var(--ink);">
                        <?php if ($page==='buku'): ?>
                            Kelola Katalog Buku
                        <?php elseif ($page==='peminjaman'): ?>
                            Monitor Peminjaman
                        <?php else: ?>
                            Kontrol Data User
                        <?php endif; ?>
                    </h1>
                    <p class="text-[13px] mt-1" style="color: var(--muted);">
                        <?php if ($page==='buku'): ?>
                            Tambah, edit, lihat, dan hapus buku dari katalog perpustakaan.
                        <?php elseif ($page==='peminjaman'): ?>
                            Pantau dan konfirmasi pengembalian buku dari user.
                        <?php else: ?>
                            Kelola akun yang terdaftar dalam sistem perpustakaan.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- ================================================== -->
                <!-- ============ HALAMAN: KELOLA BUKU =============== -->
                <!-- ================================================== -->
                <?php if ($page === 'buku'): ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                        <!-- Form tambah buku -->
                        <div class="lg:col-span-1">
                            <div class="card p-5 lg:sticky lg:top-4">
                                <div class="flex items-center gap-2 mb-5 pb-4 border-b" style="border-color: var(--line-soft);">
                                    <i class="fas fa-plus text-[11px]" style="color: var(--brand);"></i>
                                    <span class="text-[13px] font-semibold" style="color: var(--ink);">
                                        Tambah Buku Baru
                                    </span>
                                </div>

                                <form method="POST" enctype="multipart/form-data" class="space-y-4">

                                    <div>
                                        <label class="label">Judul <span style="color: var(--danger);">*</span></label>
                                        <input type="text" name="judul" required
                                               placeholder="Judul lengkap buku"
                                               class="field">
                                    </div>

                                    <div>
                                        <label class="label">Penulis <span style="color: var(--danger);">*</span></label>
                                        <input type="text" name="penulis" required
                                               placeholder="Nama penulis"
                                               class="field">
                                    </div>

                                    <div>
                                        <label class="label">Penerbit</label>
                                        <input type="text" name="penerbit"
                                               placeholder="Nama penerbit"
                                               class="field">
                                    </div>

                                    <div>
                                        <label class="label">Kategori <span style="color: var(--danger);">*</span></label>
                                        <select name="kategori" required class="field">
                                            <option value="">Pilih kategori</option>
                                            <option>Teknologi & Informatika</option>
                                            <option>Sains & Alam</option>
                                            <option>Sastra & Fiksi</option>
                                            <option>Sejarah & Budaya</option>
                                            <option>Bisnis & Ekonomi</option>
                                            <option>Filsafat & Agama</option>
                                            <option>Seni & Desain</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="label">ISBN</label>
                                            <input type="text" name="isbn" placeholder="978-…" class="field">
                                        </div>
                                        <div>
                                            <label class="label">Tahun</label>
                                            <input type="number" name="tahun" placeholder="2024" class="field" min="1900" max="2100">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="label">Stok <span style="color: var(--danger);">*</span></label>
                                        <input type="number" name="stok" value="1" required min="0" class="field">
                                    </div>

                                    <div>
                                        <label class="label">Cover</label>
                                        <input type="file" name="cover" accept="image/*"
                                               class="field text-[12px] file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-[11px] file:font-semibold file:cursor-pointer"
                                               style="padding: 6px;"
                                               onchange="previewCover(this, 'cover-preview')">
                                        <p class="text-[10px] mt-1.5" style="color: var(--muted);">
                                            JPG, PNG, GIF. Maks 5 MB.
                                        </p>
                                        <img id="cover-preview" class="hidden mt-2 w-full aspect-[3/4] object-cover rounded border"
                                             style="border-color: var(--line);" alt="">
                                    </div>

                                    <button type="submit" name="tambah_buku" class="btn btn-primary w-full mt-2">
                                        <i class="fas fa-floppy-disk text-[11px]"></i>
                                        <span>Simpan Buku</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Daftar buku -->
                        <div class="lg:col-span-2">
                            <div class="card overflow-hidden">
                                <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: var(--line-soft);">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-list text-[11px]" style="color: var(--muted);"></i>
                                        <span class="text-[13px] font-semibold" style="color: var(--ink);">
                                            Daftar Buku
                                        </span>
                                        <?php
                                        $count_buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM buku"))['t'] ?? 0;
                                        ?>
                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 rounded-full text-[10px] font-semibold tnum"
                                              style="background: var(--bg); color: var(--muted);">
                                            <?= (int)$count_buku; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th class="w-12">No</th>
                                                <th>Judul</th>
                                                <th>Kategori</th>
                                                <th class="w-24">Stok</th>
                                                <th class="w-44 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $q_buku = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY BukuID DESC");
                                            if ($q_buku && mysqli_num_rows($q_buku) > 0):
                                                $no = 1;
                                                while ($b = mysqli_fetch_assoc($q_buku)):
                                                    $stok = (int)$b['Stok'];
                                                    $stok_tersedia = $stok > 0;

                                                    // Siapkan data untuk modal edit (JSON, aman dari karakter aneh)
                                                    $edit_data = json_encode([
                                                        'id'       => (int)$b['BukuID'],
                                                        'judul'    => $b['Judul'],
                                                        'penulis'  => $b['Penulis'],
                                                        'penerbit' => $b['Penerbit'],
                                                        'kategori' => $b['Kategori'],
                                                        'stok'     => $stok,
                                                        'isbn'     => $b['ISBN'],
                                                        'tahun'    => $b['TahunTerbit'],
                                                        'cover'    => (!empty($b['Cover']) && $b['Cover'] !== 'default.jpg')
                                                                        ? 'uploads/' . $b['Cover']
                                                                        : null
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                                            ?>
                                                <tr>
                                                    <td class="tnum" style="color: var(--muted);"><?= $no++; ?></td>
                                                    <td>
                                                        <div class="flex items-center gap-3">
                                                            <?php
                                                            $has_cover = !empty($b['Cover']) && $b['Cover'] !== 'default.jpg' && file_exists('uploads/' . $b['Cover']);
                                                            ?>
                                                            <?php if ($has_cover): ?>
                                                                <img src="uploads/<?= htmlspecialchars($b['Cover']); ?>"
                                                                     alt="" class="cover-thumb">
                                                            <?php else: ?>
                                                                <div class="cover-thumb flex items-center justify-center"
                                                                     style="color: var(--brand);">
                                                                    <i class="fas fa-book text-[12px]"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="min-w-0">
                                                                <div class="text-[12.5px] font-semibold clamp-1" style="color: var(--ink);">
                                                                    <?= htmlspecialchars($b['Judul']); ?>
                                                                </div>
                                                                <div class="text-[11px] mt-0.5 clamp-1" style="color: var(--muted);">
                                                                    <?= htmlspecialchars($b['Penulis']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-[11.5px]" style="color: var(--ink-2);">
                                                            <?= htmlspecialchars($b['Kategori'] ?: '—'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge"
                                                              style="background: <?= $stok_tersedia ? 'var(--success-bg)' : 'var(--danger-bg)'; ?>;
                                                                     color: <?= $stok_tersedia ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                            <span class="w-1.5 h-1.5 rounded-full" style="background: currentColor;"></span>
                                                            <span class="tnum"><?= $stok; ?> pcs</span>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <button type="button"
                                                                    onclick='bukaEditBuku(<?= $edit_data; ?>)'
                                                                    class="btn btn-ghost">
                                                                <i class="fas fa-pen text-[10px]"></i>
                                                                <span>Edit</span>
                                                            </button>
                                                            <a href="admin_peminjaman.php?page=buku&action=hapus_buku&id=<?= (int)$b['BukuID']; ?>"
                                                               onclick="return confirm('Hapus buku &quot;<?= htmlspecialchars(addslashes($b['Judul'])); ?>&quot;?');"
                                                               class="btn btn-danger">
                                                                <i class="fas fa-trash text-[10px]"></i>
                                                                <span>Hapus</span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="5" class="py-12 text-center">
                                                        <i class="fas fa-inbox text-2xl mb-2 block" style="color: var(--line);"></i>
                                                        <div class="text-[12px]" style="color: var(--muted);">
                                                            Belum ada buku di katalog.
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- ================================================== -->
                <!-- ========= HALAMAN: KELOLA PEMINJAMAN ============= -->
                <!-- ================================================== -->
                <?php elseif ($page === 'peminjaman'): ?>

                    <div class="card overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: var(--line-soft);">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-book-open-reader text-[11px]" style="color: var(--muted);"></i>
                                <span class="text-[13px] font-semibold" style="color: var(--ink);">
                                    Peminjaman Aktif
                                </span>
                                <?php
                                $count_pinjam = mysqli_fetch_assoc(mysqli_query($koneksi,
                                    "SELECT COUNT(*) AS t FROM peminjaman WHERE TanggalPengembalian IS NULL"))['t'] ?? 0;
                                ?>
                                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 rounded-full text-[10px] font-semibold tnum"
                                      style="background: var(--bg); color: var(--muted);">
                                    <?= (int)$count_pinjam; ?>
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="w-12">No</th>
                                        <th>Peminjam</th>
                                        <th>Buku</th>
                                        <th class="w-32">Tgl Pinjam</th>
                                        <th class="w-40">Status</th>
                                        <th class="w-48 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_pinjam = mysqli_query($koneksi,
                                        "SELECT peminjaman.*, user.NamaLengkap, buku.Judul
                                         FROM peminjaman
                                         JOIN user ON peminjaman.UserID = user.UserID
                                         JOIN buku ON peminjaman.BukuID = buku.BukuID
                                         WHERE peminjaman.TanggalPengembalian IS NULL
                                         ORDER BY
                                            CASE WHEN peminjaman.Status = 'Menunggu Konfirmasi' THEN 0 ELSE 1 END,
                                            peminjaman.TanggalPeminjaman DESC");

                                    if ($q_pinjam && mysqli_num_rows($q_pinjam) > 0):
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($q_pinjam)):
                                            $is_pending = ($row['Status'] ?? '') === 'Menunggu Konfirmasi';

                                            $tgl_estimasi = strtotime($row['TanggalKembaliEstimasi']);
                                            $tgl_hariini  = strtotime(date('Y-m-d'));
                                            $telat = $tgl_estimasi < $tgl_hariini;

                                            if ($is_pending) {
                                                $badge_bg = 'var(--warning-bg)';
                                                $badge_fg = 'var(--warning)';
                                                $badge_line = 'var(--warning-line)';
                                                $badge_text = 'Menunggu Konfirmasi';
                                                $badge_icon = 'fa-hourglass-half';
                                            } elseif ($telat) {
                                                $badge_bg = 'var(--danger-bg)';
                                                $badge_fg = 'var(--danger)';
                                                $badge_line = 'var(--danger-line)';
                                                $badge_text = 'Terlambat';
                                                $badge_icon = 'fa-triangle-exclamation';
                                            } else {
                                                $badge_bg = 'var(--brand-light)';
                                                $badge_fg = 'var(--brand)';
                                                $badge_line = 'transparent';
                                                $badge_text = 'Dipinjam';
                                                $badge_icon = 'fa-book';
                                            }
                                    ?>
                                        <tr style="<?= $is_pending ? 'background: #FFFBF0;' : ''; ?>">
                                            <td class="tnum" style="color: var(--muted);"><?= $no++; ?></td>
                                            <td>
                                                <div class="text-[12.5px] font-semibold" style="color: var(--ink);">
                                                    <?= htmlspecialchars($row['NamaLengkap']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-[12.5px] clamp-1" style="color: var(--ink-2);">
                                                    <?= htmlspecialchars($row['Judul']); ?>
                                                </div>
                                            </td>
                                            <td class="tnum">
                                                <?= date('d M Y', strtotime($row['TanggalPeminjaman'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge"
                                                      style="background: <?= $badge_bg; ?>; color: <?= $badge_fg; ?>; border: 1px solid <?= $badge_line; ?>;">
                                                    <i class="fas <?= $badge_icon; ?> text-[9px]"></i>
                                                    <?= $badge_text; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="proses_kembali.php?id=<?= (int)$row['PeminjamanID']; ?>"
                                                   onclick="return confirm('Konfirmasi pengembalian buku ini? Stok akan otomatis bertambah.');"
                                                   class="btn <?= $is_pending ? 'btn-success' : 'btn-ghost'; ?>">
                                                    <i class="fas fa-check text-[10px]"></i>
                                                    <span><?= $is_pending ? 'Terima Pengembalian' : 'Konfirmasi Kembali'; ?></span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="py-12 text-center">
                                                <i class="fas fa-inbox text-2xl mb-2 block" style="color: var(--line);"></i>
                                                <div class="text-[12px]" style="color: var(--muted);">
                                                    Belum ada peminjaman aktif.
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- ================================================== -->
                <!-- ============ HALAMAN: KONTROL USER ================ -->
                <!-- ================================================== -->
                <?php else: ?>

                    <div class="card overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: var(--line-soft);">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-users text-[11px]" style="color: var(--muted);"></i>
                                <span class="text-[13px] font-semibold" style="color: var(--ink);">
                                    Daftar Akun
                                </span>
                                <?php
                                $count_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM user"))['t'] ?? 0;
                                ?>
                                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 rounded-full text-[10px] font-semibold tnum"
                                      style="background: var(--bg); color: var(--muted);">
                                    <?= (int)$count_user; ?>
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="w-12">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th class="w-32">Role</th>
                                        <th class="w-32 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_user = mysqli_query($koneksi, "SELECT * FROM user ORDER BY UserID ASC");
                                    if ($q_user && mysqli_num_rows($q_user) > 0):
                                        $no_u = 1;
                                        while ($usr = mysqli_fetch_assoc($q_user)):
                                            $is_admin = ($usr['Role'] ?? '') === 'admin';
                                            $is_self  = (int)$usr['UserID'] === (int)$_SESSION['UserID'];
                                    ?>
                                        <tr>
                                            <td class="tnum" style="color: var(--muted);"><?= $no_u++; ?></td>
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold flex-shrink-0"
                                                         style="background: <?= $is_admin ? 'var(--danger-bg)' : 'var(--brand-light)'; ?>;
                                                                color: <?= $is_admin ? 'var(--danger)' : 'var(--brand)'; ?>;">
                                                        <?= strtoupper(substr($usr['NamaLengkap'], 0, 1)); ?>
                                                    </div>
                                                    <div class="text-[12.5px] font-semibold clamp-1" style="color: var(--ink);">
                                                        <?= htmlspecialchars($usr['NamaLengkap']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="tnum">@<?= htmlspecialchars($usr['Username']); ?></td>
                                            <td class="clamp-1"><?= htmlspecialchars($usr['Email']); ?></td>
                                            <td>
                                                <span class="badge"
                                                      style="background: <?= $is_admin ? 'var(--danger-bg)' : 'var(--brand-light)'; ?>;
                                                             color: <?= $is_admin ? 'var(--danger)' : 'var(--brand)'; ?>;">
                                                    <i class="fas <?= $is_admin ? 'fa-shield-halved' : 'fa-user'; ?> text-[9px]"></i>
                                                    <?= $is_admin ? 'Admin' : 'User'; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($is_self): ?>
                                                    <span class="text-[11px] italic" style="color: var(--muted);">
                                                        Akun Anda
                                                    </span>
                                                <?php else: ?>
                                                    <a href="admin_peminjaman.php?page=user&action=hapus_user&id=<?= (int)$usr['UserID']; ?>"
                                                       onclick="return confirm('Hapus akun &quot;<?= htmlspecialchars(addslashes($usr['NamaLengkap'])); ?>&quot;? Tindakan ini tidak dapat dibatalkan.');"
                                                       class="btn btn-danger">
                                                        <i class="fas fa-trash text-[10px]"></i>
                                                        <span>Hapus</span>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="py-12 text-center">
                                                <i class="fas fa-inbox text-2xl mb-2 block" style="color: var(--line);"></i>
                                                <div class="text-[12px]" style="color: var(--muted);">
                                                    Belum ada user terdaftar.
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- Footer -->
                <div class="pt-2 pb-6 text-center text-[11px]" style="color: var(--muted);">
                    Digital Library &middot; Panel Administrator
                </div>
            </div>
        </div>
    </main>

    <!-- ============================ MODAL: EDIT BUKU ============================ -->
    <div id="modal-edit" class="hidden fixed inset-0 z-50 items-center justify-center p-4 modal-backdrop">
        <div class="modal max-w-[640px] max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b flex-shrink-0" style="border-color: var(--line-soft);">
                <div class="flex items-center gap-2">
                    <i class="fas fa-pen text-[12px]" style="color: var(--brand);"></i>
                    <span class="text-[13px] font-semibold" style="color: var(--ink);">Edit Buku</span>
                </div>
                <button type="button" onclick="tutupEditBuku()"
                        class="w-7 h-7 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                        style="color: var(--muted);">
                    <i class="fas fa-xmark text-[12px]"></i>
                </button>
            </div>

            <!-- Body -->
            <form method="POST" enctype="multipart/form-data" id="form-edit-buku" class="overflow-y-auto flex-1">
                <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">

                    <!-- Kolom kiri: cover -->
                    <div class="md:col-span-1">
                        <label class="label">Cover</label>
                        <div class="aspect-[3/4] rounded-md border overflow-hidden relative flex items-center justify-center"
                             style="border-color: var(--line); background: var(--brand-light);">
                            <img id="edit-cover-preview" src="" alt=""
                                 class="hidden w-full h-full object-cover">
                            <div id="edit-cover-placeholder"
                                 class="w-full h-full flex flex-col items-center justify-center gap-2"
                                 style="color: var(--brand);">
                                <i class="fas fa-book text-2xl"></i>
                                <span class="text-[11px]">Belum ada cover</span>
                            </div>
                        </div>

                        <input type="file" name="cover" accept="image/*" id="edit-cover-input"
                               class="field text-[11px] mt-3 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:cursor-pointer"
                               style="padding: 5px;"
                               onchange="previewCover(this, 'edit-cover-preview', 'edit-cover-placeholder')">

                        <p class="text-[10px] mt-2 leading-relaxed" style="color: var(--muted);">
                            Kosongkan jika tidak ingin mengganti cover.
                            Cover lama akan otomatis dihapus jika upload baru.
                        </p>
                    </div>

                    <!-- Kolom kanan: field-field -->
                    <div class="md:col-span-2 space-y-4">
                        <input type="hidden" name="buku_id" id="edit-buku-id" value="">

                        <div>
                            <label class="label">Judul <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="judul" id="edit-judul" required
                                   class="field">
                        </div>

                        <div>
                            <label class="label">Penulis <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="penulis" id="edit-penulis" required
                                   class="field">
                        </div>

                        <div>
                            <label class="label">Penerbit</label>
                            <input type="text" name="penerbit" id="edit-penerbit"
                                   class="field">
                        </div>

                        <div>
                            <label class="label">Kategori <span style="color: var(--danger);">*</span></label>
                            <select name="kategori" id="edit-kategori" required class="field">
                                <option value="">Pilih kategori</option>
                                <option>Teknologi & Informatika</option>
                                <option>Sains & Alam</option>
                                <option>Sastra & Fiksi</option>
                                <option>Sejarah & Budaya</option>
                                <option>Bisnis & Ekonomi</option>
                                <option>Filsafat & Agama</option>
                                <option>Seni & Desain</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">ISBN</label>
                                <input type="text" name="isbn" id="edit-isbn" class="field">
                            </div>
                            <div>
                                <label class="label">Tahun</label>
                                <input type="number" name="tahun" id="edit-tahun"
                                       class="field" min="1900" max="2100">
                            </div>
                        </div>

                        <div>
                            <label class="label">Stok <span style="color: var(--danger);">*</span></label>
                            <input type="number" name="stok" id="edit-stok" required min="0"
                                   class="field">
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t flex-shrink-0"
                     style="background: var(--bg-soft); border-color: var(--line-soft);">
                    <button type="button" onclick="tutupEditBuku()" class="btn btn-ghost">Batal</button>
                    <button type="submit" name="update_buku" class="btn btn-primary">
                        <i class="fas fa-floppy-disk text-[10px]"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    /* ========== Preview cover (dipakai di form tambah & edit) ========== */
    function previewCover(input, previewId, placeholderId) {
        const prev = document.getElementById(previewId);
        const ph   = placeholderId ? document.getElementById(placeholderId) : null;

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                prev.src = e.target.result;
                prev.classList.remove('hidden');
                if (ph) ph.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            prev.classList.add('hidden');
            if (ph) ph.classList.remove('hidden');
        }
    }

    /* ========== Modal Edit Buku ========== */
    function bukaEditBuku(data) {
        document.getElementById('edit-buku-id').value  = data.id;
        document.getElementById('edit-judul').value    = data.judul || '';
        document.getElementById('edit-penulis').value  = data.penulis || '';
        document.getElementById('edit-penerbit').value = data.penerbit || '';
        document.getElementById('edit-kategori').value = data.kategori || '';
        document.getElementById('edit-isbn').value     = data.isbn || '';
        document.getElementById('edit-tahun').value    = data.tahun || '';
        document.getElementById('edit-stok').value     = data.stok ?? 0;

        // Reset input file
        document.getElementById('edit-cover-input').value = '';

        // Cover preview
        const prev = document.getElementById('edit-cover-preview');
        const ph   = document.getElementById('edit-cover-placeholder');
        if (data.cover) {
            prev.src = data.cover;
            prev.classList.remove('hidden');
            ph.classList.add('hidden');
            prev.onerror = () => { prev.classList.add('hidden'); ph.classList.remove('hidden'); };
        } else {
            prev.src = '';
            prev.classList.add('hidden');
            ph.classList.remove('hidden');
        }

        const m = document.getElementById('modal-edit');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function tutupEditBuku() {
        const m = document.getElementById('modal-edit');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.getElementById('modal-edit').addEventListener('click', e => {
        if (e.target.id === 'modal-edit') tutupEditBuku();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupEditBuku();
    });
    </script>
</body>
</html>