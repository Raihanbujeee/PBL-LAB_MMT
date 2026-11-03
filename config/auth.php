<?php
/**
 * FILE: config/auth.php
 * Sesi, otorisasi, dan proteksi CSRF.
 */

if (!function_exists('startSecureSession')) {
    function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        require_once __DIR__ . '/database.php';
        require_once __DIR__ . '/session.php';

        // Sesi disimpan di database supaya tidak hilang saat instance serverless
        // didaur ulang atau request dilayani instance lain.
        session_set_save_handler(new DatabaseSessionHandler(db()), true);

        // Sesi dibiarkan berlaku 2 jam sejak aktivitas terakhir.
        ini_set('session.gc_maxlifetime', '7200');

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('labmmt_session');
        session_start();
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        startSecureSession();
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        startSecureSession();
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true);
    }
}

if (!function_exists('wantsJson')) {
    function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}

if (!function_exists('denyAccess')) {
    function denyAccess(string $message = 'Akses ditolak.'): void
    {
        http_response_code(403);

        if (wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
            . '<title>Akses ditolak</title></head><body style="font-family:sans-serif;text-align:center;padding:60px;">'
            . '<h1>403</h1><p>' . htmlspecialchars($message) . '</p>'
            . '<p><a href="/menu/login.php">Masuk ke akun</a></p></body></html>';
        exit;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (isLoggedIn()) {
            return;
        }

        if (wantsJson()) {
            denyAccess('Anda harus masuk terlebih dahulu.');
        }

        // Simpan halaman tujuan supaya bisa kembali setelah login.
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . loginUrl());
        exit;
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin($pdo = null): void
    {
        requireLogin();

        $role = $_SESSION['user_role'] ?? '';

        // Ambil role terbaru dari database agar perubahan hak akses langsung berlaku.
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                $role = $stmt->fetchColumn() ?: $role;
                $_SESSION['user_role'] = $role;
            } catch (PDOException $e) {
                // Abaikan, pakai role dari sesi
            }
        }

        if (!in_array($role, ['admin', 'editor'], true)) {
            denyAccess('Halaman ini khusus admin dan editor.');
        }
    }
}

if (!function_exists('loginUrl')) {
    /**
     * URL halaman login relatif terhadap file yang sedang dijalankan.
     * Dipakai karena aplikasi ini tidak punya router terpusat.
     */
    function loginUrl(): string
    {
        $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Kalau URI diakhiri "/", yang diminta adalah folder, bukan berkas.
        $endsWithSlash = substr($urlPath, -1) === '/';
        $path = trim($urlPath, '/');

        $segments = $path === '' ? 0 : substr_count($path, '/') + 1;
        $depth = $endsWithSlash ? $segments : max(0, $segments - 1);

        return str_repeat('../', $depth) . 'menu/login.php';
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        startSecureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
    }
}

if (!function_exists('csrfValid')) {
    function csrfValid(?string $token = null): bool
    {
        startSecureSession();
        $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return !empty($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('requireCsrf')) {
    function requireCsrf(): void
    {
        if (csrfValid()) {
            return;
        }

        http_response_code(419);
        if (wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sesi formulir kedaluwarsa. Muat ulang halaman.']);
            exit;
        }

        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
            . '<title>Sesi kedaluwarsa</title></head><body style="font-family:sans-serif;text-align:center;padding:60px;">'
            . '<h1>Sesi formulir kedaluwarsa</h1>'
            . '<p>Muat ulang halaman lalu kirim ulang formulir.</p>'
            . '<p><a href="javascript:history.back()">Kembali</a></p></body></html>';
        exit;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML singkat untuk dipakai di template.
     */
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
