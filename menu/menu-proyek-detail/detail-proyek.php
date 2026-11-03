<?php
// menu/menu-proyek-detail/detail-proyek.php
require_once __DIR__ . '/../../config/auth.php';
startSecureSession();

// PATH: Naik dua tingkat (../../) untuk mencapai root /
$root_prefix = "../../"; 
// PATH: Naik satu tingkat (../) untuk mencapai /menu/
$menu_prefix = "../";
$cache_buster = time(); // Untuk refresh CSS/JS

// Config files are in root, so use $root_prefix
require_once $root_prefix . 'config/db.php'; 
require_once $root_prefix . 'config/settings.php'; // PENTING: CMS Setting

// Components are in /menu/, so use $menu_prefix
require_once $menu_prefix . 'components/navbar.php';
require_once $menu_prefix . 'components/footer.php';
include $menu_prefix . 'components/floating_profile.php'; 

// Panggil Floating Profile sebelum HTML
renderFloatingProfile();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    // Kembali ke /menu/proyek.php
    header("Location: ../proyek.php");
    exit();
}

// Query Proyek + Kategori
$sql = "SELECT p.*, c.name as category_name 
        FROM projects p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.slug = ? AND (p.status = 'published' OR p.status = '1')";
$stmt = $pdo->prepare($sql);
$stmt->execute([$slug]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo "<h1>Proyek tidak ditemukan!</h1><a href='../proyek.php'>Kembali</a>";
    exit();
}

// --- PERBAIKAN PATH GAMBAR ---
$clean_img = str_replace('../', '', $project['cover_image'] ?? 'assets/images/default.jpg');
// Tambahkan $root_prefix (../../) agar mundur 2 langkah ke root
$final_img = $root_prefix . $clean_img;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> - Proyek Lab MMT</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= $root_prefix ?>assets/css/style.css?v=<?= $cache_buster ?>">
    <link rel="stylesheet" href="<?= $menu_prefix ?>components/navbar.css?v=<?= $cache_buster ?>">
    
    <link rel="stylesheet" href="assets/det-pro/css/style-detail-proyek.css?v=<?= $cache_buster ?>">

    <style>
        /* Background untuk seluruh halaman dengan wallpaper.jpg */
        body {
            background: url('<?= $root_prefix ?>assets/images/wallpaper.jpg') center center/cover fixed no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        /* Efek transparansi halus untuk area konten utama */
        .main-content-area {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: relative;
            padding: 60px 0;
        }
        
        /* Container untuk memastikan konten tetap readable */
        .container {
            position: relative;
            z-index: 1;
        }
        
        /* Hero section styling */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.8)), 
                        url('<?= $final_img ?>') center center/cover no-repeat;
            height: 400px !important;
        }
        
        /* Penyesuaian Judul di Hero */
        .hero h1 {
            font-size: 36px;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        
        .project-meta-hero {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
        }
        
        /* Primary content area */
        .primary-content {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        /* Back link button */
        .back-link {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #333;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
        }
        
        /* Project image */
        .primary-content img {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Content styling */
        .primary-content h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .lead-paragraph {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 20px;
            margin-bottom: 25px;
            line-height: 1.7;
            font-size: 16px;
        }
        
        .primary-content p {
            line-height: 1.8;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* Demo button */
        .btn {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #333;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Sidebar widgets */
        .sidebar .widget {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        /* Widget lists */
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar ul li {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 15px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .sidebar ul li:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateX(5px);
        }
        
        /* Related projects list */
        .sidebar ul li a {
            text-decoration: none;
            font-weight: 600;
            color: #333;
        }
        
        .sidebar ul li span {
            display: block;
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .hero { height: 300px; }
            .hero h1 { font-size: 24px; }
        }
    </style>
</head>
<body id="top">

    <?php renderNavbar('proyek', $root_prefix, $site_config); ?>

    <section class="hero">
        <div class="container">
            <h1><?= htmlspecialchars($project['title']) ?></h1>
            <div class="project-meta-hero">
                <i class="fas fa-folder"></i> <?= htmlspecialchars($project['category_name'] ?? 'Uncategorized') ?> 
                &nbsp;&nbsp;|&nbsp;&nbsp; 
                <i class="fas fa-calendar"></i> <?= htmlspecialchars($project['year']) ?>
            </div>
        </div>
    </section>

    <div class="main-content-area">
        <div class="container">
            
            <div class="primary-content">
                <a href="../proyek.php" class="btn-secondary back-link">&larr; Kembali ke Daftar Proyek</a>

                <img src="<?= $final_img ?>" alt="<?= htmlspecialchars($project['title']) ?>">
                
                <h3>Deskripsi Proyek</h3>
                <div class="lead-paragraph">
                    <?= nl2br(htmlspecialchars($project['summary'] ?? '')) ?>
                </div>
                
                <p><?= nl2br(htmlspecialchars($project['description'] ?? '')) ?></p>

                <?php if (!empty($project['demo_url'])): ?>
                <div style="margin-top: 30px;">
                    <a href="<?= htmlspecialchars($project['demo_url']) ?>" target="_blank" class="btn">
                        <i class="fas fa-external-link-alt"></i> Lihat Demo Proyek
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <aside class="sidebar">
                <div class="widget">
                    <h3 class="widget-title">Informasi Proyek</h3>
                    <ul>
                        <li>
                            <strong>Kategori:</strong><br>
                            <?= htmlspecialchars($project['category_name'] ?? '-') ?>
                        </li>
                        <li>
                            <strong>Tahun Pembuatan:</strong><br>
                            <?= htmlspecialchars($project['year']) ?>
                        </li>
                        <?php if (!empty($project['repo_url'])): ?>
                        <li>
                            <strong>Repository:</strong><br>
                            <a href="<?= htmlspecialchars($project['repo_url']) ?>" target="_blank">GitHub / GitLab</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="widget">
                    <h3 class="widget-title">Proyek Lainnya</h3>
                    <ul>
                        <?php
                        // PERBAIKAN: Gunakan ORDER BY RANDOM() jika menggunakan SQLite, atau ORDER BY RAND() jika MySQL
                        // Asumsi menggunakan SQLite karena sudah menggunakan sintaks RANDOM()
                        $stmt_other = $pdo->prepare("SELECT title, slug, year FROM projects WHERE slug != ? AND (status = 'published' OR status = '1') ORDER BY RANDOM() LIMIT 3");
                        $stmt_other->execute([$slug]);
                        
                        if ($stmt_other->rowCount() > 0):
                            while($other = $stmt_other->fetch()):
                        ?>
                        <li>
                            <a href="detail-proyek.php?slug=<?= $other['slug'] ?>">
                                <?= htmlspecialchars($other['title']) ?>
                            </a>
                            <span><?= $other['year'] ?></span>
                        </li>
                        <?php 
                            endwhile; 
                        else:
                            echo "<li>Tidak ada proyek terkait lainnya.</li>";
                        endif;
                        ?>
                    </ul>
                </div>
            </aside>

        </div>
    </div>

    <?php 
    // Panggil Footer dengan prefix yang sesuai
    renderFooter($root_prefix, $site_config); 
    ?>

    <a href="#top" id="scrollTopBtn" class="scroll-top-btn" aria-label="Kembali ke atas">&uarr;</a>

    <script src="<?= $root_prefix ?>assets/js/navbar.js?v=<?= $cache_buster ?>"></script>
    <script src="<?= $root_prefix ?>assets/js/scrolltop.js?v=<?= $cache_buster ?>"></script>
    <script src="assets/det-pro/js/script-detail-proyek.js?v=<?= $cache_buster ?>"></script>
</body>
</html>