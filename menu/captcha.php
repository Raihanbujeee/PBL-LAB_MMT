<?php
/**
 * FILE: menu/captcha.php
 * Menghasilkan gambar CAPTCHA dari kode yang tersimpan di sesi.
 * Dibuat sebagai gambar agar kode tidak bisa dibaca langsung dari HTML.
 *
 * Kalau ekstensi GD tidak tersedia (misalnya di Vercel), gambar dibuat
 * dalam format SVG yang hanya butuh fungsi string.
 */
require_once __DIR__ . '/../config/auth.php';
startSecureSession();

$code = (string) ($_SESSION['captcha'] ?? 'ABC123');

$width  = 160;
$height = 44;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Selalu pakai kode yang sama supaya tidak ada karakter asing di SVG.
$code = preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?: 'ABC123';

if (!function_exists('imagecreatetruecolor')) {
    renderSvgCaptcha($code, $width, $height);
    exit;
}

$image = imagecreatetruecolor($width, $height);
imageantialias($image, true);

$background = imagecolorallocate($image, 244, 246, 248);
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Garis pengganggu
for ($i = 0; $i < 6; $i++) {
    $color = imagecolorallocate($image, rand(150, 210), rand(150, 210), rand(150, 210));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
}

// Titik pengganggu
for ($i = 0; $i < 120; $i++) {
    $color = imagecolorallocate($image, rand(140, 220), rand(140, 220), rand(140, 220));
    imagesetpixel($image, rand(0, $width), rand(0, $height), $color);
}

// Karakter CAPTCHA
$font = 5;
$charWidth = imagefontwidth($font);
$totalWidth = $charWidth * strlen($code);
$x = (int) (($width - $totalWidth) / 2);

for ($i = 0; $i < strlen($code); $i++) {
    $color = imagecolorallocate($image, rand(20, 70), rand(40, 90), rand(80, 140));
    $y = rand(6, 12);
    imagestring($image, $font, $x, $y, $code[$i], $color);
    $x += $charWidth + 4;
}

header('Content-Type: image/png');

imagepng($image);
imagedestroy($image);

/**
 * Versi SVG: dipakai saat ekstensi GD tidak ada.
 * Huruf digambar miring dengan posisi dan warna acak supaya tidak mudah dibaca mesin.
 */
function renderSvgCaptcha(string $code, int $width, int $height): void
{
    header('Content-Type: image/svg+xml; charset=utf-8');

    $parts = [];
    $parts[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '"'
        . ' viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Kode verifikasi">';
    $parts[] = '<rect width="100%" height="100%" fill="#f4f6f8"/>';

    // Garis pengganggu
    for ($i = 0; $i < 6; $i++) {
        $parts[] = sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="rgb(%d,%d,%d)" stroke-width="1" opacity="0.7"/>',
            rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height),
            rand(150, 210), rand(150, 210), rand(150, 210)
        );
    }

    // Titik pengganggu
    for ($i = 0; $i < 60; $i++) {
        $parts[] = sprintf(
            '<circle cx="%d" cy="%d" r="1" fill="rgb(%d,%d,%d)" opacity="0.6"/>',
            rand(0, $width), rand(0, $height),
            rand(140, 220), rand(140, 220), rand(140, 220)
        );
    }

    // Karakter CAPTCHA
    $charCount = strlen($code);
    $step = (int) (($width - 30) / max(1, $charCount));
    $x = (int) (($width - ($step * $charCount)) / 2) + (int) ($step / 2);

    for ($i = 0; $i < $charCount; $i++) {
        $parts[] = sprintf(
            '<text x="%d" y="%d" font-family="Verdana,DejaVu Sans,sans-serif" font-size="24"'
            . ' font-weight="bold" fill="rgb(%d,%d,%d)" transform="rotate(%d %d %d)">%s</text>',
            $x, rand(30, 34),
            rand(20, 70), rand(40, 90), rand(80, 140),
            rand(-14, 14), $x, 30,
            htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8')
        );
        $x += $step;
    }

    $parts[] = '</svg>';

    echo implode('', $parts);
}
