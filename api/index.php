<?php
/**
 * FILE: api/index.php
 * Front controller untuk deployment serverless (Vercel).
 *
 * Semua permintaan non-asset diarahkan ke file ini lewat vercel.json,
 * lalu diteruskan ke file PHP yang sesuai di root project.
 * Dengan cara ini kode sumber di folder config/ tidak pernah bisa diunduh.
 */

$root = dirname(__DIR__);

// ---------------------------------------------------------------
// 1. Tentukan path yang diminta
// ---------------------------------------------------------------
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

// Saat dijalankan langsung sebagai /api/index.php, anggap sebagai halaman utama
if ($path === '/api' || $path === '/api/' || $path === '/api/index.php') {
    $path = '/';
}

$path = '/' . ltrim($path, '/');

// ---------------------------------------------------------------
// 2. Tolak path berbahaya / file sensitif
// ---------------------------------------------------------------
$blockedPatterns = [
    '#(^|/)\.#',                       // file/folder tersembunyi (.env, .git)
    '#^/config(/|$)#i',
    '#^/database(/|$)#i',
    '#^/dokumen pbl(/|$)#i',
    '#^/api(/|$)#i',
    '#\.(sql|md|ini|log|lock|example|yml|yaml|json|txt|sh|bat)$#i',
    '#^(/(menu|admin))?/components/.*\.php$#i',   // potongan layout, bukan halaman
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $path)) {
        http_response_code(404);
        exit('404 Not Found');
    }
}

// ---------------------------------------------------------------
// 3. Asset statis (hanya dipakai saat pengembangan lokal,
//    di Vercel aset sudah dilayani langsung oleh route statis)
// ---------------------------------------------------------------
$staticExtensions = [
    'css'  => 'text/css; charset=utf-8',
    'js'   => 'application/javascript; charset=utf-8',
    'mjs'  => 'application/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'  => 'font/ttf',
];

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if ($extension !== '' && isset($staticExtensions[$extension])) {
    $file = realpath($root . $path);

    if ($file && strpos($file, $root) === 0 && is_file($file)) {
        header('Content-Type: ' . $staticExtensions[$extension]);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }

    http_response_code(404);
    exit('404 Not Found');
}

// ---------------------------------------------------------------
// 4. Tentukan file PHP tujuan
// ---------------------------------------------------------------
if ($path === '/' || $path === '/index.php') {
    $target = $root . '/beranda.php';
} elseif ($path === '/admin' || $path === '/admin/') {
    // Supaya alamat /admin bisa dibuka langsung seperti yang tertulis di README.
    $target = $root . '/admin/index.php';
} else {
    if (substr($path, -1) === '/') {
        $path .= 'index.php';
    }
    if (substr($path, -4) !== '.php') {
        $path .= '.php';
    }
    $target = $root . $path;
}

$realTarget = realpath($target);
if (!$realTarget || strpos($realTarget, $root) !== 0 || !is_file($realTarget)) {
    http_response_code(404);
    require $root . '/errors/404.php';
    exit;
}

// ---------------------------------------------------------------
// 5. Jalankan file PHP dengan konteks seperti web server biasa
// ---------------------------------------------------------------
$scriptName = '/' . ltrim(str_replace('\\', '/', substr($realTarget, strlen($root))), '/');

$_SERVER['SCRIPT_FILENAME'] = $realTarget;
$_SERVER['SCRIPT_NAME']     = $scriptName;
$_SERVER['PHP_SELF']        = $scriptName;
$_SERVER['DOCUMENT_ROOT']   = $root;

// Banyak halaman memakai path relatif (mis. include 'components/header.php'),
// jadi working directory harus mengikuti folder file yang dijalankan.
chdir(dirname($realTarget));

require $realTarget;
