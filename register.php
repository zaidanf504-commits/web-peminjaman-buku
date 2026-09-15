<?php
session_start();
require 'koneksi.php';

// Kalau sudah login, redirect ke dashboard
if (isset($_SESSION['UserID'])) {
    if (($_SESSION['Role'] ?? '') === 'admin') {
        header("Location: admin_peminjaman.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$message = '';
$old = ['namalengkap' => '', 'username' => '', 'email' => '', 'alamat' => ''];

if (isset($_POST['register'])) {
    $namaLengkap = mysqli_real_escape_string($koneksi, trim($_POST['namalengkap'] ?? ''));
    $username    = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email       = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $alamat      = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));
    $password    = $_POST['password'] ?? '';

    $old = [
        'namalengkap' => $namaLengkap,
        'username'    => $username,
        'email'       => $email,
        'alamat'      => $alamat,
    ];

    if ($namaLengkap === '' || $username === '' || $email === '' || $password === '') {
        $message = "Semua field bertanda * wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $message = "Password minimal 6 karakter.";
    } else {
        $check = mysqli_query($koneksi,
            "SELECT UserID FROM user WHERE Username='$username' OR Email='$email' LIMIT 1");

        if ($check && mysqli_num_rows($check) > 0) {
            $message = "Username atau email sudah terdaftar.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO user (Username, Password, Email, NamaLengkap, Alamat, Role)
                      VALUES ('$username', '$hashed_password', '$email', '$namaLengkap', '$alamat', 'user')";

            if (mysqli_query($koneksi, $query)) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan masuk dengan akun baru Anda.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Gagal mendaftar: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0052CC;
            --brand-dark: #0747A6;
            --brand-deep: #092E75;
            --brand-mid: #4C9AFF;
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
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        html, body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
            min-height: 100%;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes blobFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(4%, -3%) scale(1.05); }
            66%      { transform: translate(-3%, 4%) scale(0.97); }
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%     { transform: translateX(-6px); }
            40%     { transform: translateX(6px); }
            60%     { transform: translateX(-4px); }
            80%     { transform: translateX(4px); }
        }

        .page-enter  { animation: fadeIn .35s ease-out both; }
        .col-form    { animation: fadeUp .55s cubic-bezier(.2,.8,.2,1) both; }
        .col-visual  { animation: fadeUp .65s cubic-bezier(.2,.8,.2,1) .1s both; }

        .stagger > * { animation: fadeUp .5s cubic-bezier(.2,.8,.2,1) both; }
        .stagger > *:nth-child(1) { animation-delay: .10s; }
        .stagger > *:nth-child(2) { animation-delay: .16s; }
        .stagger > *:nth-child(3) { animation-delay: .22s; }
        .stagger > *:nth-child(4) { animation-delay: .28s; }
        .stagger > *:nth-child(5) { animation-delay: .34s; }
        .stagger > *:nth-child(6) { animation-delay: .40s; }
        .stagger > *:nth-child(7) { animation-delay: .46s; }
        .stagger > *:nth-child(8) { animation-delay: .52s; }
        .stagger > *:nth-child(9) { animation-delay: .58s; }

        .page-exit {
            animation: pageExit .38s cubic-bezier(.4,0,1,1) both;
            pointer-events: none;
        }
        @keyframes pageExit {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(24px); }
        }

        .shake { animation: shake .4s ease-in-out; }

        /* Blob visual */
        .blob-wrap {
            position: absolute;
            inset: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at 30% 20%, #4C9AFF 0%, transparent 45%),
                radial-gradient(circle at 70% 80%, #0747A6 0%, transparent 50%),
                radial-gradient(circle at 20% 90%, #092E75 0%, transparent 55%),
                linear-gradient(150deg, #0052CC 0%, #0747A6 55%, #092E75 100%);
        }
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: .55;
            animation: blobFloat 18s ease-in-out infinite;
        }
        .blob-1 { width: 55%; height: 55%; top: 6%;  left: -6%;  background: radial-gradient(circle, #B3D4FF 0%, transparent 70%); }
        .blob-2 { width: 60%; height: 60%; top: 40%; right: -10%; background: radial-gradient(circle, #4C9AFF 0%, transparent 70%); animation-delay: -6s; }
        .blob-3 { width: 45%; height: 45%; bottom: -8%; left: 15%; background: radial-gradient(circle, #0052CC 0%, transparent 70%); animation-delay: -12s; }

        .blob-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: .16;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* Field */
        .field {
            width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--ink);
            font-size: 13px;
            padding: 10px 13px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field::placeholder { color: #A5ADBA; }
        .field:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(0,82,204,.14);
        }
        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-2);
            margin-bottom: 6px;
        }

        .btn-primary-lg {
            width: 100%;
            background: var(--brand);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 12px 16px;
            border-radius: 10px;
            transition: background .15s ease, transform .1s ease;
            letter-spacing: .01em;
        }
        .btn-primary-lg:hover { background: var(--brand-dark); }
        .btn-primary-lg:active { transform: translateY(1px); }

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            transition: color .15s ease, transform .15s ease;
        }
        .back-link:hover {
            color: var(--brand);
            transform: translateX(-2px);
        }

        /* Password strength */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            transition: width .3s ease, background .3s ease;
            background: var(--line);
        }

        /* Grid layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .form-grid > .full { grid-column: 1 / -1; }
        @media (max-width: 480px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }

        a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 6px;
        }
    </style>
</head>
<body class="page-enter">

    <div class="min-h-screen w-full flex items-center justify-center p-4 lg:p-8">

        <div class="w-full max-w-[1000px] bg-white rounded-2xl shadow-[0_1px_2px_rgba(9,30,66,.08),0_20px_48px_-16px_rgba(9,30,66,.16)] overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-[1.15fr_1fr] md:min-h-[640px]">

                <!-- ================= KIRI: FORM ================= -->
                <div class="col-form p-8 sm:p-10 lg:p-12 flex flex-col justify-center">

                    <!-- Back link -->
                    <a href="landing.php" class="back-link mb-6" data-direction="back">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Kembali ke beranda</span>
                    </a>

                    <!-- Brand -->
                    <div class="flex items-center gap-3 mb-7">
                        <img src="logo/logo.png" alt="Digital Library" class="h-10 object-contain">
                        <span class="text-[12px] font-semibold uppercase tracking-[0.16em]" style="color: var(--muted);">
                            Digital Library
                        </span>
                    </div>

                    <h1 class="text-[30px] sm:text-[34px] font-extrabold leading-[1.1] tracking-tight mb-2" style="color: var(--ink);">
                        Buat akun baru.
                    </h1>
                    <p class="text-[13px] leading-relaxed mb-7" style="color: var(--muted);">
                        Isi data di bawah untuk mulai meminjam buku dari katalog perpustakaan kami.
                    </p>

                    <?php if (!empty($message)): ?>
                        <div id="errorBox" class="shake flex items-start gap-2.5 px-3.5 py-2.5 rounded-lg border text-[13px] mb-5"
                             style="background: var(--danger-bg); border-color: var(--danger-line); color: var(--danger);">
                            <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                            <span class="font-medium"><?= htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="stagger form-grid" id="registerForm">

                        <div class="full">
                            <label for="namalengkap" class="field-label">Nama Lengkap <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="namalengkap" id="namalengkap" required
                                   autocomplete="name"
                                   placeholder="cth: Zaidan Faiz"
                                   value="<?= htmlspecialchars($old['namalengkap']); ?>"
                                   class="field">
                        </div>

                        <div>
                            <label for="username" class="field-label">Username <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="username" id="username" required
                                   autocomplete="username"
                                   placeholder="cth: zaidan"
                                   value="<?= htmlspecialchars($old['username']); ?>"
                                   class="field">
                        </div>

                        <div>
                            <label for="email" class="field-label">Email <span style="color: var(--danger);">*</span></label>
                            <input type="email" name="email" id="email" required
                                   autocomplete="email"
                                   placeholder="nama@email.com"
                                   value="<?= htmlspecialchars($old['email']); ?>"
                                   class="field">
                        </div>

                        <div class="full">
                            <label for="alamat" class="field-label">Alamat <span style="color: var(--danger);">*</span></label>
                            <textarea name="alamat" id="alamat" required rows="2"
                                      placeholder="cth: Jl. Merdeka No. 10, Jakarta"
                                      class="field resize-none"><?= htmlspecialchars($old['alamat']); ?></textarea>
                        </div>

                        <div class="full">
                            <label for="password" class="field-label">Password <span style="color: var(--danger);">*</span></label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                       autocomplete="new-password"
                                       placeholder="Minimal 6 karakter"
                                       oninput="updateStrength(this.value)"
                                       class="field pr-11">
                                <button type="button" onclick="togglePassword()" tabindex="-1"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                                        style="color: var(--muted);">
                                    <i id="passwordIcon" class="fas fa-eye text-[12px]"></i>
                                </button>
                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <div class="flex-1 h-[3px] rounded-full overflow-hidden" style="background: var(--line-soft);">
                                    <div id="strengthBar" class="strength-bar w-0"></div>
                                </div>
                                <span id="strengthLabel" class="text-[10px] font-semibold uppercase tracking-wider min-w-[60px] text-right" style="color: var(--muted);">
                                    —
                                </span>
                            </div>
                        </div>

                        <label class="full flex items-start gap-2 cursor-pointer select-none pt-1">
                            <input type="checkbox" required class="w-3.5 h-3.5 rounded mt-0.5" style="accent-color: var(--brand);">
                            <span class="text-[11.5px] leading-snug" style="color: var(--muted);">
                                Saya menyetujui <a href="#" class="font-semibold hover:underline" style="color: var(--brand);">syarat &amp; ketentuan</a> perpustakaan.
                            </span>
                        </label>

                        <button type="submit" name="register" class="btn-primary-lg full mt-1">
                            Buat Akun
                        </button>
                    </form>

                    <p class="text-center text-[12.5px] mt-7" style="color: var(--muted);">
                        Sudah punya akun?
                        <a href="login.php"
                           class="font-bold hover:underline ml-1 transition-link"
                           data-direction="back"
                           style="color: var(--brand);">
                            Masuk di sini
                        </a>
                    </p>
                </div>

                <!-- ================= KANAN: VISUAL ================= -->
                <div class="col-visual hidden md:block relative p-6 lg:p-8">
                    <div class="relative h-full rounded-2xl overflow-hidden">

                        <div class="blob-wrap">
                            <div class="blob blob-1"></div>
                            <div class="blob blob-2"></div>
                            <div class="blob blob-3"></div>
                        </div>

                        <div class="relative z-10 h-full flex flex-col justify-between p-8 text-white">

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium border border-white/25 bg-white/10 backdrop-blur-sm">
                                    <i class="fas fa-user-plus text-[10px]"></i>
                                    Gratis
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium border border-white/25 bg-white/10 backdrop-blur-sm">
                                    <i class="fas fa-clock text-[10px]"></i>
                                    Daftar &lt; 1 menit
                                </span>
                            </div>

                            <div class="space-y-4">
                                <div class="flex gap-3 items-start">
                                    <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-book text-[12px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-semibold">Koleksi Lengkap</div>
                                        <div class="text-[11px] text-white/65 mt-0.5">Beragam judul dari berbagai bidang.</div>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-bolt text-[12px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-semibold">Pinjam Instan</div>
                                        <div class="text-[11px] text-white/65 mt-0.5">Kuota 3 buku, durasi 14 hari.</div>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-star text-[12px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-semibold">Ulas &amp; Bagikan</div>
                                        <div class="text-[11px] text-white/65 mt-0.5">Rating dari pembaca lain.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white/12 backdrop-blur-md border border-white/20 rounded-2xl p-5"
                                 style="border-bottom-left-radius: 32px;">
                                <p class="text-[14px] font-medium leading-snug text-white/95">
                                    “Setiap buku adalah pintu ke dunia yang belum pernah kita datangi.”
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('passwordIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    function updateStrength(val) {
        const bar = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');

        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const map = [
            { w: '0%',   c: '#DFE1E6', t: '—',           col: '#5E6C84' },
            { w: '20%',  c: '#FF5630', t: 'Lemah',       col: '#BF2600' },
            { w: '40%',  c: '#FFAB00', t: 'Sedang',      col: '#7A4E00' },
            { w: '60%',  c: '#FFAB00', t: 'Sedang',      col: '#7A4E00' },
            { w: '80%',  c: '#36B37E', t: 'Kuat',        col: '#006644' },
            { w: '100%', c: '#0052CC', t: 'Sangat kuat', col: '#0052CC' },
        ];
        const s = map[Math.min(score, 5)];
        bar.style.width = s.w;
        bar.style.background = s.c;
        label.textContent = s.t;
        label.style.color = s.col;
    }

    window.addEventListener('DOMContentLoaded', () => {
        const err = document.getElementById('errorBox');
        if (err) setTimeout(() => err.classList.remove('shake'), 500);
    });

    /* Exit animation */
    document.querySelectorAll('.transition-link, .back-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const dir = this.dataset.direction || 'forward';
            document.body.classList.add(dir === 'back' ? 'page-exit' : 'page-exit');
            setTimeout(() => { window.location.href = url; }, 360);
        });
    });

    window.addEventListener('pageshow', e => {
        if (e.persisted) document.body.classList.remove('page-exit');
    });
    </script>
</body>
</html>