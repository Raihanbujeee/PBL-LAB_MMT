<?php
/**
 * FILE: config/database.php
 * Konfigurasi dan koneksi database PostgreSQL.
 *
 * Dipisah dari db.php supaya bisa dipakai lebih awal, misalnya oleh penangan
 * sesi, tanpa ikut memuat kumpulan helper.
 *
 * Konfigurasi diambil dari environment variable supaya kode yang sama bisa
 * dipakai di lokal (Laragon) maupun di hosting serverless (Vercel + Neon).
 */

require_once __DIR__ . '/env.php';

// Tampilkan error hanya saat pengembangan.
$appDebug = envBool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');

/**
 * Menyusun daftar opsi koneksi dari environment.
 * Mendukung dua gaya konfigurasi:
 * 1. DATABASE_URL  -> postgres://user:pass@host:5432/dbname?sslmode=require
 * 2. DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD (dipisah)
 */
if (!function_exists('buildDatabaseConfig')) {
    function buildDatabaseConfig(): array
    {
        $config = [
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '5432'),
            'dbname'   => env('DB_NAME', 'db_pbl'),
            'user'     => env('DB_USER', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'sslmode'  => env('DB_SSLMODE', ''),
        ];

        $url = env('DATABASE_URL');
        if ($url) {
            $parts = parse_url($url);
            if ($parts && isset($parts['host'])) {
                $config['host'] = $parts['host'];
                $config['port'] = $parts['port'] ?? $config['port'];
                $config['user'] = isset($parts['user']) ? rawurldecode($parts['user']) : $config['user'];
                $config['password'] = isset($parts['pass']) ? rawurldecode($parts['pass']) : $config['password'];
                $config['dbname'] = isset($parts['path']) ? ltrim($parts['path'], '/') : $config['dbname'];

                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $query);
                    if (!empty($query['sslmode'])) {
                        $config['sslmode'] = $query['sslmode'];
                    }
                }
            }
        }

        // Host selain localhost (mis. Neon, Supabase) hampir selalu butuh SSL.
        if ($config['sslmode'] === '') {
            $isLocal = in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true);
            $config['sslmode'] = $isLocal ? 'prefer' : 'require';
        }

        return $config;
    }
}

if (!function_exists('db')) {
    /**
     * Koneksi PDO tunggal per request.
     * Dipakai oleh db.php maupun oleh penangan sesi, jadi tidak ada
     * koneksi ganda walaupun fungsi ini dipanggil dari beberapa tempat.
     */
    function db(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = buildDatabaseConfig();

        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['sslmode']
            );

            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ]);
        } catch (PDOException $e) {
            error_log('[DB] Koneksi gagal: ' . $e->getMessage());

            http_response_code(500);
            if (envBool('APP_DEBUG', false)) {
                die('Koneksi ke database gagal: ' . htmlspecialchars($e->getMessage()));
            }
            die('Maaf, layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
        }

        return $pdo;
    }
}
