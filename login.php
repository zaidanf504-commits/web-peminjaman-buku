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
    <title>Masuk Perpustakaan &bull; BIBLIOTHECA</title>
    
    <!-- Tailwind CSS 3 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,600;1,6..72,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

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
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            700: '#1e40af',
                            900: '#0f172a',
                            950: '#070d1e',
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

        .input-editorial {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 13px 16px;
            font-size: 14px;
            color: #0f172a;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .input-editorial::placeholder {
            color: #94a3b8;
        }
        .input-editorial:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        .blueprint-canvas-bg {
            background-color: #070d1e;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(29, 78, 216, 0.15) 0%, transparent 70%),
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 36px 36px, 36px 36px;
        }

        @keyframes subtleShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .shake-error {
            animation: subtleShake 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-enter {
            animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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

    <!-- KOLOM KIRI: FORMULIR OTENTIKASI -->
    <main class="w-full lg:w-[48%] xl:w-[44%] min-h-screen flex flex-col justify-between p-8 sm:p-12 md:p-16 border-r border-hairline bg-white z-10 page-enter">
        
        <div class="flex items-center justify-between border-b border-hairline pb-4">
            <a href="landing.php" class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-slate-500 hover:text-cobalt-600 transition-colors group">
                <i class="fa-solid fa-arrow-left text-[11px] group-hover:-translate-x-1 transition-transform"></i>
                <span>Kembali ke Beranda</span>
            </a>
            <span class="text-[11px] font-mono text-slate-500 tracking-wider">
                ID // 01-AUTH
            </span>
        </div>

        <div class="max-w-md w-full mx-auto my-10">
            <div class="mb-8">

            <h1 class="font-serif text-3xl sm:text-4xl text-slate-900 font-normal tracking-tight leading-tight">
                    Masuk ke ruang <br><em class="italic text-cobalt-600 font-normal">baca digital.</em>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-2.5 leading-relaxed">
                    Akses catatan peminjaman aktif dan katalog arsip buku perpustakaan.
                </p>
            </div>

            <!-- Pesan Sukses -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-lg flex items-start gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed font-medium"><?= htmlspecialchars($success_msg); ?></div>
                </div>
            <?php endif; ?>

            <!-- Pesan Kesalahan -->
            <?php if (!empty($message)): ?>
                <div class="shake-error mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-lg flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed font-medium"><?= htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="username" class="block text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700 mb-1.5">
                        Username Anggota <span class="text-cobalt-600">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        required 
                        autocomplete="username"
                        placeholder="Masukkan username Anda"
                        value="<?= htmlspecialchars($old_username); ?>"
                        class="input-editorial interactive-input">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700">
                            Kata Sandi <span class="text-cobalt-600">*</span>
                        </label>
                        <button type="button" onclick="togglePass()" class="text-[11px] font-mono text-cobalt-600 hover:text-cobalt-700 font-bold focus:outline-none">
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
                            class="input-editorial interactive-input pr-10">
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        name="login" 
                        class="w-full bg-slate-900 hover:bg-cobalt-600 active:scale-[0.99] text-white font-medium py-3.5 px-6 rounded-lg text-xs uppercase font-mono tracking-wider transition-all duration-200 flex items-center justify-between shadow-sm group">
                        <span>Verifikasi & Masuk</span>
                        <i class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-5 border-t border-hairline flex items-center justify-between text-xs text-slate-600">
                <span>Belum terdaftar sebagai anggota?</span>
                <a href="register.php" class="font-mono font-bold text-cobalt-600 hover:text-cobalt-700 uppercase tracking-wider hover:underline">
                    Daftar Baru &rarr;
                </a>
            </div>
        </div>

        <div class="pt-4 border-t border-hairline flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>SISTEM SIRKULASI PUSTAKA</span>
            <span>&copy; <?= date('Y'); ?> Digital Library</span>
        </div>
    </main>

    <!-- KOLOM KANAN: 3D ARSIP INTERAKTIF -->
    <aside class="hidden lg:flex lg:flex-1 relative blueprint-canvas-bg text-white flex-col justify-between p-12 overflow-hidden select-none">
        
        <div class="relative z-10 flex items-center justify-between border-b border-white/10 pb-4 text-xs font-mono text-slate-400"></div>

        <!-- Kanvas 3D -->
        <div id="canvas-3d-container" class="absolute inset-0 z-0 cursor-grab active:cursor-grabbing"></div>

        <div class="relative z-10 max-w-sm bg-slate-950/80 backdrop-blur-md border border-white/10 p-5 rounded-lg pointer-events-none">
            <div class="text-[10px] font-mono uppercase tracking-widest text-blue-400 mb-1">
                Koleksi Pustaka
            </div>
            <p class="font-serif italic text-sm leading-relaxed text-slate-200">
                "Setiap lembaran buku yang tersimpan adalah jembatan pengetahuan melintasi masa."
            </p>
            <div class="text-[10px] font-mono text-slate-400 mt-2.5 pt-2 border-t border-white/10 flex items-center justify-between">
                <span>RESPON KURSOR AKTIF</span>
                <span>INTERAKSI HALUS</span>
            </div>
        </div>
    </aside>

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

    <!-- Script Three.js 3D Library Book Stage -->
    <script type="module">
        import * as THREE from 'three';

        const container = document.getElementById('canvas-3d-container');
        if (container) {
            const scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x070d1e, 0.08);

            const camera = new THREE.PerspectiveCamera(36, container.clientWidth / container.clientHeight, 0.1, 100);
            camera.position.set(0, 0.8, 7.8);

            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            container.appendChild(renderer.domElement);

            const mainGroup = new THREE.Group();
            scene.add(mainGroup);

            // Membuat Tekstur Sampul dengan Foil Emas secara Prosedural
            function createBookCoverTexture(title, baseHex) {
                const canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 700;
                const ctx = canvas.getContext('2d');

                ctx.fillStyle = baseHex;
                ctx.fillRect(0, 0, 512, 700);

                // Pola bingkai garis halus
                ctx.strokeStyle = '#d4af37';
                ctx.lineWidth = 4;
                ctx.strokeRect(30, 30, 452, 640);
                ctx.lineWidth = 1;
                ctx.strokeRect(40, 40, 432, 620);

                // Tipografi Emas
                ctx.fillStyle = '#fef08a';
                ctx.font = 'italic 34px "Newsreader", serif';
                ctx.textAlign = 'center';
                ctx.fillText(title, 256, 320);

                ctx.fillStyle = '#94a3b8';
                ctx.font = '12px monospace';
                ctx.fillText('BIBLIOTHECA ARCHIVE', 256, 370);

                return new THREE.CanvasTexture(canvas);
            }

            // Fungsi Membuat Model Buku 3D Realistis
            function createBookModel(title, coverColorHex, baseHexStr, bookmarkColor) {
                const book = new THREE.Group();

                // Kertas Buku
                const pagesMat = new THREE.MeshStandardMaterial({ color: 0xfaf8f5, roughness: 0.9 });
                const pages = new THREE.Mesh(new THREE.BoxGeometry(2.35, 3.25, 0.42), pagesMat);
                pages.castShadow = true;
                pages.receiveShadow = true;
                book.add(pages);

                // Sampul
                const coverTex = createBookCoverTexture(title, baseHexStr);
                const coverMat = new THREE.MeshStandardMaterial({ 
                    map: coverTex, 
                    roughness: 0.35, 
                    metalness: 0.1 
                });

                const frontCover = new THREE.Mesh(new THREE.BoxGeometry(2.45, 3.35, 0.04), coverMat);
                frontCover.position.z = 0.22;
                frontCover.castShadow = true;
                book.add(frontCover);

                const backCover = new THREE.Mesh(new THREE.BoxGeometry(2.45, 3.35, 0.04), coverMat);
                backCover.position.z = -0.22;
                backCover.castShadow = true;
                book.add(backCover);

                const spine = new THREE.Mesh(new THREE.BoxGeometry(0.06, 3.35, 0.46), coverMat);
                spine.position.x = -1.21;
                spine.castShadow = true;
                book.add(spine);

                // Pita Pembatas Buku (Bookmark Ribbon)
                const ribbonGeo = new THREE.BoxGeometry(0.12, 1.2, 0.01);
                const ribbonMat = new THREE.MeshStandardMaterial({ color: bookmarkColor, roughness: 0.4 });
                const ribbon = new THREE.Mesh(ribbonGeo, ribbonMat);
                ribbon.position.set(0.2, -1.8, 0.05);
                ribbon.rotation.z = -0.08;
                book.add(ribbon);

                return book;
            }

            const bookPrimary = createBookModel('VOL. I — OTENTIKASI', 0x1d4ed8, '#172554', 0xb91c1c);
            bookPrimary.position.set(-0.3, 0.1, 0.2);
            bookPrimary.rotation.set(0.1, -0.4, 0.05);
            mainGroup.add(bookPrimary);

            const bookSecondary = createBookModel('VOL. II — KATALOG', 0x0f172a, '#090d16', 0xd97706);
            bookSecondary.position.set(1.2, -0.6, -1.0);
            bookSecondary.rotation.set(-0.15, 0.3, -0.08);
            mainGroup.add(bookSecondary);

            // Partikel Debu Cahaya (Atmospheric Library Dust Motes)
            const particleCount = 140;
            const particleGeo = new THREE.BufferGeometry();
            const positions = new Float32Array(particleCount * 3);

            for (let i = 0; i < particleCount * 3; i += 3) {
                positions[i] = (Math.random() - 0.5) * 12;
                positions[i + 1] = (Math.random() - 0.5) * 8;
                positions[i + 2] = (Math.random() - 0.5) * 8;
            }
            particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));

            const particleMat = new THREE.PointsMaterial({
                color: 0x93c5fd,
                size: 0.045,
                transparent: true,
                opacity: 0.5
            });
            const particles = new THREE.Points(particleGeo, particleMat);
            scene.add(particles);

            // Pencahayaan
            const ambientLight = new THREE.AmbientLight(0xffffff, 1.4);
            scene.add(ambientLight);

            const keyLight = new THREE.DirectionalLight(0xffffff, 2.2);
            keyLight.position.set(6, 7, 7);
            keyLight.castShadow = true;
            scene.add(keyLight);

            const softFillLight = new THREE.DirectionalLight(0x38bdf8, 1.2);
            softFillLight.position.set(-6, -4, 4);
            scene.add(softFillLight);

            // Interaksi Mouse Halus
            let mouseTargetX = 0;
            let mouseTargetY = 0;
            let currentRotX = 0;
            let currentRotY = 0;

            container.addEventListener('pointermove', (e) => {
                const rect = container.getBoundingClientRect();
                mouseTargetX = ((e.clientX - rect.left) / rect.width - 0.5) * 0.8;
                mouseTargetY = ((e.clientY - rect.top) / rect.height - 0.5) * 0.5;
            });

            // Reaksi saat input formulir difokuskan
            let focusOffset = 0;
            document.querySelectorAll('.interactive-input').forEach(input => {
                input.addEventListener('focus', () => { focusOffset = 0.15; });
                input.addEventListener('blur', () => { focusOffset = 0; });
            });

            const clock = new THREE.Clock();
            function animate() {
                requestAnimationFrame(animate);
                const elapsed = clock.getElapsedTime();

                // Damping rotasi kamera/grup
                currentRotY += (mouseTargetX - currentRotY) * 0.05;
                currentRotX += (mouseTargetY - currentRotX) * 0.05;

                mainGroup.rotation.y = currentRotY;
                mainGroup.rotation.x = currentRotX;

                // Animasi Buku Mengambang
                bookPrimary.position.y = 0.1 + Math.sin(elapsed * 1.2) * 0.06 + focusOffset;
                bookSecondary.position.y = -0.6 + Math.cos(elapsed * 0.9) * 0.05 - (focusOffset * 0.5);

                // Pergerakan partikel debu halus
                const pos = particleGeo.attributes.position.array;
                for (let i = 1; i < particleCount * 3; i += 3) {
                    pos[i] += 0.002;
                    if (pos[i] > 4) pos[i] = -4;
                }
                particleGeo.attributes.position.needsUpdate = true;

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
