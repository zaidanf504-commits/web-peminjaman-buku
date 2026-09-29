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
            padding: 12px 16px 12px 42px;
            font-size: 14px;
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
      src="https://www.pexels.com/download/video/6981523/">
    </video>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-[1px] z-0 pointer-events-none"></div>

    <!-- Main Card Container -->
    <div class="w-full max-w-4xl relative z-10 animate-fade-in my-6">
        <div class="glass-card rounded-3xl overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[580px]">

            <!-- ================= KIRI: FORM LOGIN ================= -->
            <div class="md:col-span-7 p-8 sm:p-12 flex flex-col justify-between bg-white/5 backdrop-blur-md">
                <div>
                    <!-- Navigation / Header -->
                    <div class="flex items-center justify-between mb-8">
                        <a href="landing.php" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-300 hover:text-white transition-colors group">
                            <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                            <span>Beranda</span>
                        </a>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-brand-500/20 text-brand-300 border border-brand-400/30">
                            <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                            Portal Masuk
                        </span>
                    </div>

                    <!-- Title -->
                    <div class="mb-8">
                        <div class="inline-flex items-center gap-2 text-brand-400 font-bold text-sm mb-2">
                            <i class="fas fa-book-open"></i>
                            <span>Digital Library</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Selamat Datang Kembali</h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">Masukkan akun Anda untuk mengeksplorasi koleksi literatur.</p>
                    </div>

                    <!-- Success Alert -->
                    <?php if (!empty($success_msg)): ?>
                        <div class="flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-200 text-xs backdrop-blur-md">
                            <i class="fas fa-check-circle text-emerald-400 text-base flex-shrink-0"></i>
                            <div><?= htmlspecialchars($success_msg); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Error Alert -->
                    <?php if (!empty($message)): ?>
                        <div class="animate-shake flex items-center gap-3 p-3.5 mb-6 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-200 text-xs backdrop-blur-md">
                            <i class="fas fa-exclamation-circle text-rose-400 text-base flex-shrink-0"></i>
                            <div><?= htmlspecialchars($message); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Username</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    type="text" 
                                    name="username" 
                                    id="username" 
                                    required 
                                    placeholder="Masukkan username Anda"
                                    value="<?= htmlspecialchars($old_username); ?>"
                                    class="input-clean">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    required 
                                    placeholder="••••••••"
                                    class="input-clean pr-10">
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1">
                                    <i id="eyeIcon" class="fas fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button 
                                type="submit" 
                                name="login" 
                                class="w-full bg-brand-600 hover:bg-brand-500 active:scale-[0.99] text-white font-bold py-3.5 px-6 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center justify-center gap-2 group">
                                <span>Masuk Sekarang</span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-slate-300">
                    Belum memiliki akun? 
                    <a href="register.php" class="text-brand-400 font-bold hover:text-brand-300 hover:underline ml-0.5">Daftar Akun Baru &rarr;</a>
                </div>
            </div>

            <!-- ================= KANAN: ANIMASI 3D INTERAKTIF ================= -->
            <div class="md:col-span-5 relative bg-slate-900/30 border-l border-white/10 overflow-hidden flex flex-col justify-between p-8 text-white">
                
                <!-- Canvas 3D -->
                <div id="canvas3d-container"></div>

                <!-- Overlay Banner Content -->
                <div class="relative z-10 pointer-events-none">
                    <span class="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[10px] font-mono uppercase tracking-widest text-brand-200 border border-white/10">
                        3D Interactive Library
                    </span>
                </div>

                <div class="relative z-10 pointer-events-none my-auto py-12">
                    <h3 class="text-xl font-extrabold text-white leading-snug tracking-tight">
                        Eksplorasi Pengetahuan Tanpa Batas.
                    </h3>
                    <p class="text-xs text-brand-200/80 mt-2 leading-relaxed">
                        Gerakkan kursor Anda di area ini untuk berinteraksi dengan visual 3D buku digital kami.
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
