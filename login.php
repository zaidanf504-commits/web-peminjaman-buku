<?php
session_start();
require 'koneksi.php';

// Jika sudah login, alihkan ke dashboard masing-masing
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
        $message = "Username dan kata sandi wajib diisi.";
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
            $message = "Kredensial tidak cocok. Silakan periksa kembali username atau kata sandi Anda.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otentikasi Anggota &bull; BIBLIOTHECA</title>
    
    <!-- Tailwind CSS 3 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Typography: Newsreader + Plus Jakarta Sans + Space Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Newsreader"', 'Georgia', 'serif'],
                        mono: ['"Space Mono"', 'monospace'],
                    },
                    colors: {
                        cobalt: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                            950: '#0b132b',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #ffffff;
            color: #0f172a;
        }

        .border-hairline {
            border-color: rgba(15, 23, 42, 0.08);
        }

        /* Input Presisi & Tenang */
        .editorial-input {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 13px 16px;
            font-size: 13.5px;
            color: #0f172a;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .editorial-input::placeholder {
            color: #94a3b8;
        }
        .editorial-input:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Latar Kanan Blueprint Tech */
        .cobalt-blueprint {
            background-color: #0f172a;
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
        }

        /* Micro Entrance Fade */
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-enter {
            animation: pageFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes subtleShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .shake-error {
            animation: subtleShake 0.4s ease-in-out;
        }
    </style>

    <script type="importmap">
    {
      "imports": {
        "three": "https://unpkg.com/three@0.164.1/build/three.module.js"
      }
    }
    </script>
</head>
<body class="min-h-screen flex flex-col lg:flex-row selection:bg-cobalt-600 selection:text-white">

    <!-- ================= KOLOM KIRI: FORMULIR OTENTIKASI ================= -->
    <main class="w-full lg:w-[50%] xl:w-[46%] min-h-screen flex flex-col justify-between p-8 sm:p-12 md:p-16 border-r border-hairline bg-white z-10 animate-enter">
        
        <!-- Header Navigasi -->
        <div class="flex items-center justify-between border-b border-hairline pb-5">
            <a href="landing.php" class="inline-flex items-center gap-2.5 text-xs font-mono uppercase tracking-widest text-slate-500 hover:text-cobalt-600 transition-colors group">
                <i class="fa-solid fa-arrow-left text-[10px] group-hover:-translate-x-1 transition-transform"></i>
                <span>Kembali ke Beranda</span>
            </a>
            <span class="text-[10px] font-mono text-cobalt-600 font-bold tracking-widest uppercase bg-cobalt-50 px-2.5 py-1 rounded">
                SECURE AUTH // ID-01
            </span>
        </div>

        <!-- Konten Form Utama -->
        <div class="max-w-md w-full mx-auto my-10">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-cobalt-600"></span>
                    <span class="text-[11px] font-mono uppercase tracking-widest text-slate-500 font-semibold">Bibliotheca Digital</span>
                </div>
                <h1 class="font-serif text-4xl sm:text-5xl text-slate-900 font-normal tracking-tight leading-[1.1]">
                    Masuk ke ruang <br><em class="italic text-cobalt-600 font-normal">baca digital.</em>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-2.5 leading-relaxed">
                    Masukkan akun peminjam Anda untuk mengelola buku aktif dan mengakses katalog reservasi.
                </p>
            </div>

            <!-- Notifikasi Sukses Registrasi -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-start gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed font-medium"><?= htmlspecialchars($success_msg); ?></div>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Error Login -->
            <?php if (!empty($message)): ?>
                <div class="shake-error mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed font-medium"><?= htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4" id="loginForm">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="username" class="text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700">
                            Username Anggota <span class="text-cobalt-600">*</span>
                        </label>
                    </div>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        required 
                        autocomplete="username"
                        placeholder="Ketik username Anda"
                        value="<?= htmlspecialchars($old_username); ?>"
                        class="editorial-input interactive-field">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700">
                            Kata Sandi <span class="text-cobalt-600">*</span>
                        </label>
                        <button type="button" onclick="togglePass()" class="text-[10px] font-mono text-cobalt-600 hover:text-cobalt-700 font-bold focus:outline-none">
                            <span id="toggleText">LIHAT</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="editorial-input interactive-field pr-10">
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        name="login" 
                        class="w-full bg-slate-900 hover:bg-cobalt-600 active:scale-[0.99] text-white font-semibold py-3.5 px-6 rounded text-xs uppercase font-mono tracking-widest transition-all duration-200 flex items-center justify-between shadow-sm group">
                        <span>Verifikasi & Masuk</span>
                        <i class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>

            <!-- Tautan Registrasi -->
            <div class="mt-8 pt-5 border-t border-hairline flex items-center justify-between text-xs text-slate-600">
                <span>Belum memiliki kartu anggota?</span>
                <a href="register.php" class="font-mono font-bold text-cobalt-600 hover:text-cobalt-700 uppercase tracking-wider hover:underline">
                    Daftar Baru &rarr;
                </a>
            </div>
        </div>

        <!-- Footer Meta -->
        <div class="pt-5 border-t border-hairline flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>KEAMANAN ENKRIPSI BCRYPT</span>
            <span>&copy; <?= date('Y'); ?> BIBLIOTHECA</span>
        </div>

    </main>

    <!-- ================= KOLOM KANAN: STUDIO 3D ARSIP ================= -->
    <aside class="hidden lg:flex lg:flex-1 relative cobalt-blueprint text-white flex-col justify-between p-12 overflow-hidden select-none">
        
        <!-- Header Kanan -->
        <div class="relative z-10 flex items-center justify-between border-b border-white/10 pb-5 text-xs font-mono text-blue-200/80">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                VISUALISASI 3D ARSIP
            </span>
            <span class="text-[10px] text-slate-400">PERSPEKTIF INTERAKTIF</span>
        </div>

        <!-- 3D Stage Container -->
        <div id="login-3d-stage" class="absolute inset-0 z-0 cursor-grab active:cursor-grabbing"></div>

        <!-- Kotak Catatan Editorial -->
        <div class="relative z-10 max-w-sm bg-slate-950/70 backdrop-blur-md border border-white/10 p-5 rounded pointer-events-none">
            <div class="text-[9px] font-mono uppercase tracking-widest text-blue-400 mb-1.5">
                Koleksi Fisik & Digital
            </div>
            <p class="font-serif italic text-base leading-snug text-slate-200 mb-3">
                "Sebuah buku adalah perangkat paling ringkas untuk melintasi ruang dan masa."
            </p>
            <div class="text-[10px] font-mono text-slate-400 flex items-center justify-between pt-2.5 border-t border-white/10">
                <span>RESPON KURSOR AKTIF</span>
                <span>WEBGL / THREE.JS</span>
            </div>
        </div>

    </aside>

    <!-- ================= SCRIPT & THREE.JS LOGIC ================= -->
    <script>
        function togglePass() {
            const p = document.getElementById('password');
            const t = document.getElementById('toggleText');
            if (p.type === 'password') {
                p.type = 'text';
                t.textContent = 'SEMBUNYIKAN';
            } else {
                p.type = 'password';
                t.textContent = 'LIHAT';
            }
        }
    </script>

    <script type="module">
        import * as THREE from 'three';

        const container = document.getElementById('login-3d-stage');
        if (container) {
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(35, container.clientWidth / container.clientHeight, 0.1, 100);
            camera.position.set(0, 1.2, 8.5);

            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            container.appendChild(renderer.domElement);

            const bookGroup = new THREE.Group();
            scene.add(bookGroup);

            // Fungsi Membuat Buku Hardcover dengan Proporsi Nyata
            function createBook(coverColor, y, ry, rz) {
                const book = new THREE.Group();

                // Blok Halaman Kertas
                const pages = new THREE.Mesh(
                    new THREE.BoxGeometry(2.5, 3.4, 0.44),
                    new THREE.MeshStandardMaterial({ color: 0xfbfbf9, roughness: 0.9 })
                );
                pages.castShadow = true;
                pages.receiveShadow = true;
                book.add(pages);

                // Kulit Sampul Buku
                const coverMat = new THREE.MeshStandardMaterial({ 
                    color: coverColor, 
                    roughness: 0.28, 
                    metalness: 0.08 
                });

                const front = new THREE.Mesh(new THREE.BoxGeometry(2.6, 3.52, 0.04), coverMat);
                front.position.z = 0.23;
                front.castShadow = true;
                book.add(front);

                const back = new THREE.Mesh(new THREE.BoxGeometry(2.6, 3.52, 0.04), coverMat);
                back.position.z = -0.23;
                back.castShadow = true;
                book.add(back);

                const spine = new THREE.Mesh(new THREE.BoxGeometry(0.06, 3.52, 0.5), coverMat);
                spine.position.x = -1.28;
                spine.castShadow = true;
                book.add(spine);

                book.position.y = y;
                book.rotation.y = ry;
                book.rotation.z = rz;
                return book;
            }

            // Dua Buku Berdampingan dengan Rotasi Elegan
            const bookA = createBook(0xffffff, 0.2, -0.4, 0.06);
            const bookB = createBook(0x2563eb, -0.5, 0.35, -0.12);
            bookB.position.x = 1.0;
            bookB.position.z = -1.0;

            bookGroup.add(bookA);
            bookGroup.add(bookB);

            // Pencahayaan
            const ambient = new THREE.AmbientLight(0xffffff, 1.4);
            scene.add(ambient);

            const keyLight = new THREE.DirectionalLight(0xffffff, 2.0);
            keyLight.position.set(5, 8, 7);
            keyLight.castShadow = true;
            scene.add(keyLight);

            const fillLight = new THREE.DirectionalLight(0x60a5fa, 1.2);
            fillLight.position.set(-6, -3, 3);
            scene.add(fillLight);

            // Parallax Halus Berbasis Mouse
            let targetRotY = -0.2;
            let targetRotX = 0.1;
            let focusImpulse = 0;

            container.addEventListener('pointermove', (e) => {
                const rect = container.getBoundingClientRect();
                const nx = (e.clientX - rect.left) / rect.width - 0.5;
                const ny = (e.clientY - rect.top) / rect.height - 0.5;
                targetRotY = -0.2 + nx * 0.8;
                targetRotX = 0.1 + ny * 0.5;
            });

            // Respon halus saat pengguna memfokuskan kursor pada input
            document.querySelectorAll('.interactive-field').forEach(el => {
                el.addEventListener('focus', () => { focusImpulse = 0.12; });
                el.addEventListener('blur', () => { focusImpulse = 0; });
            });

            const clock = new THREE.Clock();
            function animate() {
                requestAnimationFrame(animate);
                const t = clock.getElapsedTime();

                bookGroup.rotation.y += (targetRotY - bookGroup.rotation.y) * 0.05;
                bookGroup.rotation.x += (targetRotX - bookGroup.rotation.x) * 0.05;

                // Animasi Melayang Tenang (Harmonic Idle Float)
                bookA.position.y = 0.2 + Math.sin(t * 1.4) * 0.06 + focusImpulse;
                bookB.position.y = -0.5 + Math.cos(t * 1.1) * 0.06 - (focusImpulse * 0.5);

                renderer.render(scene, camera);
            }
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
        }
    </script>
</body>
</html>
