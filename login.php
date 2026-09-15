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
$old_username = '';
$success_msg = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $old_username = $username;

    if ($username === '' || $password === '') {
        $message = "Username dan password wajib diisi.";
    } else {
        $query = mysqli_query($koneksi, "SELECT * FROM user WHERE Username='$username' LIMIT 1");
        $data = $query ? mysqli_fetch_assoc($query) : null;

        if ($data && password_verify($password, $data['Password'])) {
            session_regenerate_id(true);
            $_SESSION['UserID']      = $data['UserID'];
            $_SESSION['Username']    = $data['Username'];
            $_SESSION['NamaLengkap'] = $data['NamaLengkap'];
            $_SESSION['Role']        = $data['Role'];

            if ($data['Role'] === 'admin') {
                header("Location: admin_peminjaman.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $message = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Digital Library</title>
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

            --success: #006644;
            --success-bg: #E3FCEF;
            --success-line: #ABF5D1;
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        html, body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
            height: 100%;
            overflow: hidden;
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
        .stagger > *:nth-child(1) { animation-delay: .12s; }
        .stagger > *:nth-child(2) { animation-delay: .18s; }
        .stagger > *:nth-child(3) { animation-delay: .24s; }
        .stagger > *:nth-child(4) { animation-delay: .30s; }
        .stagger > *:nth-child(5) { animation-delay: .36s; }
        .stagger > *:nth-child(6) { animation-delay: .42s; }

        .page-exit {
            animation: pageExit .38s cubic-bezier(.4,0,1,1) both;
            pointer-events: none;
        }
        @keyframes pageExit {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(-24px); }
        }
        .page-exit-reverse {
            animation: pageExitReverse .38s cubic-bezier(.4,0,1,1) both;
            pointer-events: none;
        }
        @keyframes pageExitReverse {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(24px); }
        }

        .shake { animation: shake .4s ease-in-out; }

        /* Blob */
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
            padding: 11px 14px;
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

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #C1C7D0; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #A5ADBA; }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 6px;
        }
    </style>
</head>
<body class="page-enter">

    <div class="min-h-screen w-full flex items-center justify-center p-4 lg:p-8">

        <div class="w-full max-w-[1000px] bg-white rounded-2xl shadow-[0_1px_2px_rgba(9,30,66,.08),0_20px_48px_-16px_rgba(9,30,66,.16)] overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-[1.15fr_1fr] md:min-h-[600px]">

                <!-- ================= KIRI: FORM ================= -->
                <div class="col-form p-8 sm:p-10 lg:p-14 flex flex-col justify-center">

                    <!-- Back link -->
                    <a href="landing.php" class="back-link mb-6" data-direction="back">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Kembali ke beranda</span>
                    </a>

                    <!-- Brand -->
                    <div class="flex items-center gap-3 mb-8">
                        <img src="logo/logo.png" alt="Digital Library" class="h-10 object-contain">
                        <span class="text-[12px] font-semibold uppercase tracking-[0.16em]" style="color: var(--muted);">
                            Digital Library
                        </span>
                    </div>

                    <!-- Heading -->
                    <h1 class="text-[32px] sm:text-[36px] font-extrabold leading-[1.1] tracking-tight mb-2" style="color: var(--ink);">
                        Selamat datang<br>kembali.
                    </h1>
                    <p class="text-[13.5px] leading-relaxed mb-8" style="color: var(--muted);">
                        Masuk untuk melanjutkan aktivitas literasi Anda.
                    </p>

                    <!-- Pesan sukses dari register -->
                    <?php if (!empty($success_msg)): ?>
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-lg border text-[13px] mb-5"
                             style="background: var(--success-bg); border-color: var(--success-line); color: var(--success);">
                            <i class="fas fa-circle-check mt-0.5 text-[12px]"></i>
                            <span class="font-medium"><?= htmlspecialchars($success_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Error -->
                    <?php if (!empty($message)): ?>
                        <div id="errorBox" class="shake flex items-start gap-2.5 px-3.5 py-2.5 rounded-lg border text-[13px] mb-5"
                             style="background: var(--danger-bg); border-color: var(--danger-line); color: var(--danger);">
                            <i class="fas fa-circle-exclamation mt-0.5 text-[12px]"></i>
                            <span class="font-medium"><?= htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="" class="stagger space-y-4" id="loginForm">

                        <div>
                            <label for="username" class="field-label">Username</label>
                            <input type="text" name="username" id="username" required
                                   autocomplete="username"
                                   placeholder="cth: zaidan"
                                   value="<?= htmlspecialchars($old_username); ?>"
                                   class="field">
                        </div>

                        <div>
                            <label for="password" class="field-label">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                       autocomplete="current-password"
                                       placeholder="Masukkan password Anda"
                                       class="field pr-11">
                                <button type="button" onclick="togglePassword()" tabindex="-1"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-md flex items-center justify-center transition hover:bg-[#F4F5F7]"
                                        style="color: var(--muted);">
                                    <i id="passwordIcon" class="fas fa-eye text-[12px]"></i>
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer select-none pt-1">
                            <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded" style="accent-color: var(--brand);">
                            <span class="text-[12px]" style="color: var(--muted);">Ingat saya di perangkat ini</span>
                        </label>

                        <button type="submit" name="login" class="btn-primary-lg mt-2">
                            Masuk
                        </button>
                    </form>

                    <!-- CTA register -->
                    <p class="text-center text-[12.5px] mt-7" style="color: var(--muted);">
                        Belum punya akun?
                        <a href="register.php"
                           class="font-bold hover:underline ml-1 transition-link"
                           data-direction="forward"
                           style="color: var(--brand);">
                            Daftar sekarang
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
                                    <i class="fas fa-book-open text-[10px]"></i>
                                    Perpustakaan Digital
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium border border-white/25 bg-white/10 backdrop-blur-sm">
                                    <i class="fas fa-star text-[10px]"></i>
                                    Koleksi Terkurasi
                                </span>
                            </div>

                            <div class="bg-white/12 backdrop-blur-md border border-white/20 rounded-2xl p-6"
                                 style="border-bottom-right-radius: 40px;">
                                <p class="text-[18px] font-semibold leading-snug text-white/95">
                                    “Buku adalah cara paling tenang untuk berkeliling dunia, tanpa harus beranjak dari kursi.”
                                </p>
                                <div class="mt-5">
                                    <div class="text-[13px] font-bold">Pramoedya Ananta Toer</div>
                                    <div class="text-[11px] text-white/65">Sastrawan Indonesia</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-white/70">
                                <div class="flex items-center gap-2 text-[11px]">
                                    <i class="fas fa-circle text-[5px]"></i>
                                    <span>Sistem Peminjaman Buku</span>
                                </div>
                                <div class="text-[11px] tnum">2026</div>
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
            document.body.classList.add(dir === 'back' ? 'page-exit-reverse' : 'page-exit');
            setTimeout(() => { window.location.href = url; }, 360);
        });
    });

    window.addEventListener('pageshow', e => {
        if (e.persisted) {
            document.body.classList.remove('page-exit', 'page-exit-reverse');
        }
    });
    </script>
</body>
</html>