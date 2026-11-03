-- =====================================================================
-- PATCH DATABASE
-- Melengkapi objek yang belum ada pada dump awal (DOKUMEN PBL/db_pbl.sql)
-- dan mengamankan kata sandi pengguna.
-- Aman dijalankan berulang kali (idempotent).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Materialized view aktivitas bulanan
--    Dipakai halaman Laporan Bulanan dan stored procedure generate_monthly_report
-- ---------------------------------------------------------------------
DROP MATERIALIZED VIEW IF EXISTS public.mv_monthly_activity;

CREATE MATERIALIZED VIEW public.mv_monthly_activity AS
SELECT date_trunc('month', created_at)::date AS month,
       count(*) FILTER (WHERE entity_type::text = 'news')  AS news_comments,
       count(*) FILTER (WHERE entity_type::text = 'media') AS media_comments,
       count(*) FILTER (WHERE entity_type::text NOT IN ('news', 'media') OR entity_type IS NULL) AS other_comments,
       count(*) AS total_comments
FROM public.comments
WHERE status::text = 'approved'
GROUP BY 1;

-- Unique index wajib agar REFRESH ... CONCURRENTLY bisa dipakai
CREATE UNIQUE INDEX idx_mv_monthly_activity_month ON public.mv_monthly_activity (month);

-- ---------------------------------------------------------------------
-- 2. Materialized view ringkasan pesan masuk
-- ---------------------------------------------------------------------
DROP MATERIALIZED VIEW IF EXISTS public.mv_feedback_summary;

CREATE MATERIALIZED VIEW public.mv_feedback_summary AS
SELECT date_trunc('month', created_at)::date AS month,
       count(*) AS total_feedback,
       count(*) FILTER (WHERE is_read = false) AS unread_feedback,
       count(*) FILTER (WHERE is_read = true)  AS read_feedback
FROM public.feedback
GROUP BY 1;

CREATE UNIQUE INDEX idx_mv_feedback_summary_month ON public.mv_feedback_summary (month);

-- ---------------------------------------------------------------------
-- 3. Unique index untuk mv_lab_dashboard_stats
--    View ini hanya berisi satu baris, sehingga index unik pada salah satu
--    kolomnya cukup untuk memenuhi syarat REFRESH ... CONCURRENTLY yang
--    dipakai stored procedure generate_monthly_report dan
--    refresh_lab_dashboard_stats().
-- ---------------------------------------------------------------------
DROP INDEX IF EXISTS public.idx_mv_dashboard_unique;
DROP INDEX IF EXISTS public.idx_mv_dashboard_refresh;

CREATE UNIQUE INDEX idx_mv_dashboard_unique ON public.mv_lab_dashboard_stats (total_news);

-- View lain juga diberi index unik pada kolom id supaya refresh-nya bisa
-- memakai CONCURRENTLY (tidak mengunci tabel saat dibaca).
CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_news_stats_unique ON public.mv_news_with_stats (id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_project_details_unique ON public.mv_project_details (id);

-- ---------------------------------------------------------------------
-- 4. Hapus baris profil lab yang kosong (duplikat)
-- ---------------------------------------------------------------------
DELETE FROM public.lab_profile
WHERE id NOT IN (SELECT min(id) FROM public.lab_profile);

-- ---------------------------------------------------------------------
-- 5. Kata sandi pengguna memakai hash bcrypt
--    admin@labpolinema.ac.id  / admin123
--    editor1@labpolinema.ac.id / editor123
--    anggota@labpolinema.ac.id / anggota123
-- ---------------------------------------------------------------------
UPDATE public.users
SET password = '$2y$10$urUmF37hHsH8zwxRiflyIu2S02iVUtq45./Gg16UbUmBjcr9xTlri'
WHERE email = 'admin@labpolinema.ac.id';

UPDATE public.users
SET password = '$2y$10$dGnAqRCHCUzepWkl0Wf6pOLl8gDjzs0TjQGUFMOnE/7z4IgRE8E/S'
WHERE email = 'editor1@labpolinema.ac.id';

-- ---------------------------------------------------------------------
-- 6. Tabel penyimpanan sesi
--    Dipakai config/session.php agar pengguna tidak ter-logout saat
--    request dilayani instance serverless yang berbeda.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.sessions (
    id varchar(128) PRIMARY KEY,
    data text NOT NULL,
    last_activity integer NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON public.sessions (last_activity);
