<?php
require_once __DIR__ . '/../../config/auth.php';
startSecureSession();

function renderFloatingProfile() {
    // Keluar jika user_id tidak diset
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    ?>

    <style>
        /* Base Variables (Konsisten) */
        :root {
            --primary-color: #6a11cb; /* Ungu */
            --secondary-color: #2575fc; /* Biru */
            --text-color: #333;
            --button-size: 70px; /* Diperbesar dari 65px */
            --menu-width: 240px; /* Menu lebih lebar */
        }

        /* Floating Button - Tambahkan cursor 'grab' untuk menunjukkan bisa digeser */
        .floating-profile {
            position: fixed;
            /* Inisialisasi posisi awal */
            bottom: 35px;
            right: 35px;
            width: var(--button-size);
            height: var(--button-size);
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            cursor: grab; /* Ubah kursor */
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 30px; 
            z-index: 99999;
            box-shadow: 0 12px 35px rgba(0,0,0,0.4), inset 0 0 20px rgba(255,255,255,0.4);
            animation: bounce 5s infinite ease-in-out;
            transition: all .4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            border: 4px solid rgba(255,255,255,0.8);
        }

        /* Saat digeser, kursor berubah menjadi 'grabbing' */
        .floating-profile.dragging {
            cursor: grabbing;
            transition: none; /* Nonaktifkan transisi saat digeser */
            animation: none; /* Hentikan animasi saat digeser */
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Nonaktifkan bounce dan transisi default saat hover untuk mencegah konflik dengan logika drag */
        .floating-profile:hover {
            transform: scale(1.1) rotate(2deg); 
            box-shadow: 0 18px 50px rgba(0,0,0,0.5); 
            animation: none;
        }

        /* Expanding Menu - Dibiarkan fixed, posisi relatif terhadap viewport */
        .floating-menu {
            position: fixed;
            bottom: calc(var(--button-size) + 45px); 
            right: 35px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.35);
            padding: 20px;
            width: var(--menu-width);
            display: none;
            z-index: 99998;
            opacity: 0;
            transform: translateY(30px) scale(.8);
            transition: all .4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid #eee;
        }
        
        /* Menu harus mengikuti tombol saat tombol digeser */
        /* Kita akan mengelola posisi ini di JavaScript */

        .floating-menu::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 25px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 12px solid #fff; 
            filter: drop-shadow(0 4px 2px rgba(0, 0, 0, 0.1));
        }

        .floating-menu.active {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Menu Content Styling (Dipertahankan) */
        .menu-header {
            font-size: 16px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .menu-header i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .floating-menu hr {
            border: 0;
            height: 1px;
            background: rgba(0,0,0,0.1);
            margin: 15px 0;
        }

        .floating-menu a {
            text-decoration: none;
            color: var(--text-color);
            font-size: 15px;
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-radius: 10px;
            transition: all .2s ease-out;
            margin: 4px 0;
            font-weight: 500;
        }

        .floating-menu a i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            color: var(--secondary-color);
        }

        .floating-menu a:hover {
            color: var(--secondary-color);
            background-color: #eef4ff;
            transform: translateX(5px);
            box-shadow: 0 3px 8px rgba(37, 117, 252, 0.1);
        }

        .floating-profile.glow {
            box-shadow: 0 0 20px var(--primary-color), 0 0 35px var(--secondary-color), inset 0 0 25px rgba(255,255,255,0.9);
        }
    </style>

    <div class="floating-profile" id="floatingProfileBtn">
        <i class="fas fa-user-shield"></i> </div>

    <div class="floating-menu" id="floatingMenu">
        <div class="menu-header">
            <i class="fas fa-fingerprint"></i>
            ID Pengguna: <?= htmlspecialchars($_SESSION['user_id']) ?>
        </div>
        <hr>
        <a href="user_profile.php">
            <i class="fas fa-id-badge"></i> Kelola Profil
        </a>
        <a href="logout.php">
            <i class="fas fa-power-off"></i> Keluar (Logout)
        </a>
    </div>

    <script>
        // Ambil elemen
        const btn = document.getElementById("floatingProfileBtn");
        const menu = document.getElementById("floatingMenu");

        // Variabel untuk drag
        let isDragging = false;
        let startX, startY; // Posisi mouse/touch saat dimulai
        let initialX, initialY; // Posisi awal tombol

        // Ukuran tombol
        const buttonSize = parseFloat(getComputedStyle(btn).getPropertyValue('--button-size'));
        const menuWidth = parseFloat(getComputedStyle(btn).getPropertyValue('--menu-width'));
        const rightOffset = 35; // Jarak default dari kanan

        // 1. Fungsi Toggle Menu
        function toggleFloatingMenu() {
            // Hanya jalankan jika tidak sedang dalam proses drag
            if (!isDragging) {
                menu.classList.toggle("active");
                btn.classList.toggle("glow");
                updateMenuPosition();
            }
        }
        
        // 2. Fungsi Update Posisi Menu
        function updateMenuPosition() {
            if (menu.classList.contains("active")) {
                // Ambil posisi tombol (menggunakan getBoundingClientRect)
                const rect = btn.getBoundingClientRect();
                const btnX = rect.left;
                const btnY = rect.top;
                
                // Hitung posisi menu
                // Menu harus berada di atas tombol dan disejajarkan ke kanan
                const menuLeft = btnX - menuWidth + buttonSize;
                const menuTop = btnY - menu.offsetHeight - 10; // 10px padding/jarak

                menu.style.left = `${menuLeft}px`;
                menu.style.top = `${menuTop}px`;
                menu.style.right = 'auto'; // Nonaktifkan properti right/bottom agar hanya menggunakan left/top
                menu.style.bottom = 'auto';
            } else {
                 // Reset posisi menu kembali ke posisi default fixed (di kanan bawah)
                menu.style.left = 'auto';
                menu.style.top = 'auto';
                menu.style.right = rightOffset + 'px';
                menu.style.bottom = buttonSize + 45 + 'px';
            }
        }

        // 3. Logika Geser (Drag)
        function startDrag(e) {
            e.preventDefault(); // Mencegah event default browser
            
            // Sembunyikan menu saat mulai drag
            menu.classList.remove("active");
            btn.classList.remove("glow");

            // Tentukan koordinat awal mouse/touch
            const clientX = e.clientX || e.touches[0].clientX;
            const clientY = e.clientY || e.touches[0].clientY;

            isDragging = true;
            btn.classList.add("dragging");
            btn.style.transition = 'none'; // Hentikan transisi CSS saat drag
            
            // Ambil posisi tombol saat ini
            const rect = btn.getBoundingClientRect();
            
            // Hitung offset: (Posisi mouse) - (Posisi tombol)
            startX = clientX - rect.left;
            startY = clientY - rect.top;

            // Atur posisi tombol menjadi 'absolute' untuk kemudahan drag, 
            // lalu gunakan 'fixed' lagi setelah drag selesai.
            // PENTING: Gunakan 'left' dan 'top' untuk drag
            btn.style.right = 'auto';
            btn.style.bottom = 'auto';
            btn.style.position = 'fixed';
            btn.style.left = rect.left + 'px';
            btn.style.top = rect.top + 'px';

            document.addEventListener('mousemove', onDragMove);
            document.addEventListener('mouseup', onDragEnd);
            document.addEventListener('touchmove', onDragMove);
            document.addEventListener('touchend', onDragEnd);
        }

        function onDragMove(e) {
            if (!isDragging) return;

            const clientX = e.clientX || e.touches[0].clientX;
            const clientY = e.clientY || e.touches[0].clientY;

            // Hitung posisi baru
            let newX = clientX - startX;
            let newY = clientY - startY;

            // Batasi agar tombol tidak keluar dari viewport
            newX = Math.max(0, Math.min(newX, window.innerWidth - buttonSize));
            newY = Math.max(0, Math.min(newY, window.innerHeight - buttonSize));

            // Terapkan posisi baru
            btn.style.left = newX + 'px';
            btn.style.top = newY + 'px';
        }

        function onDragEnd(e) {
            if (!isDragging) return;
            isDragging = false;
            btn.classList.remove("dragging");

            // Aktifkan kembali transisi CSS default setelah drag selesai
            btn.style.transition = ''; 
            
            // Hapus event listener
            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', onDragEnd);
            document.removeEventListener('touchmove', onDragMove);
            document.removeEventListener('touchend', onDragEnd);

            // Periksa apakah ini adalah event klik atau drag (Jika digeser minimal 5 pixel, anggap drag)
            const movedX = Math.abs(e.clientX - (btn.getBoundingClientRect().left + startX));
            const movedY = Math.abs(e.clientY - (btn.getBoundingClientRect().top + startY));

            if (movedX < 5 && movedY < 5) {
                // Jika tidak banyak bergerak, ini adalah klik/tap, jadi toggle menu
                toggleFloatingMenu();
            }
        }

        // Event Listener untuk memulai drag
        btn.addEventListener('mousedown', startDrag);
        btn.addEventListener('touchstart', startDrag);
        
        // 4. Sembunyikan menu jika mengklik di luar area menu/tombol
        document.addEventListener('click', function(event) {
            // Cek apakah klik berasal dari luar tombol dan luar menu
            const isClickInside = btn.contains(event.target) || menu.contains(event.target);

            if (!isClickInside && menu.classList.contains("active")) {
                menu.classList.remove("active");
                btn.classList.remove("glow");
            }
        });
        
        // Atur posisi menu saat window diresize
        window.addEventListener('resize', updateMenuPosition);
    </script>

    <?php
}
?>