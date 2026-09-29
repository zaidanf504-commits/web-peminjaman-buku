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
    <title>Daftar Akun — Digital Library</title>
    
    <!-- Tailwind CSS 3 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Three.js CDN untuk Animasi 3D Interaktif -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #030303;
            color: #f8fafc;
        }

        /* Glassmorphism Ultra Transparent Style */
        .glass-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        .input-clean {
            width: 100%;
            background: rgba(255, 255, 255, 0.07);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 11px 16px 11px 40px;
            font-size: 13.5px;
            color: #ffffff;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-clean::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        .input-clean:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
        }

        /* Micro Entrance Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        .animate-shake {
            animation: shake 0.4s ease-in-out;
        }

        #canvas3d-container {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: auto;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden selection:bg-brand-500 selection:text-white">

    <!-- Video Background & Dark Overlay -->
    <video 
      autoplay 
      loop 
      muted 
      playsinline 
      class="fixed inset-0 w-full h-full object-cover z-0 pointer-events-none"
      src="https://www.pexels.com/download/video/16839128/">
    </video>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-[1px] z-0 pointer-events-none"></div>

    <!-- Main Card Container -->
    <div class="w-full max-w-4xl relative z-10 animate-fade-in my-6">
        <div class="glass-card rounded-3xl overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[620px]">

            <!-- ================= KIRI: FORM REGISTER ================= -->
            <div class="md:col-span-7 p-8 sm:p-11 flex flex-col justify-between bg-white/5 backdrop-blur-md">
                <div>
                    <!-- Navigation / Header -->
                    <div class="flex items-center justify-between mb-6">
                        <a href="landing.php" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-300 hover:text-white transition-colors group">
                            <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                            <span>Beranda</span>
                        </a>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Akun Baru
                        </span>
                    </div>

                    <!-- Title -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 text-brand-400 font-bold text-sm mb-1.5">
                            <i class="fas fa-book-open"></i>
                            <span>Digital Library</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Buat Akun Anda</h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">Lengkapi data untuk meminjam buku secara instant.</p>
                    </div>

                    <!-- Error Alert -->
                    <?php if (!empty($message)): ?>
                        <div class="animate-shake flex items-center gap-3 p-3.5 mb-5 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-200 text-xs backdrop-blur-md">
                            <i class="fas fa-exclamation-circle text-rose-400 text-base flex-shrink-0"></i>
                            <div><?= htmlspecialchars($message); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="" class="space-y-3.5">
                        
                        <!-- Nama Lengkap -->
                        <div>
                            <label for="namalengkap" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Nama Lengkap <span class="text-brand-400">*</span>
                            </label>
                            <div class="relative">
                                <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    type="text" 
                                    name="namalengkap" 
                                    id="namalengkap" 
                                    required 
                                    placeholder="Nama lengkap Anda"
                                    value="<?= htmlspecialchars($old['namalengkap']); ?>"
                                    class="input-clean">
                            </div>
                        </div>

                        <!-- Grid: Username & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="username" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                                    Username <span class="text-brand-400">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input 
                                        type="text" 
                                        name="username" 
                                        id="username" 
                                        required 
                                        placeholder="Username"
                                        value="<?= htmlspecialchars($old['username']); ?>"
                                        class="input-clean">
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                                    Email <span class="text-brand-400">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email" 
                                        required 
                                        placeholder="nama@email.com"
                                        value="<?= htmlspecialchars($old['email']); ?>"
                                        class="input-clean">
                                </div>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label for="alamat" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Alamat Domisili
                            </label>
                            <div class="relative">
                                <i class="fas fa-location-dot absolute left-4 top-3 text-slate-400 text-xs"></i>
                                <textarea 
                                    name="alamat" 
                                    id="alamat" 
                                    rows="2"
                                    placeholder="Alamat lengkap domisili Anda"
                                    class="input-clean resize-none"><?= htmlspecialchars($old['alamat']); ?></textarea>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Password <span class="text-brand-400">*</span>
                            </label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    required 
                                    placeholder="Minimal 6 karakter"
                                    class="input-clean pr-10">
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1">
                                    <i id="eyeIcon" class="fas fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button 
                                type="submit" 
                                name="register" 
                                class="w-full bg-brand-600 hover:bg-brand-500 active:scale-[0.99] text-white font-bold py-3.5 px-6 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center justify-center gap-2 group">
                                <span>Daftar Sekarang</span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-6 pt-5 border-t border-white/10 text-center text-xs text-slate-300">
                    Sudah memiliki akun? 
                    <a href="login.php" class="text-brand-400 font-bold hover:text-brand-300 hover:underline ml-0.5">Masuk di Sini &rarr;</a>
                </div>
            </div>

            <!-- ================= KANAN: ANIMASI 3D INTERAKTIF ================= -->
            <div class="md:col-span-5 relative bg-slate-900/30 border-l border-white/10 overflow-hidden flex flex-col justify-between p-8 text-white">
                
                <!-- Canvas 3D -->
                <div id="canvas3d-container"></div>

                <!-- Overlay Banner Content -->
                <div class="relative z-10 pointer-events-none">
                    <span class="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[10px] font-mono uppercase tracking-widest text-brand-200 border border-white/10">
                        Keanggotaan Digital
                    </span>
                </div>

                <div class="relative z-10 pointer-events-none my-auto py-12">
                    <h3 class="text-xl font-extrabold text-white leading-snug tracking-tight">
                        Akses Ribuan Buku Hanya dalam 1 Menit.
                    </h3>
                    <p class="text-xs text-brand-200/80 mt-2 leading-relaxed">
                        Nikmati kenyamanan peminjaman literatur tanpa biaya pendaftaran.
                    </p>
                </div>

                <div class="relative z-10 pointer-events-none text-[11px] text-brand-300/60 font-mono flex items-center justify-between">
                    <span>&copy; <?= date('Y'); ?> Digital Library</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-cube"></i> Three.js Render</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    /* ================= THREE.JS 3D INTERACTIVE BOOK ================= */
    (function init3D() {
        const container = document.getElementById('canvas3d-container');
        if (!container) return;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 6;

        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0x60a5fa, 1.5);
        dirLight.position.set(5, 5, 5);
        scene.add(dirLight);

        const pointLight = new THREE.PointLight(0x2563eb, 2, 10);
        pointLight.position.set(-3, -2, 2);
        scene.add(pointLight);

        // 3D Book Group
        const bookGroup = new THREE.Group();

        // Cover
        const coverGeo = new THREE.BoxGeometry(2.2, 3, 0.35);
        const coverMat = new THREE.MeshPhongMaterial({ color: 0x1d4ed8, shininess: 80 });
        const cover = new THREE.Mesh(coverGeo, coverMat);
        bookGroup.add(cover);

        // Pages
        const pagesGeo = new THREE.BoxGeometry(2.05, 2.85, 0.28);
        const pagesMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.3 });
        const pages = new THREE.Mesh(pagesGeo, pagesMat);
        pages.position.x = 0.05;
        bookGroup.add(pages);

        // Spine Accent
        const spineGeo = new THREE.BoxGeometry(0.1, 3.02, 0.37);
        const spineMat = new THREE.MeshBasicMaterial({ color: 0x60a5fa });
        const spine = new THREE.Mesh(spineGeo, spineMat);
        spine.position.x = -1.06;
        bookGroup.add(spine);

        scene.add(bookGroup);

        // Floating Particles
        const particlesGeo = new THREE.BufferGeometry();
        const particleCount = 60;
        const posArray = new Float32Array(particleCount * 3);

        for(let i=0; i < particleCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 8;
        }
        particlesGeo.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        const particleMat = new THREE.PointsMaterial({
            size: 0.04,
            color: 0x93c5fd,
            transparent: true,
            opacity: 0.6
        });
        const particlesMesh = new THREE.Points(particlesGeo, particleMat);
        scene.add(particlesMesh);

        // Interaction Mouse Tracking
        let mouseX = 0;
        let mouseY = 0;
        let targetRotationX = 0;
        let targetRotationY = 0;

        container.addEventListener('mousemove', (e) => {
            const rect = container.getBoundingClientRect();
            mouseX = ((e.clientX - rect.left) / container.clientWidth) * 2 - 1;
            mouseY = -(((e.clientY - rect.top) / container.clientHeight) * 2 - 1);

            targetRotationY = mouseX * 0.8;
            targetRotationX = mouseY * 0.6;
        });

        // Animation Loop
        function animate() {
            requestAnimationFrame(animate);

            // Smooth Interpolation (Lerp)
            bookGroup.rotation.y += (targetRotationY - bookGroup.rotation.y) * 0.05;
            bookGroup.rotation.x += (targetRotationX - bookGroup.rotation.x) * 0.05;

            // Subtle Idle Float
            bookGroup.position.y = Math.sin(Date.now() * 0.002) * 0.15;
            particlesMesh.rotation.y += 0.001;

            renderer.render(scene, camera);
        }
        animate();

        // Responsive Resize
        window.addEventListener('resize', () => {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
    })();
    </script>
</body>
</html>
