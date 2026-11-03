<?php
require_once __DIR__ . '/../config/auth.php';
startSecureSession();
// PATH: Naik satu tingkat dari /menu/ ke root /config/
require_once '../config/db.php';
require_once '../config/settings.php'; // CMS Setting

// Include components (Agar fungsi render Navbar/Footer/Profile tersedia)
require_once 'components/navbar.php';
require_once 'components/footer.php';
include 'components/floating_profile.php';

// Utilities
$path_prefix = "../"; // Digunakan untuk navigasi dari /menu/ ke root /
$cache_buster = time(); // Untuk refresh CSS/JS

// --- PENGATURAN HALAMAN ---
$limit = 6; // Tampilkan 6 proyek per halaman (agar pas 2 baris x 3 kolom)
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// --- FILTER PARAMETER ---
$search_term = $_GET['s'] ?? '';
$category_slug = $_GET['kategori'] ?? 'semua';
$tech_slug = $_GET['teknologi'] ?? 'semua';
$year = $_GET['tahun'] ?? 'semua';
$sort = $_GET['sort'] ?? 'terbaru';

// --- QUERY BUILDER ---
$sql_base = "FROM projects p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN project_tags pt ON p.id = pt.project_id
             LEFT JOIN tags t ON pt.tag_id = t.id
             WHERE (p.status = 'published' OR p.status = '1')";

$params = [];

if (!empty($search_term)) {
    $sql_base .= " AND (p.title LIKE ? OR p.slug LIKE ? OR p.summary LIKE ? OR p.description LIKE ?)";
    
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if ($category_slug != 'semua') {
    $sql_base .= " AND c.slug = ?";
    $params[] = $category_slug;
}

if ($tech_slug != 'semua') {
    $sql_base .= " AND EXISTS (SELECT 1 FROM project_tags pt_inner JOIN tags t_inner ON pt_inner.tag_id = t_inner.id WHERE pt_inner.project_id = p.id AND t_inner.slug = ?)";
    $params[] = $tech_slug;
}

if ($year != 'semua') {
    $sql_base .= " AND p.year = ?";
    $params[] = (int)$year;
}

$sql_base .= " GROUP BY p.id"; // Penting untuk menghindari duplikasi saat join tags

// --- HITUNG TOTAL DATA (MENGGUNAKAN $sql_base LENGKAP) ---
try {
    $sql_count = "SELECT COUNT(DISTINCT p.id) " . $sql_base; 
    
    $sql_count_final = "SELECT COUNT(*) FROM (" 
                     . "SELECT p.id " . $sql_base . ") AS count_alias";
    
    $stmt_count = $pdo->prepare($sql_count_final);
    $stmt_count->execute($params);
    $total_projects = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    $total_projects = 0;
}
$total_pages = $total_projects > 0 ? ceil($total_projects / $limit) : 1;

// Sorting
$order_by = " ORDER BY p.created_at DESC";
if ($sort == 'a-z') {
    $order_by = " ORDER BY p.title ASC";
}

// --- AMBIL DATA ---
try {
    $sql_final = "SELECT p.id, p.title, p.slug, p.summary, p.cover_image, p.demo_url
                  " . $sql_base . 
                  $order_by . 
                  " LIMIT ? OFFSET ?";

    $params_final = $params;
    $params_final[] = $limit;
    $params_final[] = $offset;

    $stmt_projects = $pdo->prepare($sql_final);
    $stmt_projects->execute($params_final);
    $projects = $stmt_projects->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $projects = [];
}

// Data Dropdown
try {
    $categories = $pdo->query("SELECT name, slug FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $tags = $pdo->query("SELECT name, slug FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $years = $pdo->query("SELECT DISTINCT year FROM projects WHERE year IS NOT NULL ORDER BY year DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = []; $tags = []; $years = [];
}

// Helper Pagination (sama seperti file sebelumnya)
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

$is_filter_active = !empty($search_term) || $category_slug != 'semua' || $year != 'semua';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Proyek - Laboratorium MMT</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="components/navbar.css?v=<?= $cache_buster ?>">
    <link rel="stylesheet" href="<?= $path_prefix ?>assets/css/style.css?v=<?= $cache_buster ?>">
    <link rel="stylesheet" href="assets/proyek/css/style-proyek.css?v=<?= $cache_buster ?>">
    
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
                        url('<?= $path_prefix ?><?= htmlspecialchars($site_config['project_hero_image'] ?? 'assets/images/hero.jpg') ?>') center center/cover no-repeat;
        }
        
        /* Tambahan styling untuk card/content agar lebih transparan */
        .project-filter-bar, .project-grid, .pagination-controls, .no-results {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Project card styling */
        .project-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
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
        
        /* Tag badges styling */
        .project-card-tags .tag-badge {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body id="top">
    <?php renderNavbar('proyek', $path_prefix, $site_config); ?>
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-breadcrumb">
                    <a href="<?= $path_prefix ?>index.php">Beranda</a> <i class="fas fa-chevron-right"></i> 
                    <span>Katalog Proyek</span>
                </div>
                <h1><?= htmlspecialchars($site_config['project_title'] ?? 'Katalog Proyek') ?></h1>
            </div>
        </section>

        <div class="main-content-area">
            <div class="container">
                
                <form class="search-filter-container horizontal-filter-form" action="" method="get">
    
    <div class="filter-item search-item">
        <label for="filter-search">Pencarian</label>
        <input type="search" id="filter-search" name="s" placeholder="Cari proyek..." value="<?= htmlspecialchars($search_term) ?>">
    </div>

    <div class="filter-item">
        <label for="filter-kategori">Kategori</label>
        <select id="filter-kategori" name="kategori">
            <option value="semua">Semua</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['slug'] ?>" <?= ($category_slug == $cat['slug']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-item">
        <label for="filter-tahun">Tahun</label>
        <select id="filter-tahun" name="tahun">
            <option value="semua">Semua</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y['year'] ?>" <?= ($year == $y['year']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($y['year']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-item button-item">
        <label>&nbsp;</label>
        <div style="display: flex; gap: 10px;">
            <?php if ($is_filter_active): ?>
                <a href="proyek.php" class="btn-filter" style="background-color: #dc3545; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>

            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>
</form>
                    

                <div class="project-grid <?= ($total_projects == 0) ? 'grid-empty' : '' ?>">
                    <?php
                    if ($projects):
                        $sql_tags = "SELECT t.name FROM tags t JOIN project_tags pt ON t.id = pt.tag_id WHERE pt.project_id = ?";
                        $stmt_tags = $pdo->prepare($sql_tags);

                        foreach ($projects as $project):
                            // Ambil tags untuk setiap proyek
                            $stmt_tags->execute([ $project['id'] ]);
                            $tags_list = $stmt_tags->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Fix path gambar proyek
                            $img_proyek_raw = $project['cover_image'] ?? 'assets/images/default.jpg';
                            $img_proyek = $path_prefix . str_replace('../', '', $img_proyek_raw);
                            
                            // Tentukan link detail proyek
                            $detail_link = "menu-proyek-detail/detail-proyek.php?slug=" . htmlspecialchars($project['slug']);
                            ?>
                            
                            <a href="<?= $detail_link ?>" class="project-card">
                                <div class="project-card-thumbnail">
                                    <img src="<?= htmlspecialchars($img_proyek) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
                                </div>
                                <div class="project-card-content">
                                    <h4><?= htmlspecialchars($project['title']) ?></h4>
                                    <p><?= htmlspecialchars(substr($project['summary'], 0, 100)) ?>...</p>
                                    <div class="project-card-tags">
                                        <?php foreach($tags_list as $t): ?>
                                            <span class='tag-badge'><?= htmlspecialchars($t['name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </a>
                        
                        <?php 
                        endforeach;
                    else:
                        echo "<div class='no-results'>
                                    <h3>Tidak ada proyek yang ditemukan.</h3>
                                    <p>Coba reset filter pencarian atau cek kembali kata kunci Anda.</p>
                                    <a href='proyek.php' class='btn'>Lihat Semua</a>
                                </div>";
                    endif; 
                    ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination-controls">
                    <?php 
                    $qs = $_GET; 
                    unset($qs['page']); 
                    $pages_show = build_pagination($page, $total_pages);

                    // Prev
                    if ($page > 1) {
                        $qs['page'] = $page - 1;
                        echo '<a href="?' . http_build_query($qs) . '" class="btn-page">&laquo;</a>';
                    }

                    // Angka
                    foreach ($pages_show as $p):
                        if ($p === '...') {
                            echo '<span class="page-ellipsis">...</span>';
                        } else {
                            $qs['page'] = $p;
                            $activeClass = ($p == $page) ? 'active' : '';
                            echo '<a href="?' . http_build_query($qs) . '" class="btn-page ' . $activeClass . '">' . $p . '</a>';
                        }
                    endforeach;

                    // Next
                    if ($page < $total_pages) {
                        $qs['page'] = $page + 1;
                        echo '<a href="?' . http_build_query($qs) . '" class="btn-page">&raquo;</a>';
                    }
                    ?>
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
    <script src="assets/proyek/js/script-proyek.js?v=<?= $cache_buster ?>"></script>

</body>
</html>