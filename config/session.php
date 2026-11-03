<?php
/**
 * FILE: config/session.php
 * Penyimpanan sesi di tabel PostgreSQL.
 *
 * Di hosting serverless (Vercel) berkas sesi di /tmp hanya berlaku untuk satu
 * instance dan bisa hilang kapan saja. Dengan menyimpan sesi di database,
 * pengguna tetap login walaupun request dilayani instance yang berbeda.
 */

if (!class_exists('DatabaseSessionHandler')) {
    class DatabaseSessionHandler implements SessionHandlerInterface
    {
        private PDO $pdo;
        private bool $tableChecked = false;

        public function __construct(PDO $pdo)
        {
            $this->pdo = $pdo;
        }

        public function open(string $path, string $name): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            try {
                $this->ensureTable();

                $stmt = $this->pdo->prepare(
                    'SELECT data FROM sessions WHERE id = ? AND last_activity >= ?'
                );
                $stmt->execute([$id, time() - $this->maxLifetime()]);
                $data = $stmt->fetchColumn();

                return $data === false ? '' : (string) $data;
            } catch (Throwable $e) {
                error_log('[session] gagal membaca: ' . $e->getMessage());
                return '';
            }
        }

        public function write(string $id, string $data): bool
        {
            try {
                $this->ensureTable();

                $stmt = $this->pdo->prepare(
                    'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
                     ON CONFLICT (id) DO UPDATE
                     SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity'
                );
                $stmt->execute([$id, $data, time()]);

                return true;
            } catch (Throwable $e) {
                error_log('[session] gagal menyimpan: ' . $e->getMessage());
                return false;
            }
        }

        public function destroy(string $id): bool
        {
            try {
                $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
            } catch (Throwable $e) {
                error_log('[session] gagal menghapus: ' . $e->getMessage());
            }

            return true;
        }

        public function gc(int $max_lifetime): int|false
        {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < ?');
                $stmt->execute([time() - $max_lifetime]);
                return $stmt->rowCount();
            } catch (Throwable $e) {
                return false;
            }
        }

        private function maxLifetime(): int
        {
            $lifetime = (int) ini_get('session.gc_maxlifetime');
            return $lifetime > 0 ? $lifetime : 1440;
        }

        /**
         * Jaga-jaga kalau tabel sessions belum ikut terpasang saat impor skema.
         */
        private function ensureTable(): void
        {
            if ($this->tableChecked) {
                return;
            }

            $this->tableChecked = true;

            $exists = $this->pdo->query("SELECT to_regclass('public.sessions')")->fetchColumn();
            if ($exists) {
                return;
            }

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS sessions (
                    id varchar(128) PRIMARY KEY,
                    data text NOT NULL,
                    last_activity integer NOT NULL
                )'
            );
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions (last_activity)');
        }
    }
}
