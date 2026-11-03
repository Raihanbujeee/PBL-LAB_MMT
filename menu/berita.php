<?php
require_once __DIR__ . '/../config/auth.php';
startSecureSession();
// PATH: Naik satu tingkat dari /menu/ ke root /config/
require_once '../config/db.php'; // KONEKSI DATABASE ANDA
require_once '../config/settings.php'; // CMS Setting

// Components
require_once 'components/navbar.php';
require_once 'components/footer.php';
require_once 'components/floating_profile.php';

// Utilities
$path_prefix = "../"; // Digunakan untuk navigasi ke root dari /menu/
$cache_buster = time(); // Untuk refresh CSS/JS

// ==========================================
// 1. LOGIKA UTAMA (Filter, Search, Paging)
// ==========================================

$search_term = $_GET['search'] ?? '';
$filter_kategori = $_GET['kategori'] ?? 'semua';
$filter_tahun = $_GET['tahun'] ?? 'semua';

$limit = 3; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query Kondisi
$sql_conditions = "WHERE status = 'published'";
$params = [];

if (!empty($search_term)) {
    $sql_conditions .= " AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if ($filter_kategori != 'semua') {
    $sql_conditions .= " AND category = ?";
    $params[] = $filter_kategori;
}

if ($filter_tahun != 'semua') {
    // PERBAIKAN: Gunakan EXTRACT(YEAR FROM created_at) sesuai hasil debug
    $sql_conditions .= " AND EXTRACT(YEAR FROM created_at) = ?";
    $params[] = $filter_tahun;
}

// Hitung Total Data (Untuk Paging)
try {
    $count_sql = "SELECT COUNT(*) FROM news $sql_conditions";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_news = (int) $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_news = 0;
}

$total_pages = $total_news > 0 ? (int) ceil($total_news / $limit) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Ambil Data Berita (List Bawah)
try {
    $params_fetch = $params;
    $params_fetch[] = $limit;
    $params_fetch[] = $offset;

    $sql = "SELECT * FROM news $sql_conditions ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_fetch);
    $news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $news_items = [];
}

// Event Highlight (Banner Atas - Tidak Kena Paging)
try {
    $event_sql = "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 1";
    $event_stmt = $pdo->prepare($event_sql);
    $event_stmt->execute();
    $event_highlight = $event_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $event_highlight = null;
}

// Data Dropdown - PERBAIKAN: Gunakan EXTRACT untuk query tahun juga
try {
    // Query tahun dengan EXTRACT yang sama dengan filter
    $years_stmt = $pdo->query("SELECT DISTINCT EXTRACT(YEAR FROM created_at) as year FROM news WHERE status = 'published' AND created_at IS NOT NULL ORDER BY year DESC");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Pastikan tahun dalam format integer
    $available_years = array_map('intval', $available_years);
    
    // Jika tidak ada tahun di database, gunakan default 2022-2025
    if (empty($available_years)) {
        $available_years = [2025, 2024, 2023, 2022];
    }
    
    // Untuk kategori, ambil dari database
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM news WHERE status = 'published' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    $available_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Jika tidak ada kategori di database, gunakan default
    if (empty($available_categories)) {
        $available_categories = ['berita', 'kegiatan'];
    }
    
} catch (PDOException $e) {
    $available_years = [2025, 2024, 2023, 2022];
    $available_categories = ['berita', 'kegiatan'];
}

// Kalender Data - PERBAIKAN: Gunakan EXTRACT atau DATE() untuk konsistensi
$events_by_date = [];
try {
    $stmt_events = $pdo->query("SELECT id, title, slug, summary, DATE(created_at) as event_date FROM news WHERE status = 'published' ORDER BY created_at DESC");
    $rows = $stmt_events->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $d = $r['event_date'];
        if (!isset($events_by_date[$d])) $events_by_date[$d] = [];
        // Pastikan link detail berita sudah benar
        $events_by_date[$d][] = [
            'title' => $r['title'], 
            'slug' => $r['slug'], 
            'summary' => $r['summary'], 
            'link' => "menu-detail-berita/detail-berita.php?slug={$r['slug']}"
        ]; 
    }
} catch (PDOException $e) { 
    $events_by_date = []; 
}

// Encode untuk JavaScript. Menggunakan JSON_UNESCAPED_UNICODE untuk kompatibilitas.
$events_json = json_encode($events_by_date, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

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
    if ($total > 1 && $total != 1) $pages[] = $total;
    return array_unique($pages);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita & Kegiatan - Laboratorium MMT</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="components/navbar.css?v=<?= $cache_buster ?>"> 
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= $cache_buster ?>"> 
    
    <style>
        /* 
        |--------------------------------------------------------------------------
        | STYLE BERITA - CSS yang digabungkan dari style-berita.css
        |--------------------------------------------------------------------------
        */
        
        :root {
            /* Definisi Warna yang diperbarui: Lebih cerah dan premium */
            --color-primary: #FE7927 !important; /* DIUBAH: #FF7043 menjadi #FE7927 dengan !important */
            --color-accent: #FFC107;
            --color-contrast: #003B8E;
            --color-text: #333333;
            --color-light-bg: #F9F9F9;
            --color-light-hover: #EDEDED;
            --color-shadow: rgba(0, 0, 0, 0.08);
            --border-radius: 8px;
        }

        /* OVERRIDE SUPER STRONG UNTUK WARNA OREN #FE7927 - Tambahkan di bagian ATAS */
        .navbar,
        .site-header,
        .header {
            background-color: #FE7927 !important;
        }

        .navbar .nav-links a.active,
        .navbar .nav-links a:hover,
        .header .nav-links a.active,
        .header .nav-links a:hover {
            color: #FE7927 !important;
        }

        .navbar .nav-links a.active::after,
        .header .nav-links a.active::after {
            background-color: #FE7927 !important;
        }

        /* Override untuk semua elemen dengan warna biru/orange lama */
        [style*="background-color: #003b8e"],
        [style*="background-color:#003b8e"],
        [style*="background-color: #FF7043"],
        [style*="background-color:#FF7043"] {
            background-color: #FE7927 !important;
        }

        [style*="color: #003b8e"],
        [style*="color:#003b8e"],
        [style*="color: #FF7043"],
        [style*="color:#FF7043"] {
            color: #FE7927 !important;
        }

        [style*="border-color: #003b8e"],
        [style*="border-color:#003b8e"],
        [style*="border-color: #FF7043"],
        [style*="border-color:#FF7043"] {
            border-color: #FE7927 !important;
        }

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
                        url('<?= $path_prefix ?><?= htmlspecialchars($site_config['news_hero_image'] ?? 'assets/images/hero.jpg') ?>') center center/cover no-repeat;
        }
        
        /* Tambahan styling untuk card/content agar lebih transparan */
        .primary-content, .sidebar {
            border-radius: 12px;
            padding: 15px;
        }
        
        .widget, .facility-item, .search-filter-container {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Event highlight lebih transparan */
        .event-highlight {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        /* Pagination controls transparan */
        .pagination-controls {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            padding: 15px;
        }
        
        /* No results transparan */
        .no-results {
            background: none;
            /* background: rgba(255, 255, 255, 0.7); */
            backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 30px;
        }

        /* --- Global Layout/Hero Overrides --- */
/* ============================================
   HERO SECTION - SAMA PERSIS DENGAN PROFIL.PHP
   ============================================ */
/* HAPUS SEMUA CSS HERO YANG ADA */
/* TAMBAHKAN INI DI BAGIAN INLINE STYLE (di dalam <style> tag) */

/* RESET DAN BUAT SAMA PERSIS */
.hero {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)),
                url('<?= $path_prefix ?><?= htmlspecialchars($site_config['news_hero_image'] ?? 'assets/images/hero.jpg') ?>') center center/cover no-repeat;
    padding: 60px 0 30px 0 !important; /* ATAS 60px, BAWAH 30px */
}

/* --- HANYA MENGUBAH WARNA TULISAN 'BERANDA' --- */
.hero-breadcrumb a:first-child {
    color: #d74709 !important; /* Warna oranye spesifik Anda */
    text-decoration: none !important;
}

/* Pastikan saat di-hover warnanya tetap oranye atau sedikit lebih terang */
.hero-breadcrumb a:first-child:hover {
    color: #ff6600 !important;
}

.hero h1 {
    font-size: 2rem !important; /* UKURAN YANG SAMA DENGAN PROFIL */
    margin-top: 0 !important;
}

        .main-content-area .container {
            display: grid;
            grid-template-columns: 2.5fr 1fr;
            gap: 50px; 
            padding: 60px 15px;
        }

        @media (max-width: 1024px) {
            .main-content-area .container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 40px 15px;
            }
            .hero {
                height: 250px !important;
                padding-bottom: 30px;
            }
        }

        /* --- Event Highlight Section (Banner Atas) --- */
        .event-highlight {
            display: flex;
            background-color: #FFFFFF;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 50px;
            box-shadow: 0 8px 20px var(--color-shadow); 
            transition: transform 0.3s ease;
        }

        .event-highlight:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
        }

        .event-highlight-img {
            flex: 1.5;
            max-height: 400px;
            min-height: 300px;
            background-color: var(--color-light-bg);
        }

        .event-highlight-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-highlight-content {
            flex: 1;
            padding: 35px;
            background-color: #FE7927 !important; 
            color: white;
        }

        .event-tag {
            display: inline-block;
            background-color: var(--color-accent);
            color: #FE7927 !important; 
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .event-highlight-content h2 {
            font-size: 2rem;
            line-height: 1.2;
            margin-bottom: 10px;
            color: white;
        }

        .event-date {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
        }

        .event-date i {
            margin-right: 5px;
        }

        .event-highlight-content p {
            margin-bottom: 25px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .event-highlight-content .btn {
            background-color: white;
            color: #FE7927 !important;
            border: 2px solid white;
            font-weight: 700;
            padding: 10px 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            border-radius: 5px;
        }

        .event-highlight-content .btn:hover {
            background-color: var(--color-accent);
            color: #FE7927 !important; 
            border-color: var(--color-accent);
        }

        @media (max-width: 768px) {
            .event-highlight {
                flex-direction: column;
            }
            .event-highlight-img {
                min-height: 250px;
                flex: none;
            }
            .event-highlight-content {
                padding: 30px;
            }
            .event-highlight-content h2 {
                font-size: 1.5rem;
            }
        }

        /* --- Search & Filter Container (Diperbaiki & Disempurnakan) --- */
        .search-filter-container {
            margin-bottom: 30px;
            padding: 25px;
            border: none;
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px); 
            border: 1px solid rgba(255, 255, 255, 0.4); 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .search-filter-container form {
            width: 100%;
        }

        .search-row {
            display: flex;
            gap: 15px;
            align-items: flex-end; /* Memastikan input dan tombol sejajar di bawah */
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: flex-end; /* Memastikan select dan tombol sejalar di bawah */
            margin-bottom: 15px;
        }

        .search-input-group, .filter-group {
            flex-grow: 1;
        }

        .search-input-group label, .filter-group label {
            display: block;
            color: var(--color-text);
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .search-input-group input[type="text"], .filter-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #DDDDDD;
            border-radius: 5px;
            transition: border-color 0.3s;
            background-color: white;
            height: 44px; 
            box-sizing: border-box; 
        }

        .search-input-group input[type="text"]:focus, .filter-group select:focus {
            border-color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            outline: none;
            box-shadow: 0 0 5px rgba(254, 121, 39, 0.5);
        }

        .btn-search {
            flex-shrink: 0; 
            padding: 12px 25px;
            background-color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            height: 44px; 
        }

        .btn-search:hover {
            background-color: #e56a1e !important; /* DIUBAH: #f76036 menjadi #e56a1e */
        }

        .btn-filter {
            flex-shrink: 0; 
            padding: 12px 25px;
            background-color: var(--color-accent);
            color: #FE7927 !important; 
            border: none;
            font-weight: 700;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            height: 44px; 
        }
        .btn-filter:hover {
            background-color: #ffaa00;
        }

        .search-results-info {
            padding: 15px 0 0;
            border-top: 1px dashed #DDD;
            font-weight: 600;
            color: #555;
            margin-top: 10px;
            font-size: 0.95rem;
        }

        .search-results-info a {
            color: var(--color-contrast) !important; 
            font-weight: 700;
            margin-left: 10px;
        }

        @media (max-width: 600px) {
            .search-row, .filter-row {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            .search-input-group, .filter-group {
                margin-bottom: 5px;
            }
            .btn-search, .btn-filter {
                width: 100%;
                margin-top: 5px;
            }
        }

        /* --- Dekorasi Pemisah Visual (BARU) --- */
        .section-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 40px 0;
            opacity: 0.7;
        }

        .separator-line {
            height: 1px;
            background-color: #DDD;
            flex-grow: 1;
        }

        .separator-icon {
            color: var(--color-accent); /* Kuning/Emas */
            font-size: 1.5rem;
            margin: 0 20px;
        }

        /* --- News Item List (Card Berita) - Peningkatan Estetika --- */
        .facility-item {
            display: flex;
            align-items: flex-start; 
            gap: 30px;
            margin-bottom: 40px;
            padding: 25px;
            border: none;
            border-radius: var(--border-radius);
            background-color: #FFFFFF;
            
            /* Bayangan baru (lebih 3D) */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); 
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Transisi halus */
        }

        .facility-item:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Bayangan dramatis saat hover */
            transform: translateY(-5px); /* Sedikit naik untuk efek melayang */
        }

        .facility-image {
            flex: 0 0 280px; 
            height: 200px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .facility-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease-in-out; /* Animasi zoom gambar */
        }

        .facility-item:hover .facility-image img {
            transform: scale(1.05); 
        }

        .facility-text {
            flex-grow: 1;
        }

        .facility-text h2 {
            font-size: 1.6rem;
            margin-bottom: 5px;
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            line-height: 1.3;
        }

        .article-metadata {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .article-metadata i {
            color: var(--color-accent); /* Kuning */
            margin-right: 5px;
        }

        /* Pemisah Metadata */
        .metadata-separator {
            color: #CCC;
            margin: 0 10px;
        }

        /* Kategori di Metadata */
        .article-category {
            font-weight: 600;
            color: var(--color-text);
        }

        .facility-text p {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.5;
        }

        .facility-text .btn {
            margin-top: 15px;
            background-color: transparent;
            border: 2px solid #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            padding: 6px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .facility-text .btn:hover {
            background-color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            color: white;
            box-shadow: 0 4px 10px rgba(254, 121, 39, 0.4); /* Bayangan pada tombol hover */
        }

        @media (min-width: 769px) {
            .facility-item.image-right {
                flex-direction: row-reverse;
            }
        }
        @media (max-width: 768px) {
            .facility-item {
                flex-direction: column;
                text-align: left;
                padding: 20px;
                margin-bottom: 30px;
            }
            .facility-image {
                width: 100%;
                height: 220px;
                flex: none;
                margin-bottom: 15px;
            }
            .facility-text h2 {
                font-size: 1.4rem;
            }
        }

        /* --- Pagination Controls --- */
        .pagination-controls {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }

        .btn-page, .page-ellipsis {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #DDD;
            border-radius: 50%;
            font-size: 1rem;
            color: var(--color-primary); 
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-page:hover {
            background-color: var(--color-accent);
            color: var(--color-primary); 
            border-color: var(--color-accent);
        }

        .btn-page.active {
            background-color: var(--color-primary); /* Kembali ke variable, tanpa !important */
            color: white; /* Tetap putih untuk kontras */
            border-color: var(--color-primary); /* Kembali ke variable, tanpa !important */
            transform: scale(1.1);
        }

        .page-ellipsis {
            border: none;
            color: #999;
        }

        /* --- Sidebar (Aside) --- */
        .sidebar {
            position: sticky;
            top: 100px;
            align-self: flex-start;
        }

        .widget {
            margin-bottom: 40px;
            padding: 25px;
            background-color: #FFFFFF;
            box-shadow: 0 4px 15px var(--color-shadow);
            border-radius: var(--border-radius);
        }

        .widget-title {
            font-size: 1.5rem;
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            border-bottom: 3px solid var(--color-accent); /* Kuning */
            margin-bottom: 25px;
            padding-bottom: 10px;
            font-weight: 700;
        }

        /* Kalender Widget */
        .widget-calendar {
            text-align: center;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 0 10px;
        }
        .cal-nav-prev, .cal-nav-next {
            text-decoration: none; 
            font-weight: bold; 
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            font-size: 1.2rem;
        }
        .cal-current-month {
            font-weight: bold; 
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar-table th {
            font-weight: 700;
            color: #555;
            font-size: 0.9rem;
            padding-bottom: 10px;
        }

        .calendar-table td {
            padding: 8px 0;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
            height: 40px;
            width: calc(100% / 7);
        }

        .calendar-table td:hover:not(.event-day) {
            background-color: var(--color-light-bg);
            border-radius: 50%;
        }

        .calendar-table td.event-day {
            background-color: var(--color-accent); /* Kuning */
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            border-radius: 50%;
            font-weight: 700;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 2px 5px rgba(255, 193, 7, 0.4);
        }

        .calendar-table td.event-day:hover {
            background-color: #ffaa00; /* Kuning lebih gelap saat hover */
            transform: scale(1.05);
        }

        /* Style untuk hari ini di kalender */
        .calendar-table td.today-day {
            border: 2px solid #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            border-radius: 50%;
        }

        /* Widget Terkini (Kontras Kuat) */
        .widget-news {
            background-color: #FFFFFF; /* Diubah dari var(--color-primary) ke putih */
            box-shadow: 0 4px 15px var(--color-shadow); /* Kembali ke bayangan standar */
            color: var(--color-text); /* Teks kembali ke warna gelap */
        }

        .widget-news ul {
            list-style: none;
            padding: 0;
        }

        .widget-news li {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1); /* Border lebih gelap */
        }

        .widget-news li:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .widget-news .widget-title {
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            border-bottom: 3px solid var(--color-accent); /* Kuning/Emas tetap menjadi aksen */
        }

        .widget-news li a {
            transition: padding-left 0.2s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
        }
        .widget-news li a:hover {
            padding-left: 5px;
        }

        .widget-news li h4 {
            font-size: 1.05rem;
            color: var(--color-text); /* Kembali ke warna teks gelap */
            line-height: 1.3;
            margin-bottom: 3px;
            transition: color 0.3s;
        }

        .widget-news li a:hover h4 {
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
        }

        .widget-news li .date {
            font-size: 0.85rem;
            color: #777; /* Warna abu-abu untuk tanggal */
        }

        .no-results {
            /* background-color: var(--color-light-bg); */
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(4px); /* Blur lebih halus */
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 50px 0;
            margin-bottom: 30px;
        }

        .no-results h3 {
            color: var(--color-text);
        }

        /* --- CSS untuk Modal Kalender --- */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none; 
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal {
            background-color: white;
            padding: 35px;
            border-radius: 12px;
            width: 95%;
            max-width: 550px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal h3 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.6rem;
            border-bottom: 2px solid #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            padding-bottom: 10px;
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
        }

        .modal .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #FE7927 !important; /* DIUBAH: var(--color-primary) menjadi #FE7927 langsung */
            line-height: 1;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .modal .close-btn:hover {
            opacity: 1;
        }

        .event-item {
            padding: 15px 0;
            border-bottom: 1px dashed #DDD;
        }
        .event-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .event-item h4 {
            margin-top: 0;
            margin-bottom: 5px;
        }
        .event-item h4 a {
            font-weight: 700;
            color: var(--color-contrast); /* Biru */
            transition: color 0.3s;
        }
        .event-item h4 a:hover {
            color: var(--color-accent);
            text-decoration: underline;
        }

        .event-item p {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            line-height: 1.5;
        }

        /* PAKSA UBAH WARNA BACKGROUND FOOTER */
        footer, 
        .site-footer, 
        [style*="background-color"] {
            background-color: #d74709ff !important; /* Warna Oranye Gelap / Cokelat Oranye */
            background: #d74709ff !important;
        }

        /* Jika footer menggunakan tag section atau div khusus di dalam fungsi renderFooter */
        main + footer, 
        footer div, 
        footer section {
            background-color: #d74709ff !important;
        }
    </style>
</head>
<body id="top">
    <?php 
        // Memanggil renderNavbar dengan path prefix dan site config
        renderNavbar('berita', $path_prefix, $site_config); 
    ?>
    
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-breadcrumb">
                    <a href="<?= $path_prefix ?>index.php">Beranda</a> <i class="fas fa-chevron-right"></i> 
                    <span>Berita & Kegiatan</span>
                </div>
                <h1><?= htmlspecialchars($site_config['news_title'] ?? 'Berita & Kegiatan') ?></h1>
            </div>
        </section>

        <div class="main-content-area">
            <div class="container">
                
                <div class="primary-content">

                    <?php if ($event_highlight && empty($search_term) && $filter_kategori == 'semua' && $filter_tahun == 'semua'): 
                        $hl_img = str_replace('../', '', $event_highlight['cover_image']);
                    ?>
                    <section class="event-highlight">
                        <div class="event-highlight-img">
                            <img src="<?= $path_prefix ?><?= htmlspecialchars($hl_img) ?>" alt="<?= htmlspecialchars($event_highlight['title']) ?>">
                        </div>
                        <div class="event-highlight-content">
                            <span class="event-tag"><?= htmlspecialchars($event_highlight['category'] ?? 'Terbaru') ?></span>
                            <h2><?= htmlspecialchars($event_highlight['title']) ?></h2>
                            <p class="event-date"><i class="fas fa-calendar-alt"></i> <?= date('d F Y', strtotime($event_highlight['created_at'])) ?></p>
                            <p><?= htmlspecialchars(substr($event_highlight['summary'], 0, 150)) ?>...</p>
                            
                            <a href="menu-detail-berita/detail-berita.php?slug=<?= htmlspecialchars($event_highlight['slug']) ?>" class="btn">Baca Selengkapnya</a>
                        </div>
                    </section>
                    <?php endif; ?>

                    <div class="search-filter-container">
                        <form action="" method="get">
                            <div class="search-row">
                                <div class="search-input-group">
                                    <label>Cari Berita</label>
                                    <input type="text" name="search" placeholder="Kata kunci..." value="<?= htmlspecialchars($search_term) ?>">
                                </div>
                                <button type="submit" class="btn-search">Cari</button>
                            </div>

                            <div class="filter-row">
                                <div class="filter-group">
                                    <label>Kategori</label>
                                    <select name="kategori">
                                        <option value="semua">Semua</option>
                                        <?php foreach ($available_categories as $cat): ?>
                                            <?php 
                                            // Tampilkan nama kategori yang lebih user-friendly
                                            $cat_display = ($cat == 'berita') ? 'Berita' : (($cat == 'kegiatan') ? 'Kegiatan' : $cat);
                                            ?>
                                            <option value="<?= $cat ?>" <?= ($filter_kategori == $cat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat_display) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Tahun</label>
                                    <select name="tahun">
                                        <option value="semua">Semua</option>
                                        <?php foreach ($available_years as $y): ?>
                                            <option value="<?= $y ?>" <?= ($filter_tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-filter">Filter</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="section-separator">
                        <span class="separator-line"></span>
                        <i class="fas fa-newspaper separator-icon"></i> <span class="separator-line"></span>
                    </div>

                    <?php if (count($news_items) > 0): ?>
                        <?php 
                        $pos = 'left';
                        foreach ($news_items as $item): 
                            $class = ($pos == 'left') ? 'image-left' : 'image-right';
                            $pos = ($pos == 'left') ? 'right' : 'left';
                            // Menghapus '../' pada path cover_image yang disimpan di DB
                            $img_news = str_replace('../', '', $item['cover_image']);
                        ?>
                        <article class="facility-item <?= $class ?>">
                            <div class="facility-image">
                                <img src="<?= $path_prefix ?><?= htmlspecialchars($img_news) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            </div>
                            <div class="facility-text">
                                <h2><?= htmlspecialchars($item['title']) ?></h2>
                                <span class="article-metadata">
                                    <i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($item['created_at'])) ?>
                                    <?php if (!empty($item['category'])): ?>
                                    <span class="metadata-separator">|</span>
                                    <i class="fas fa-tag"></i> <span class="article-category"><?= htmlspecialchars($item['category']) ?></span>
                                    <?php endif; ?>
                                </span>
                                <p><?= htmlspecialchars(substr($item['summary'], 0, 120)) ?>...</p>
                                <a href="menu-detail-berita/detail-berita.php?slug=<?= htmlspecialchars($item['slug']) ?>" class="btn">Lihat Detail</a>
                            </div>
                        </article>
                        <?php endforeach; ?>

                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-controls">
                            <?php 
                            $q = $_GET; 
                            unset($q['page'], $q['cal_month'], $q['cal_year']); 
                            $pages = build_pagination($page, $total_pages);
                            
                            // Tombol Previous
                            if($page > 1) {
                                $q['page'] = $page - 1;
                                echo '<a class="btn-page" href="?'.http_build_query($q).'">&laquo;</a>';
                            }

                            // Angka Halaman
                            foreach($pages as $p) {
                                if($p === '...') {
                                    echo '<span class="page-ellipsis">...</span>';
                                } else {
                                    $q['page'] = $p;
                                    $act = ($p == $page) ? 'active' : '';
                                    echo '<a class="btn-page '.$act.'" href="?'.http_build_query($q).'">'.$p.'</a>';
                                }
                            }

                            // Tombol Next
                            if($page < $total_pages) {
                                $q['page'] = $page + 1;
                                echo '<a class="btn-page" href="?'.http_build_query($q).'">&raquo;</a>';
                            }
                            ?>
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-results">
                            <h3>Tidak ada berita ditemukan.</h3>
                            <p>Coba reset filter pencarian atau cek kembali kata kunci Anda.</p>
                            <a href="berita.php" class="btn">Lihat Semua</a>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="sidebar">
                    <div class="widget widget-calendar">
                        <h3 class="widget-title">Kalender Kegiatan</h3>
                        <?php
                            $cMonth = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : date('n');
                            $cYear = isset($_GET['cal_year']) ? (int)$_GET['cal_year'] : date('Y');
                            $prevMonth = $cMonth - 1; $prevYear = $cYear; if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
                            $nextMonth = $cMonth + 1; $nextYear = $cYear; if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
                            
                            function getCalUrl($m, $y) { 
                                $p = $_GET; 
                                unset($p['page']); 
                                $p['cal_month'] = $m; 
                                $p['cal_year'] = $y; 
                                return '?' . http_build_query($p); 
                            }
                            
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $cMonth, $cYear);
                            $firstDay = date('N', strtotime("$cYear-$cMonth-01")); 
                            $mNames = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                            
                            echo "<div class='calendar-header'>\n";
                            echo "<a href='".getCalUrl($prevMonth, $prevYear)."' class='cal-nav-prev' id='calNavPrev'>&laquo;</a>\n";
                            echo "<span class='cal-current-month' id='calCurrentMonth'>{$mNames[$cMonth]} $cYear</span>\n";
                            echo "<a href='".getCalUrl($nextMonth, $nextYear)."' class='cal-nav-next' id='calNavNext'>&raquo;</a>\n";
                            echo "</div>\n";
                            echo "<table class='calendar-table' id='calendarTable'>\n";
                            echo "<thead><tr><th>Sn</th><th>Sl</th><th>Rb</th><th>Km</th><th>Jm</th><th>Sb</th><th>Mg</th></tr></thead>\n";
                            echo "<tbody><tr>\n";
                            
                            for ($i = 1; $i < $firstDay; $i++) echo "<td></td>\n";
                            
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $dStr = sprintf('%04d-%02d-%02d', $cYear, $cMonth, $day);
                                $cls = isset($events_by_date[$dStr]) ? 'event-day' : '';
                                $today_class = ($dStr == date('Y-m-d')) ? "today-day" : "";
                                
                                echo "<td class='$cls $today_class' data-date='$dStr'>$day</td>\n";
                                
                                if ((($day + $firstDay - 1) % 7) == 0) echo "</tr>\n<tr>\n";
                            }
                            
                            if ((($daysInMonth + $firstDay - 1) % 7) != 0) {
                                while(((($daysInMonth + $firstDay - 1) % 7) != 0) && (((($daysInMonth + $firstDay - 1) % 7) < 7))) {
                                    echo "<td></td>\n";
                                    $daysInMonth++;
                                }
                                echo "</tr>\n";
                            }
                            echo "</tbody></table>\n";
                        ?>
                    </div>
                    <div class="widget widget-news">
                        <h3 class="widget-title">Terkini</h3>
                        <ul>
                            <?php 
                            $side_stmt = $pdo->query("SELECT title, slug, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 4");
                            while($s = $side_stmt->fetch()): ?>
                            <li><a href="menu-detail-berita/detail-berita.php?slug=<?= $s['slug'] ?>"><h4><?= htmlspecialchars($s['title']) ?></h4><span class="date"><?= date('d M Y', strtotime($s['created_at'])) ?></span></a></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </aside>

            </div>
        </div>
    </main>
    
    <a href="#top" id="scrollTopBtn" class="scroll-top-btn">&uarr;</a>

    <?php 
        renderFloatingProfile(); 
        // Memanggil renderFooter dengan path prefix dan site config
        renderFooter($path_prefix, $site_config); 
    ?>

    <!-- Modal untuk menampilkan kegiatan berdasarkan tanggal -->
    <div id="modalBackdrop" class="modal-backdrop">
        <div class="modal">
            <button class="close-btn" id="modalCloseBtn">&times;</button>
            <h3 id="modalDateTitle">Kegiatan</h3>
            <div id="modalEventsList"></div> 
        </div>
    </div>

    <script>
        // ==========================================================
        // JAVASCRIPT UNTUK KALENDER KEGIATAN
        // ==========================================================
        
        // DATA EVENT DARI PHP
        const eventsByDate = <?= $events_json ?>;
        
        // Fungsi untuk menampilkan modal dengan kegiatan pada tanggal tertentu
        function showModal(date, events) {
            const modalBackdrop = document.getElementById('modalBackdrop');
            const modalEventsList = document.getElementById('modalEventsList');
            const modalDateTitle = document.getElementById('modalDateTitle');
            
            if (!modalBackdrop || !modalEventsList || !modalDateTitle) return;

            // Format tanggal untuk ditampilkan
            const dateObj = new Date(date);
            const dayNames = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", 
                               "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            
            const dayName = dayNames[dateObj.getDay()];
            const day = dateObj.getDate();
            const month = monthNames[dateObj.getMonth()];
            const year = dateObj.getFullYear();
            
            modalDateTitle.innerHTML = `Kegiatan pada <span id="modalDate">${dayName}, ${day} ${month} ${year}</span>`;
            modalEventsList.innerHTML = ''; 

            if (events && events.length > 0) {
                events.forEach(event => {
                    const item = document.createElement('div');
                    item.className = 'event-item';
                    item.innerHTML = `
                        <h4><a href="${event.link}" onclick="event.stopPropagation();">${event.title}</a></h4>
                        <p>${event.summary || 'Lihat detail berita/kegiatan.'}</p>
                    `;
                    modalEventsList.appendChild(item);
                });
            } else {
                modalEventsList.innerHTML = '<p class="text-center">Tidak ada kegiatan terjadwal pada tanggal ini.</p>';
            }

            modalBackdrop.style.display = 'flex';
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            const modalBackdrop = document.getElementById('modalBackdrop');
            if (modalBackdrop) {
                modalBackdrop.style.display = 'none';
            }
        }

        // Fungsi untuk setup event listener pada sel kalender
        function setupCalendarCellClickEvents() {
            const cells = document.querySelectorAll('.calendar-table td[data-date]');
            
            cells.forEach(cell => {
                const date = cell.getAttribute('data-date');
                
                cell.addEventListener('click', function() {
                    if (eventsByDate[date]) {
                        showModal(date, eventsByDate[date]);
                    } else {
                        // Jika tidak ada event, tetap tampilkan modal dengan pesan
                        showModal(date, []);
                    }
                });
                
                // Tambahkan cursor pointer untuk semua sel yang memiliki tanggal
                cell.style.cursor = 'pointer';
            });
        }

        // Inisialisasi ketika DOM siap
        document.addEventListener('DOMContentLoaded', () => {
            // Setup event listener untuk tombol close modal
            const closeBtn = document.getElementById('modalCloseBtn');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            // Setup backdrop click untuk close modal
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) {
                        closeModal();
                    }
                });
            }
            
            // Setup event listener untuk sel kalender
            setupCalendarCellClickEvents();
            
            // Tambahkan event listener untuk Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            
            // Setup navigasi kalender untuk refresh event listener setelah navigasi
            const prevBtn = document.getElementById('calNavPrev');
            const nextBtn = document.getElementById('calNavNext');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    // Setelah navigasi, setup ulang event listener
                    setTimeout(() => {
                        setupCalendarCellClickEvents();
                    }, 100);
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    // Setelah navigasi, setup ulang event listener
                    setTimeout(() => {
                        setupCalendarCellClickEvents();
                    }, 100);
                });
            }
            
            // Panggil fungsi scroll animation jika ada
            if (typeof handleScrollAnimation === 'function') {
                handleScrollAnimation();
            }
        });

        // Fungsi untuk animasi scroll (jika ada di file lain)
        function handleScrollAnimation() {
            const animatedElements = document.querySelectorAll(
                '.event-highlight, .search-filter-container, .section-separator, .facility-item, .pagination-controls, .widget, .no-results'
            );

            animatedElements.forEach((element) => {
                const rect = element.getBoundingClientRect();
                const triggerPoint = window.innerHeight * 0.9; 

                if (rect.top <= triggerPoint && rect.bottom >= 0) {
                    element.classList.add('animate-in');
                } 
            });
        }

        // Menghubungkan fungsi ke event scroll dan load
        window.addEventListener('scroll', handleScrollAnimation);
        window.addEventListener('load', handleScrollAnimation);
    </script>
    
    <script src="<?= $path_prefix ?>assets/js/navbar.js?v=<?= $cache_buster ?>"></script>
    <script src="<?= $path_prefix ?>assets/js/scrolltop.js?v=<?= $cache_buster ?>"></script>
</body>
</html>