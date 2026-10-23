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
        $message = "Semua kolom bertanda bintang (*) wajib dilengkapi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format alamat email tidak valid.";
    } elseif (strlen($password) < 6) {
        $message = "Kata sandi minimal harus terdiri dari 6 karakter.";
    } else {
        $check = mysqli_query($koneksi,
            "SELECT UserID FROM user WHERE Username='$username' OR Email='$email' LIMIT 1");

        if ($check && mysqli_num_rows($check) > 0) {
            $message = "Username atau alamat email tersebut sudah terdaftar pada sistem.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO user (Username, Password, Email, NamaLengkap, Alamat, Role)
                      VALUES ('$username', '$hashed_password', '$email', '$namaLengkap', '$alamat', 'user')";

            if (mysqli_query($koneksi, $query)) {
                $_SESSION['success'] = "Pendaftaran berhasil! Akun Anda aktif, silakan masuk.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Terjadi gangguan sistem: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota &bull; BIBLIOTHECA</title>
    
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
            padding: 11px 14px;
            font-size: 13.5px;
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

    <!-- KOLOM KIRI: FORMULIR REGISTRASI -->
    <main class="w-full lg:w-[50%] xl:w-[46%] min-h-screen flex flex-col justify-between p-8 sm:p-12 md:px-14 md:py-10 border-r border-hairline bg-white z-10 page-enter">
        
        <div class="flex items-center justify-between border-b border-hairline pb-4">
            <a href="landing.php" class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-slate-500 hover:text-cobalt-600 transition-colors group">
                <i class="fa-solid fa-arrow-left text-[11px] group-hover:-translate-x-1 transition-transform"></i>
                <span>Kembali ke Beranda</span>
            </a>
            <span class="text-[11px] font-mono text-slate-500 tracking-wider">
                ID // 02-REG
            </span>
        </div>

        <div class="max-w-md w-full mx-auto my-6">
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2">
                     
                </div>
                <h1 class="font-serif text-3xl sm:text-4xl text-slate-900 font-normal tracking-tight leading-tight">
                    Kartu akses pustaka <br><em class="italic text-cobalt-600 font-normal">dalam satu langkah.</em>
                </h1>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Lengkapi formulir berikut. Kartu sirkulasi 3D di sisi kanan akan diperbarui secara langsung saat Anda mengetik.
                </p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-lg flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed font-medium"><?= htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-3.5">
                <div>
                    <label for="namalengkap" class="block text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700 mb-1">
                        Nama Lengkap <span class="text-cobalt-600">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="namalengkap" 
                        id="namalengkap" 
                        required 
                        autocomplete="name"
                        placeholder="Contoh: Muhammad Farhan"
                        value="<?= htmlspecialchars($old['namalengkap']); ?>"
                        class="input-editorial">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="username" class="block text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700 mb-1">
                            Username <span class="text-cobalt-600">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            required 
                            autocomplete="username"
                            placeholder="farhan_library"
                            value="<?= htmlspecialchars($old['username']); ?>"
                            class="input-editorial">
                    </div>

                    <div>
                        <label for="email" class="block text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700 mb-1">
                            Alamat Email <span class="text-cobalt-600">*</span>
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            required 
                            autocomplete="email"
                            placeholder="nama@domain.com"
                            value="<?= htmlspecialchars($old['email']); ?>"
                            class="input-editorial">
                    </div>
                </div>

                <div>
                    <label for="alamat" class="block text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700 mb-1">
                        Alamat Domisili
                    </label>
                    <textarea 
                        name="alamat" 
                        id="alamat" 
                        rows="2"
                        placeholder="Alamat atau kota domisili saat ini"
                        class="input-editorial resize-none leading-relaxed"><?= htmlspecialchars($old['alamat']); ?></textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="text-[11px] font-mono uppercase tracking-wider font-bold text-slate-700">
                            Kata Sandi <span class="text-cobalt-600">*</span>
                        </label>
                        <button type="button" onclick="toggleRegPassword()" class="text-[11px] font-mono text-cobalt-600 hover:text-cobalt-700 font-bold focus:outline-none">
                            <span id="eyeLabel">LIHAT</span>
                        </button>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Minimal 6 karakter"
                        class="input-editorial">
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        name="register" 
                        class="w-full bg-slate-900 hover:bg-cobalt-600 active:scale-[0.99] text-white font-medium py-3.5 px-6 rounded-lg text-xs uppercase font-mono tracking-wider transition-all duration-200 flex items-center justify-between shadow-sm group">
                        <span>Terbitkan Akun Anggota</span>
                        <i class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-hairline flex items-center justify-between text-xs text-slate-600">
                <span>Sudah memiliki akun terdaftar?</span>
                <a href="login.php" class="font-mono font-bold text-cobalt-600 hover:text-cobalt-700 uppercase tracking-wider hover:underline">
                    Masuk ke Akun &rarr;
                </a>
            </div>
        </div>

        <div class="pt-4 border-t border-hairline flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>KEANGGOTAAN DIGITAL RESMI</span>
            <span>&copy; <?= date('Y'); ?> BIBLIOTHECA</span>
        </div>
    </main>

    <!-- KOLOM KANAN: SIMULASI KARTU ANGGOTA 3D THREE.JS -->
    <aside class="hidden lg:flex lg:flex-1 relative blueprint-canvas-bg text-white flex-col justify-between p-12 overflow-hidden select-none">
    
        <!-- Kanvas 3D -->
        <div id="register-3d-stage" class="absolute inset-0 z-0 cursor-grab active:cursor-grabbing"></div>

        <div class="relative z-10 flex items-center justify-between text-[11px] font-mono text-slate-400 border-t border-white/10 pt-4">
            <span>GERAKKAN KURSOR UNTUK MEMUTAR KARTU</span>
            <span>OTOMATISASI PENERBITAN</span>
        </div>
    </aside>

    <script>
        function toggleRegPassword() {
            const input = document.getElementById('password');
            const label = document.getElementById('eyeLabel');
            if (input.type === 'password') {
                input.type = 'text';
                label.textContent = 'SEMBUNYIKAN';
            } else {
                input.type = 'password';
                label.textContent = 'LIHAT';
            }
        }
    </script>

    <!-- Script Three.js 3D Dynamic Card Stage -->
    <script type="module">
        import * as THREE from 'three';

        const container = document.getElementById('register-3d-stage');
        if (container) {
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(35, container.clientWidth / container.clientHeight, 0.1, 100);
            camera.position.set(0, 0, 7.5);

            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;
            container.appendChild(renderer.domElement);

            const cardGroup = new THREE.Group();
            scene.add(cardGroup);

            // Membuat Canvas 2D Beresolusi Tinggi untuk Permukaan Depan Kartu
            const cardCanvas = document.createElement('canvas');
            cardCanvas.width = 1024;
            cardCanvas.height = 640;
            const ctx = cardCanvas.getContext('2d');
            const cardTexture = new THREE.CanvasTexture(cardCanvas);

            // Fungsi Menggambar Permukaan Kartu Fisik
            function drawCardSurface(name, user) {
                const displayName = (name && name.trim()) ? name.toUpperCase() : 'M. ZAIDAN FAIZ';
                const displayUser = (user && user.trim()) ? '@' + user.toLowerCase() : '@zaidan';

                // Latar Belakang Kartu (Matte Obsidian & Deep Navy)
                const grad = ctx.createLinearGradient(0, 0, 1024, 640);
                grad.addColorStop(0, '#0c1527');
                grad.addColorStop(1, '#050914');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, 1024, 640);

                // Pola Garis Teknis
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
                ctx.lineWidth = 1;
                for (let y = 40; y < 640; y += 40) {
                    ctx.beginPath();
                    ctx.moveTo(0, y);
                    ctx.lineTo(1024, y);
                    ctx.stroke();
                }

                // Garis Aksen Cobalt Biru di Atas
                ctx.fillStyle = '#1d4ed8';
                ctx.fillRect(0, 0, 1024, 18);

                // Header Kartu
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 36px "Newsreader", serif';
                ctx.fillText('BIBLIOTHECA', 60, 90);

                ctx.fillStyle = '#60a5fa';
                ctx.font = 'bold 15px "Space Mono", monospace';
                ctx.fillText('KARTU SIRKULASI PUSTAKA // DIGITAL PASS', 60, 120);

                // Chip Logam EMV Emas
                ctx.fillStyle = '#eab308';
                ctx.beginPath();
                ctx.roundRect(60, 160, 110, 85, 10);
                ctx.fill();
                ctx.strokeStyle = '#ca8a04';
                ctx.lineWidth = 3;
                ctx.stroke();

                // Garis Sirkuit Chip
                ctx.strokeStyle = '#a16207';
                ctx.lineWidth = 2;
                ctx.strokeRect(85, 175, 60, 55);

                // Data Anggota
                ctx.fillStyle = '#94a3b8';
                ctx.font = '14px "Space Mono", monospace';
                ctx.fillText('NAMA PEMEGANG KARTU', 60, 310);

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 34px "Plus Jakarta Sans", sans-serif';
                ctx.fillText(displayName, 60, 360);

                // ID Pengguna & Status
                ctx.fillStyle = '#94a3b8';
                ctx.font = '14px "Space Mono", monospace';
                ctx.fillText('ID PENGGUNA', 60, 440);
                ctx.fillStyle = '#60a5fa';
                ctx.font = 'bold 22px "Space Mono", monospace';
                ctx.fillText(displayUser, 60, 475);

                ctx.fillStyle = '#94a3b8';
                ctx.font = '14px "Space Mono", monospace';
                ctx.fillText('STATUS AKSES', 420, 440);
                ctx.fillStyle = '#34d399';
                ctx.font = 'bold 22px "Space Mono", monospace';
                ctx.fillText('AKTIF // VIP', 420, 475);

                // Barcode di Bawah
                ctx.fillStyle = '#ffffff';
                ctx.font = '24px monospace';
                ctx.letterSpacing = '6px';
                ctx.fillText('||||| |||| | |||||| ||| ||||||| |||||', 60, 570);

                ctx.fillStyle = '#64748b';
                ctx.font = '13px "Space Mono", monospace';
                ctx.letterSpacing = '0px';
                ctx.fillText('TERBIT: 2026 // RESMI', 740, 570);

                cardTexture.needsUpdate = true;
            }

            // Inisialisasi gambar kartu dengan nilai awal
            const inputNama = document.getElementById('namalengkap');
            const inputUser = document.getElementById('username');
            drawCardSurface(inputNama.value, inputUser.value);

            // Membuat Bentuk Kartu 3D
            const cardGeometry = new THREE.BoxGeometry(4.2, 2.65, 0.06);

            const sideMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, metalness: 0.8, roughness: 0.2 });
            const frontMat = new THREE.MeshStandardMaterial({
                map: cardTexture,
                roughness: 0.25,
                metalness: 0.15
            });
            const backMat = new THREE.MeshStandardMaterial({ color: 0x090d16, roughness: 0.5 });

            const materials = [sideMat, sideMat, sideMat, sideMat, frontMat, backMat];
            const cardMesh = new THREE.Mesh(cardGeometry, materials);
            cardGroup.add(cardMesh);

            // Cincin Geometri Orbit Tenang di Belakang Kartu
            const ringGeo = new THREE.TorusGeometry(3.2, 0.015, 16, 120);
            const ringMat = new THREE.MeshBasicMaterial({ color: 0x3b82f6, transparent: true, opacity: 0.25 });
            const ring = new THREE.Mesh(ringGeo, ringMat);
            ring.rotation.x = Math.PI / 2.5;
            scene.add(ring);

            // Pencahayaan
            const ambient = new THREE.AmbientLight(0xffffff, 1.6);
            scene.add(ambient);

            const spotLight = new THREE.DirectionalLight(0xffffff, 2.5);
            spotLight.position.set(4, 5, 6);
            scene.add(spotLight);

            const sheenLight = new THREE.PointLight(0x60a5fa, 2.0, 15);
            sheenLight.position.set(-3, -2, 4);
            scene.add(sheenLight);

            // Responsivitas Formulir ke Tekstur 3D secara Real-Time
            let tiltImpulse = 0;
            function triggerCardUpdate() {
                drawCardSurface(inputNama.value, inputUser.value);
                tiltImpulse = 0.15;
            }

            inputNama.addEventListener('input', triggerCardUpdate);
            inputUser.addEventListener('input', triggerCardUpdate);

            // Interaksi Kursor Mouse
            let targetRotX = 0;
            let targetRotY = 0;

            container.addEventListener('pointermove', (e) => {
                const rect = container.getBoundingClientRect();
                const nx = (e.clientX - rect.left) / rect.width - 0.5;
                const ny = (e.clientY - rect.top) / rect.height - 0.5;
                targetRotY = nx * 0.9;
                targetRotX = -ny * 0.6;
            });

            const clock = new THREE.Clock();
            function animate() {
                requestAnimationFrame(animate);
                const elapsed = clock.getElapsedTime();

                // LERP rotasi kartu
                cardGroup.rotation.y += (targetRotY - cardGroup.rotation.y) * 0.06;
                cardGroup.rotation.x += (targetRotX + tiltImpulse - cardGroup.rotation.x) * 0.06;
                tiltImpulse *= 0.92;

                // Animasi mengambang tenang
                cardGroup.position.y = Math.sin(elapsed * 1.5) * 0.08;
                ring.rotation.z = elapsed * 0.08;

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
