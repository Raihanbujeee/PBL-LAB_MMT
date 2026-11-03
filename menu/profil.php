<?php
require_once __DIR__ . '/../config/auth.php';
startSecureSession();
require_once '../config/db.php';
require_once '../config/settings.php';

require_once 'components/navbar.php';
require_once 'components/footer.php';
include 'components/floating_profile.php'; 

$path_prefix = "../";
$cache_buster = time();

// Fetch lab profile
$profile = $pdo->query("SELECT * FROM lab_profile LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Fetch members with social media columns (including future ones)
$members = $pdo->query("
    SELECT 
        id, 
        name, 
        role, 
        avatar_url, 
        linkedin_url, 
        scholar_url, 
        youtube, 
        facebook, 
        instagram,
        tags
    FROM members 
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Lab - Laboratorium Mobile and Multimedia Tech POLINEMA</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="components/navbar.css?v=<?= $cache_buster ?>">
    <link rel="stylesheet" href="<?= $path_prefix ?>assets/css/style.css?v=<?= $cache_buster ?>">
    <link rel="stylesheet" href="assets/profil/css/style-profil.css?v=<?= $cache_buster ?>">
    
    <style>
        /* ===== STYLING ASLI PROFIL.PHP ===== */
        /* Background untuk seluruh halaman dengan wallpaper.jpg */
        body {
            background: url('<?= $path_prefix ?>assets/images/wallpaper.jpg') center center/cover fixed no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        /* Efek transparansi halus untuk area konten utama */
        .profile-section {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: relative;
            padding: 40px 0;
        }
        
        /* Section dengan background light */
        .profile-section.bg-light {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Container untuk memastikan konten tetap readable */
        .container {
            position: relative;
            z-index: 1;
        }
        
        /* Hero section styling */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)),
                        url('<?= $path_prefix ?><?= htmlspecialchars($site_config['about_hero_image'] ?? 'assets/images/hero.jpg') ?>') center center/cover no-repeat;
            color: white;
            padding: 100px 0 60px;
            text-align: center;
        }
        
        .hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        /* Visi Misi Cards */
        .visi-card, .misi-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        /* Sejarah text area */
        .sejarah-text {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 25px;
            line-height: 1.8;
        }
        
        /* Struktur placeholder */
        .struktur-placeholder {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 30px;
            text-align: center;
        }
        
        .struktur-placeholder img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        /* Team cards - DIUBAH LEBIH KECIL */
        .team-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 15px;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .team-card:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-color: rgba(254, 121, 39, 0.3);
        }
        
        /* Team grid - DIUBAH UNTUK KARTU LEBIH KECIL */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            justify-items: center;
        }
        
        /* Team photo - DIUBAH LEBIH KECIL */
        .team-photo-wrapper {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(254, 121, 39, 0.1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        
        .team-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .team-card:hover .team-photo {
            transform: scale(1.05);
        }
        
        /* Team info - DIUBAH LEBIH KOMPAK */
        .team-info {
            text-align: center;
        }
        
        .team-info h4 {
            font-size: 1.1rem;
            margin: 0 0 5px;
            color: #333;
            font-weight: 600;
        }
        
        .team-role {
            display: block;
            font-size: 0.85rem;
            color: #FE7927;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        /* Team tags - DIUBAH LEBIH KECIL */
        .team-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            margin: 10px 0;
        }
        
        .team-tags .tag {
            background: rgba(254, 121, 39, 0.08);
            color: #FE7927;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            border: 1px solid rgba(254, 121, 39, 0.15);
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .team-card:hover .team-tags .tag {
            background: rgba(254, 121, 39, 0.12);
            border-color: rgba(254, 121, 39, 0.25);
        }
        
        /* Social media links - STYLING BARU YANG MINIMALIS DAN NETRAL */
        .social-media-links {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .social-media-links a {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #666;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            position: relative;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .social-media-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.1);
            background: white;
            color: #FE7927;
            border-color: rgba(254, 121, 39, 0.3);
        }
        
        /* Warna khusus saat hover untuk masing-masing platform (sangat subtle) */
        .social-media-links a.linkedin:hover {
            color: #0077B5;
            border-color: rgba(0, 119, 181, 0.3);
        }
        
        .social-media-links a.scholar:hover {
            color: #4285F4;
            border-color: rgba(66, 133, 244, 0.3);
        }
        
        .social-media-links a.instagram:hover {
            color: #E1306C;
            border-color: rgba(225, 48, 108, 0.3);
        }
        
        .social-media-links a.youtube:hover {
            color: #FF0000;
            border-color: rgba(255, 0, 0, 0.3);
        }
        
        .social-media-links a.facebook:hover {
            color: #1877F2;
            border-color: rgba(24, 119, 242, 0.3);
        }
        
        /* Tooltip untuk social media */
        .social-media-links a::after {
            content: attr(title);
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .social-media-links a:hover::after {
            opacity: 1;
            visibility: visible;
            bottom: -30px;
        }
        
        /* Section titles - DIUBAH MENJADI PUTIH */
        .section-title {
            color: #ffffff;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-size: 2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .team-card {
                max-width: 250px;
                padding: 12px;
            }
            
            .team-photo-wrapper {
                width: 100px;
                height: 100px;
            }
            
            .social-media-links a {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .team-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin: 0 auto;
            }
        }
        
        /* ===== FIX UNTUK FOOTER ===== */
        .site-footer {
            background: linear-gradient(135deg, #E65100 0%, #BF360C 100%) !important;
            color: #F8F8F8;
            padding: 50px 0;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
            margin-top: 60px;
        }
        
        .site-footer::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: #1565C0;
            opacity: 0.1;
            border-radius: 50%;
            z-index: 1;
        }
        
        .site-footer::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 150px;
            height: 150px;
            background: #FFB300;
            opacity: 0.1;
            border-radius: 50%;
            z-index: 1;
        }
        
        .footer-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }
        
        .footer-col {
            width: calc(33.333% - 20px);
            min-width: 250px;
        }
        
        @media (max-width: 992px) {
            .footer-col { width: calc(50% - 15px); }
        }
        
        @media (max-width: 576px) {
            .footer-col { width: 100%; }
        }
        
        .footer-col h4 {
            color: #FFB300;
            border-bottom: 3px solid #FFD54F;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-size: 1.25rem;
            font-weight: bold;
            position: relative;
        }
        
        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 30px;
            height: 3px;
            background: #1565C0;
        }
        
        .footer-col p, .policy-links a, .footer-col li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-col a:hover {
            color: #FFD54F;
            transform: translateX(5px);
        }
        
        .footer-col li {
            list-style: none;
            padding: 6px 0;
            border-left: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .footer-col li:hover {
            border-left: 2px solid #1565C0;
            padding-left: 8px;
        }
        
        .footer-col ul {
            padding-left: 0;
        }
        
        .footer-col p i {
            color: #FFD54F;
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        
        /* SOCIAL LINKS - INI YANG PENTING! */
        .social-links-footer {
            margin-top: 20px;
            display: flex;
            gap: 8px;
        }
        
        .social-links-footer a {
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 38px !important;
            height: 38px !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid #FFB300 !important;
            border-radius: 8px !important;
            color: #FFB300 !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
            text-decoration: none !important;
        }
        
        .social-links-footer a:hover {
            background: #FFB300 !important;
            color: #E65100 !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 5px 15px rgba(255, 179, 0, 0.3) !important;
        }
        
        .social-links-footer a:nth-child(2):hover { /* Twitter/X */
            background: #000000 !important;
            border-color: #000000 !important;
            color: white !important;
        }
        
        .footer-copyright {
            border-top: 1px solid rgba(255, 179, 0, 0.3);
            padding-top: 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #FFD54F;
            position: relative;
            z-index: 2;
        }
        
        /* Scroll to top button */
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #FE7927;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 20px;
            box-shadow: 0 3px 10px rgba(254, 121, 39, 0.3);
            z-index: 1000;
            transition: all 0.3s;
            opacity: 0;
            visibility: hidden;
        }
        
        .scroll-top-btn.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top-btn:hover {
            background: #e56a1f;
            transform: translateY(-3px);
        }
    </style>
</head>
<body id="top">
    <?php renderNavbar('profil', $path_prefix, $site_config); ?>
    
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-breadcrumb">
                    <a href="<?= $path_prefix ?>index.php">Beranda</a> <i class="fas fa-chevron-right"></i> 
                    <span>Profil Laboratorium</span>
                </div>
                <h1><?= htmlspecialchars($site_config['about_title'] ?? 'Profil Laboratorium') ?></h1>
            </div>
        </section>

        <section class="profile-section" id="visi-misi">
            <div class="container">
                <div class="visi-misi-grid">
                    <div class="visi-card">
                        <h3><i class="fas fa-eye"></i> Visi</h3>
                        <p><?php echo nl2br(htmlspecialchars($profile['visi'] ?? 'Visi belum diatur.')); ?></p>
                    </div>
                    <div class="misi-card">
                        <h3><i class="fas fa-bullseye"></i> Misi</h3>
                        <?php 
                        $misi_content = $profile['misi'] ?? '';
                        $misi_items = explode("\n", $misi_content);
                        
                        if (!empty(trim($misi_content))) {
                            echo '<ul>';
                            foreach ($misi_items as $item) {
                                if (trim($item) !== '') {
                                    echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                                }
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>Misi belum diatur.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="profile-section bg-light" id="sejarah">
            <div class="container">
                <h2 class="section-title">Sejarah Singkat</h2>
                <div class="sejarah-text">
                    <?php echo nl2br(htmlspecialchars($profile['sejarah'] ?? 'Sejarah belum diatur.')); ?>
                </div>
            </div>
        </section>
        
        <section class="profile-section" id="struktur">
            <div class="container">
                <h2 class="section-title">Struktur Organisasi</h2>
                <div class="struktur-placeholder">
                    <?php 
                    // LOGIKA PERBAIKAN: 
                    // Cek apakah ada file gambar di database ($profile['struktur_org_path'])
                    // Jika ada, pakai itu. Jika tidak, pakai placeholder bawaan.
                    $struktur_img = !empty($profile['struktur_org_path']) 
                        ? $path_prefix . $profile['struktur_org_path'] 
                        : $path_prefix . 'assets/images/struktur-org-placeholder.png';
                    ?>
                    <img src="<?= htmlspecialchars($struktur_img) ?>" alt="Bagan Struktur Organisasi" 
                         style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
            </div>
        </section>
        
        <section class="profile-section bg-light" id="tim">
            <div class="container">
                <h2 class="section-title">Tim Kami</h2>
                <div class="team-grid">
                    <?php 
                    if (!empty($members)):
                        foreach ($members as $member): 
                            // LOGIKA PERBAIKAN PATH GAMBAR:
                            // 1. Ambil path dari database
                            $db_path = $member['avatar_url'];
                            
                            // 2. Cek apakah path valid
                            if (!empty($db_path)) {
                                // Bersihkan tanda "../" kalau sudah tersimpan di database biar tidak dobel
                                $clean_path = str_replace('../', '', $db_path);
                                // Tambahkan prefix path yang benar (../assets/...)
                                $final_avatar_path = $path_prefix . $clean_path;
                            } else {
                                // Default jika kosong
                                $final_avatar_path = $path_prefix . "assets/images/placeholder-team.jpg";
                            }
                            
                            // Filter hanya social media yang ada isinya
                            $social_media = [];
                            if (!empty($member['linkedin_url'])) {
                                $social_media[] = [
                                    'url' => $member['linkedin_url'],
                                    'icon' => 'fab fa-linkedin-in',
                                    'class' => 'linkedin',
                                    'title' => 'LinkedIn'
                                ];
                            }
                            if (!empty($member['scholar_url'])) {
                                $social_media[] = [
                                    'url' => $member['scholar_url'],
                                    'icon' => 'fas fa-graduation-cap',
                                    'class' => 'scholar',
                                    'title' => 'Google Scholar'
                                ];
                            }
                            if (!empty($member['instagram'])) {
                                $social_media[] = [
                                    'url' => $member['instagram'],
                                    'icon' => 'fab fa-instagram',
                                    'class' => 'instagram',
                                    'title' => 'Instagram'
                                ];
                            }
                            if (!empty($member['youtube'])) {
                                $social_media[] = [
                                    'url' => $member['youtube'],
                                    'icon' => 'fab fa-youtube',
                                    'class' => 'youtube',
                                    'title' => 'YouTube'
                                ];
                            }
                            if (!empty($member['facebook'])) {
                                $social_media[] = [
                                    'url' => $member['facebook'],
                                    'icon' => 'fab fa-facebook-f',
                                    'class' => 'facebook',
                                    'title' => 'Facebook'
                                ];
                            }
                        ?>
                        <div class="team-card">
                            <div class="team-photo-wrapper">
                                <img src="<?php echo htmlspecialchars($final_avatar_path); ?>" 
                                     alt="Foto <?php echo htmlspecialchars($member['name']); ?>" 
                                     class="team-photo"
                                     onerror="this.src='<?= $path_prefix ?>assets/images/placeholder-team.jpg'">
                            </div>
                            <div class="team-info">
                                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                                <span class="team-role"><?php echo htmlspecialchars($member['role']); ?></span>
                                
                                <?php if (!empty($member['tags'])): ?>
                                <div class="team-tags">
                                    <?php 
                                    $tags = explode(',', $member['tags']);
                                    foreach (array_slice($tags, 0, 3) as $tag): // Tampilkan maksimal 3 tag
                                        $trimmed_tag = trim($tag);
                                        if (!empty($trimmed_tag)):
                                    ?>
                                    <span class="tag"><?php echo htmlspecialchars($trimmed_tag); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (count($tags) > 3): ?>
                                    <span class="tag">+<?php echo count($tags) - 3; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($social_media)): ?>
                                <div class="social-media-links">
                                    <?php foreach ($social_media as $social): ?>
                                    <a href="<?php echo htmlspecialchars($social['url']); ?>" 
                                       target="_blank" 
                                       class="<?php echo htmlspecialchars($social['class']); ?>"
                                       title="<?php echo htmlspecialchars($social['title']); ?>"
                                       aria-label="<?php echo htmlspecialchars($social['title']); ?>">
                                        <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach;
                    else:
                        echo "<div style='grid-column: 1/-1; text-align: center; background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); padding: 30px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.3);'>
                                <p>Data anggota tim belum tersedia.</p>
                              </div>";
                    endif;
                    ?>
                </div>
            </div>
        </section>
    </main>

    <?php 
    renderFloatingProfile(); 
    renderFooter($path_prefix, $site_config); 
    ?>


    <script src="<?= $path_prefix ?>assets/js/navbar.js?v=<?= $cache_buster ?>"></script>
    <script src="<?= $path_prefix ?>assets/js/scrolltop.js?v=<?= $cache_buster ?>"></script>
    <script src="assets/profil/js/script-profil.js?v=<?= $cache_buster ?>"></script>

    <script>
        // Script untuk handling error gambar
        document.addEventListener('DOMContentLoaded', function() {
            // Fallback untuk gambar yang error
            const teamPhotos = document.querySelectorAll('.team-photo');
            teamPhotos.forEach(photo => {
                photo.onerror = function() {
                    this.src = '<?= $path_prefix ?>assets/images/placeholder-team.jpg';
                };
            });
            
            // Smooth scroll untuk anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href !== '#') {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            window.scrollTo({
                                top: target.offsetTop - 80,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
            
            // Scroll to top button visibility
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
            
            // Hover effect untuk kartu tim
            const teamCards = document.querySelectorAll('.team-card');
            teamCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
            });
        });
    </script>
</body>
</html>