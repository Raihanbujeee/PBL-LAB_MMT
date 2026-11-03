<?php
require_once __DIR__ . '/../config/auth.php';
startSecureSession();
// PATH: Naik satu tingkat dari /menu/ ke root /config/
require_once '../config/db.php'; // KONEKSI DATABASE
require_once '../config/settings.php'; // CMS Setting

// Components
require_once 'components/navbar.php';
require_once 'components/footer.php';
require_once 'components/floating_profile.php';

// Utilities
$path_prefix = "../"; // Digunakan untuk navigasi dari /menu/ ke root /
$cache_buster = time(); // Untuk refresh CSS/JS

// --- LOGIKA FILTER & PAGINATION ---
$limit = 6; 
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

$search_term = $_GET['search'] ?? '';
$filter_jenis = $_GET['jenis'] ?? 'semua';
$filter_acara = $_GET['acara'] ?? 'semua';
$filter_tahun = $_GET['tahun'] ?? 'semua';

// Query Builder
$sql_conditions = "WHERE 1=1";
$params = [];

if (!empty($search_term)) {
    $sql_conditions .= " AND (LOWER(caption) LIKE ? OR LOWER(deskripsi) LIKE ? OR LOWER(event_name) LIKE ?)";
    
    $lower_search_term = strtolower($search_term);
    
    $params[] = "%$lower_search_term%";
    $params[] = "%$lower_search_term%";
    $params[] = "%$lower_search_term%";
}

if ($filter_jenis != 'semua') {
    $sql_conditions .= " AND type = ?";
    $params[] = $filter_jenis;
}
 
if ($filter_acara != 'semua') {
    $sql_conditions .= " AND event_name = ?";
    $params[] = $filter_acara;
}

if ($filter_tahun != 'semua') {
    $sql_conditions .= " AND EXTRACT(YEAR FROM created_at) = ?";
    $params[] = $filter_tahun;
}

// Hitung Total Data
try {
    $sql_count = "SELECT COUNT(*) FROM media_assets $sql_conditions";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_items = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    $total_items = 0;
}
$total_pages = $total_items > 0 ? ceil($total_items / $limit) : 1;

// Query Final
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

try {
    $params_fetch = $params;
    $params_fetch[] = $limit;
    $params_fetch[] = $offset;

    $sql = "SELECT * FROM media_assets $sql_conditions ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_fetch);
    $media_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $media_items = []; 
}

// Data Dropdown
try {
    $types_stmt = $pdo->query("SELECT DISTINCT type FROM media_assets WHERE type IS NOT NULL ORDER BY type");
    $available_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

    $events_stmt = $pdo->query("SELECT DISTINCT event_name FROM media_assets WHERE event_name IS NOT NULL AND event_name != '' ORDER BY event_name");
    $available_events = $events_stmt->fetchAll(PDO::FETCH_COLUMN);

    $years_stmt = $pdo->query("SELECT DISTINCT EXTRACT(YEAR FROM created_at) as year FROM media_assets ORDER BY year DESC");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_types = []; $available_events = []; $available_years = [];
}

// Helper Pagination
function build_pagination($current, $total, $adj = 2) {
    $pages = [];
    if ($total <= 1) return [1];
    $pages[] = 1;
    $start = max(2, $current - $adj);
    $end = min($total - 1, $current + $adj);
    if ($start > 2) $pages[] = '...';
    for ($i = $start; $i <= $end; $i++) $pages[] = $i;
    if ($end < $total - 1) $pages[] = '...';
    if ($total > 1) $pages[] = $total;
    return array_unique($pages);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Multimedia - Laboratorium MMT</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="components/navbar.css?v=<?= $cache_buster ?>"> 
    <link rel="stylesheet" href="<?= $path_prefix ?>assets/css/style.css?v=<?= $cache_buster ?>"> 
    <link rel="stylesheet" href="assets/galeri/css/style-galeri.css?v=<?= $cache_buster ?>"> 
    
    <style>
        /* Background untuk seluruh halaman dengan wallpaper.jpg */
        body {
            background: url('<?= $path_prefix ?>assets/images/wallpaper.jpg') center center/cover fixed no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        } 
        
        /* Efek transparansi halus untuk area konten utama - LEBIH TRANSPARAN */
        .main-content-area {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Container untuk memastikan konten tetap readable */
        .container {
            position: relative;
            z-index: 1;
        }
        
        /* Hero section styling */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)),
                        url('<?= $path_prefix ?><?= htmlspecialchars($site_config['gallery_hero_image'] ?? 'assets/images/hero.jpg') ?>') center center/cover no-repeat;
        }
        
        /* Tambahan styling untuk card/content agar lebih transparan */
        .primary-content, .sidebar {
            border-radius: 12px;
            padding: 15px;
        }
        
        .search-filter-container, .gallery-grid-main, .pagination-controls, .no-results {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Gallery item styling */
        .gallery-item {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
        }
        
        .gallery-item:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Search results info */
        .search-results-info {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body id="top">
    <?php renderNavbar('galeri', $path_prefix, $site_config); ?>
    
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-breadcrumb">
                    <a href="<?= $path_prefix ?>index.php">Beranda</a> <i class="fas fa-chevron-right"></i> 
                    <span>Galeri Multimedia</span>
                </div>
                <h1><?= htmlspecialchars($site_config['gallery_title'] ?? 'Galeri Multimedia') ?></h1>
            </div>
        </section>

        <div class="main-content-area">
            <div class="container">

                <form class="search-filter-container horizontal-filter-form" action="" method="get">
                    
                    <div class="filter-item search-item">
                        <label>Pencarian</label>
                        <input type="text" name="search" placeholder="Cari media..." value="<?= htmlspecialchars($search_term) ?>">
                    </div>

                    <div class="filter-item">
                        <label>Kategori</label>
                        <select name="jenis">
                            <option value="semua">Semua</option>
                            <option value="foto" <?= ($filter_jenis == 'foto') ? 'selected' : '' ?>>Foto</option>
                            <option value="video" <?= ($filter_jenis == 'video') ? 'selected' : '' ?>>Video</option>
                            <option value="animasi" <?= ($filter_jenis == 'animasi') ? 'selected' : '' ?>>Animasi</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Tahun</label>
                        <select name="tahun">
                            <option value="semua">Semua</option>
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?= $y ?>" <?= ($filter_tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item button-item">
                        <label>&nbsp;</label>
                        <div style="display: flex; gap: 10px;">
                            <?php if (!empty($search_term) || $filter_jenis != 'semua' || $filter_tahun != 'semua'): ?>
                                <a href="galeri.php" class="btn-filter" style="background-color: #dc3545; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            <?php endif; ?>

                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                </form>


                <?php if (count($media_items) > 0): ?>
                <div class="gallery-grid-main">
                    <?php foreach ($media_items as $item): 
                        $icon = ($item['type'] === 'video') ? 'fa-play' : (($item['type'] === 'animasi') ? 'fa-film' : 'fa-camera');
                        $media_type = ucfirst($item['type']);
                        
                        // Fix path gambar
                        $is_link = filter_var($item['url'], FILTER_VALIDATE_URL);
                        $img_src = $is_link ? $item['url'] : $path_prefix . str_replace('../', '', $item['url']);
                    ?>
                    <a href="menu-detail-galeri/galeri-detail.php?id=<?= $item['id'] ?>" class="gallery-item">
                        <div class="image-container">
                            <?php if ($item['type'] == 'video'): ?>
                                <video width="100%" height="100%" style="object-fit: cover;" preload="metadata" muted>
                                    <source src="<?= htmlspecialchars($img_src) ?>#t=0.5" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($item['caption']) ?>">
                            <?php endif; ?>

                            <div class="overlay"><i class="fas <?= $icon ?>"></i></div>
                            <span class="type-badge"><?= $media_type ?></span>
                        </div>
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['caption']) ?></h4>
                            <?php if (!empty($item['event_name'])): ?>
                            <span class="event-tag"><?= htmlspecialchars($item['event_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination-controls">
                    <?php 
                    $qs = $_GET;
                    unset($qs['page']); 
                    $pages_show = build_pagination($page, $total_pages);

                    if ($page > 1) {
                        $qs['page'] = $page - 1;
                        echo '<a href="?' . http_build_query($qs) . '" class="btn-page">&laquo;</a>';
                    }

                    foreach ($pages_show as $p):
                        if ($p === '...') {
                            echo '<span class="page-ellipsis">...</span>';
                        } else {
                            $qs['page'] = $p;
                            $active = ($p == $page) ? 'active' : '';
                            echo '<a href="?' . http_build_query($qs) . '" class="btn-page ' . $active . '">' . $p . '</a>';
                        }
                    endforeach;

                    if ($page < $total_pages) {
                        $qs['page'] = $page + 1;
                        echo '<a href="?' . http_build_query($qs) . '" class="btn-page">&raquo;</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state-centered">
                            <div class="no-results no-results-content">
                                <h3>Tidak ada media ditemukan.</h3>
                                <p>Coba reset filter pencarian atau cek kembali kata kunci Anda.</p>
                                <a href="galeri.php" class="btn-yellow-action">Lihat Semua</a>
                            </div>
                        </div>
                    <?php endif; ?>
                
            </div>
        </div>
    </main>

    <?php
    renderFloatingProfile();
    renderFooter($path_prefix, $site_config);
    ?>

    <a href="#top" id="scrollTopBtn" class="scroll-top-btn" aria-label="Kembali ke atas">&uarr;</a>

    <script src="<?= $path_prefix ?>assets/js/navbar.js?v=<?= $cache_buster ?>"></script>
    <script src="<?= $path_prefix ?>assets/js/scrolltop.js?v=<?= $cache_buster ?>"></script>
    <script src="assets/galeri/js/script-galeri.js?v=<?= $cache_buster ?>"></script>

</body>
</html>