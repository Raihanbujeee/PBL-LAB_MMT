--
-- PostgreSQL database dump
--

-- Dumped from database version 15.14
-- Dumped by pg_dump version 17.2

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.project_members DROP CONSTRAINT IF EXISTS project_members_project_id_fkey;
ALTER TABLE IF EXISTS ONLY public.project_members DROP CONSTRAINT IF EXISTS project_members_member_id_fkey;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS fk_projects_user;
ALTER TABLE IF EXISTS ONLY public.project_tags DROP CONSTRAINT IF EXISTS fk_projects_tags_tag;
ALTER TABLE IF EXISTS ONLY public.project_tags DROP CONSTRAINT IF EXISTS fk_projects_tags_project;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS fk_projects_category;
ALTER TABLE IF EXISTS ONLY public.news DROP CONSTRAINT IF EXISTS fk_news_user;
ALTER TABLE IF EXISTS ONLY public.news_tags DROP CONSTRAINT IF EXISTS fk_news_tags_tag;
ALTER TABLE IF EXISTS ONLY public.news_tags DROP CONSTRAINT IF EXISTS fk_news_tags_news;
ALTER TABLE IF EXISTS ONLY public.comments DROP CONSTRAINT IF EXISTS fk_comments_user;
DROP TRIGGER IF EXISTS update_users_updated_at ON public.users;
DROP TRIGGER IF EXISTS update_projects_updated_at ON public.projects;
DROP TRIGGER IF EXISTS update_news_updated_at ON public.news;
DROP TRIGGER IF EXISTS update_comments_updated_at ON public.comments;
DROP INDEX IF EXISTS public.idx_sessions_last_activity;
DROP INDEX IF EXISTS public.idx_projects_created_at;
DROP INDEX IF EXISTS public.idx_news_tags_tag;
DROP INDEX IF EXISTS public.idx_news_created_at;
DROP INDEX IF EXISTS public.idx_mv_project_details_year;
DROP INDEX IF EXISTS public.idx_mv_project_details_unique;
DROP INDEX IF EXISTS public.idx_mv_project_details_rating;
DROP INDEX IF EXISTS public.idx_mv_project_details_id;
DROP INDEX IF EXISTS public.idx_mv_news_stats_unique;
DROP INDEX IF EXISTS public.idx_mv_news_stats_created;
DROP INDEX IF EXISTS public.idx_mv_news_stats_category;
DROP INDEX IF EXISTS public.idx_mv_monthly_activity_month;
DROP INDEX IF EXISTS public.idx_mv_feedback_summary_month;
DROP INDEX IF EXISTS public.idx_mv_dashboard_unique;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_key;
ALTER TABLE IF EXISTS ONLY public.tags DROP CONSTRAINT IF EXISTS tags_slug_key;
ALTER TABLE IF EXISTS ONLY public.tags DROP CONSTRAINT IF EXISTS tags_pkey;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS projects_slug_key;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS projects_pkey;
ALTER TABLE IF EXISTS ONLY public.project_tags DROP CONSTRAINT IF EXISTS project_tags_pkey;
ALTER TABLE IF EXISTS ONLY public.project_members DROP CONSTRAINT IF EXISTS project_members_pkey;
ALTER TABLE IF EXISTS ONLY public.news_tags DROP CONSTRAINT IF EXISTS news_tags_pkey;
ALTER TABLE IF EXISTS ONLY public.news_tags DROP CONSTRAINT IF EXISTS news_tags_news_id_tag_id_key;
ALTER TABLE IF EXISTS ONLY public.news DROP CONSTRAINT IF EXISTS news_slug_key;
ALTER TABLE IF EXISTS ONLY public.news DROP CONSTRAINT IF EXISTS news_pkey;
ALTER TABLE IF EXISTS ONLY public.members DROP CONSTRAINT IF EXISTS members_pkey;
ALTER TABLE IF EXISTS ONLY public.media_assets DROP CONSTRAINT IF EXISTS media_assets_pkey;
ALTER TABLE IF EXISTS ONLY public.lab_profile DROP CONSTRAINT IF EXISTS lab_profile_pkey;
ALTER TABLE IF EXISTS ONLY public.feedback DROP CONSTRAINT IF EXISTS feedback_pkey;
ALTER TABLE IF EXISTS ONLY public.comments DROP CONSTRAINT IF EXISTS comments_pkey;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_slug_key;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_pkey;
ALTER TABLE IF EXISTS ONLY public.activity_logs DROP CONSTRAINT IF EXISTS activity_logs_pkey;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.tags ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.projects ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.project_members ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.news_tags ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.news ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.members ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.media_assets ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.lab_profile ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.feedback ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.comments ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.categories ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.activity_logs ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP SEQUENCE IF EXISTS public.tags_id_seq;
DROP TABLE IF EXISTS public.tags;
DROP TABLE IF EXISTS public.sessions;
DROP SEQUENCE IF EXISTS public.projects_id_seq;
DROP TABLE IF EXISTS public.project_tags;
DROP SEQUENCE IF EXISTS public.project_members_id_seq;
DROP SEQUENCE IF EXISTS public.news_tags_id_seq;
DROP SEQUENCE IF EXISTS public.news_id_seq;
DROP MATERIALIZED VIEW IF EXISTS public.mv_project_details;
DROP TABLE IF EXISTS public.project_members;
DROP MATERIALIZED VIEW IF EXISTS public.mv_news_with_stats;
DROP TABLE IF EXISTS public.news_tags;
DROP MATERIALIZED VIEW IF EXISTS public.mv_monthly_activity;
DROP MATERIALIZED VIEW IF EXISTS public.mv_lab_dashboard_stats;
DROP TABLE IF EXISTS public.users;
DROP TABLE IF EXISTS public.projects;
DROP TABLE IF EXISTS public.news;
DROP MATERIALIZED VIEW IF EXISTS public.mv_feedback_summary;
DROP SEQUENCE IF EXISTS public.members_id_seq;
DROP TABLE IF EXISTS public.members;
DROP SEQUENCE IF EXISTS public.media_assets_id_seq;
DROP TABLE IF EXISTS public.media_assets;
DROP SEQUENCE IF EXISTS public.lab_profile_id_seq;
DROP TABLE IF EXISTS public.lab_profile;
DROP SEQUENCE IF EXISTS public.feedback_id_seq;
DROP TABLE IF EXISTS public.feedback;
DROP SEQUENCE IF EXISTS public.comments_id_seq;
DROP TABLE IF EXISTS public.comments;
DROP SEQUENCE IF EXISTS public.categories_id_seq;
DROP TABLE IF EXISTS public.categories;
DROP SEQUENCE IF EXISTS public.activity_logs_id_seq;
DROP TABLE IF EXISTS public.activity_logs;
DROP FUNCTION IF EXISTS public.update_updated_at_column();
DROP PROCEDURE IF EXISTS public.update_entity_rating(IN p_entity_type character varying, IN p_entity_id integer);
DROP FUNCTION IF EXISTS public.refresh_lab_dashboard_stats();
DROP FUNCTION IF EXISTS public.get_lab_stats();
DROP PROCEDURE IF EXISTS public.generate_monthly_report(IN p_month date);
DROP PROCEDURE IF EXISTS public.cleanup_old_data(IN p_days_old integer);
DROP PROCEDURE IF EXISTS public.backup_lab_data(IN p_backup_type character varying);
DROP PROCEDURE IF EXISTS public.add_comment(IN p_entity_type character varying, IN p_entity_id integer, IN p_author_name character varying, IN p_author_email character varying, IN p_rating integer, IN p_content text, IN p_user_id integer);
--
-- Name: add_comment(character varying, integer, character varying, character varying, integer, text, integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.add_comment(IN p_entity_type character varying, IN p_entity_id integer, IN p_author_name character varying, IN p_author_email character varying, IN p_rating integer, IN p_content text, IN p_user_id integer DEFAULT NULL::integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_status VARCHAR(20);
    v_entity_exists BOOLEAN;
BEGIN
    -- Validasi rating
    IF p_rating IS NOT NULL AND (p_rating < 1 OR p_rating > 5) THEN
        RAISE EXCEPTION 'Rating must be between 1 and 5';
    END IF;
    
    -- Cek apakah entity exists
    IF p_entity_type = 'news' THEN
        SELECT EXISTS(SELECT 1 FROM news WHERE id = p_entity_id) INTO v_entity_exists;
    ELSIF p_entity_type = 'project' THEN
        SELECT EXISTS(SELECT 1 FROM projects WHERE id = p_entity_id) INTO v_entity_exists;
    ELSIF p_entity_type = 'media' THEN
        SELECT EXISTS(SELECT 1 FROM media_assets WHERE id = p_entity_id) INTO v_entity_exists;
    ELSE
        RAISE EXCEPTION 'Invalid entity type';
    END IF;
    
    IF NOT v_entity_exists THEN
        RAISE EXCEPTION 'Entity not found';
    END IF;
    
    -- Tentukan status (auto-approve jika ada user_id, pending jika tidak)
    IF p_user_id IS NOT NULL THEN
        v_status := 'approved';
    ELSE
        v_status := 'pending';
    END IF;
    
    -- Insert komentar
    INSERT INTO comments (
        entity_type, entity_id, author_name, author_email, 
        rating, content, status, user_id, created_at, updated_at
    ) VALUES (
        p_entity_type, p_entity_id, p_author_name, p_author_email,
        p_rating, p_content, v_status, p_user_id, NOW(), NOW()
    );
    
    -- Update rating entity
    IF p_rating IS NOT NULL AND v_status = 'approved' THEN
        CALL update_entity_rating(p_entity_type, p_entity_id);
    END IF;
    
    COMMIT;
END;
$$;


--
-- Name: backup_lab_data(character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.backup_lab_data(IN p_backup_type character varying DEFAULT 'full'::character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_backup_data JSONB;
    v_timestamp TEXT;
BEGIN
    v_timestamp := TO_CHAR(NOW(), 'YYYY-MM-DD_HH24-MI-SS');
    
    -- Kumpulkan data berdasarkan jenis backup
    IF p_backup_type = 'full' THEN
        SELECT jsonb_build_object(
            'timestamp', NOW(),
            'lab_profile', (SELECT json_agg(row_to_json(lp)) FROM lab_profile lp),
            'members', (SELECT json_agg(row_to_json(m)) FROM members m),
            'projects_summary', (SELECT json_agg(row_to_json(p)) FROM projects p WHERE status IN ('published', '1')),
            'news_summary', (SELECT json_agg(row_to_json(n)) FROM news n WHERE status = 'published'),
            'stats', (SELECT row_to_json(s) FROM mv_lab_dashboard_stats s)
        ) INTO v_backup_data;
        
    ELSIF p_backup_type = 'minimal' THEN
        SELECT jsonb_build_object(
            'timestamp', NOW(),
            'members_count', (SELECT COUNT(*) FROM members),
            'projects_count', (SELECT COUNT(*) FROM projects WHERE status IN ('published', '1')),
            'news_count', (SELECT COUNT(*) FROM news WHERE status = 'published'),
            'dashboard_stats', (SELECT row_to_json(s) FROM mv_lab_dashboard_stats s)
        ) INTO v_backup_data;
    END IF;
    
    -- Simpan ke file atau tabel backup (contoh: output ke log)
    RAISE NOTICE 'Backup created: %', v_backup_data;
    
    -- Bisa juga insert ke tabel backup jika ada
    -- INSERT INTO backup_logs (backup_type, data, created_at) 
    -- VALUES (p_backup_type, v_backup_data, NOW());
    
    COMMIT;
END;
$$;


--
-- Name: cleanup_old_data(integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.cleanup_old_data(IN p_days_old integer DEFAULT 365)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Archive dan hapus komentar spam/pending yang sangat lama
    DELETE FROM comments 
    WHERE status = 'pending' 
      AND created_at < NOW() - INTERVAL '30 days';
    
    -- Archive feedback yang sudah dibaca dan sangat lama
    DELETE FROM feedback 
    WHERE is_read = true 
      AND created_at < NOW() - INTERVAL '180 days';
    
    -- Non-aktifkan proyek/berita yang sangat lama dan tidak aktif
    UPDATE projects 
    SET status = 'archived' 
    WHERE status = '1' 
      AND updated_at < NOW() - (p_days_old || ' days')::INTERVAL;
    
    UPDATE news 
    SET status = 'archived' 
    WHERE status = 'published' 
      AND updated_at < NOW() - (p_days_old || ' days')::INTERVAL;
    
    -- Refresh materialized views
    PERFORM refresh_lab_dashboard_stats();
    
    COMMIT;
    
    RAISE NOTICE 'Cleanup completed for data older than % days', p_days_old;
END;
$$;


--
-- Name: generate_monthly_report(date); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.generate_monthly_report(IN p_month date DEFAULT NULL::date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_report_month DATE;
    v_total_news INTEGER;
    v_total_projects INTEGER;
    v_total_comments INTEGER;
    v_total_feedback INTEGER;
    v_report_text TEXT;
BEGIN
    -- Jika bulan tidak ditentukan, gunakan bulan sebelumnya
    IF p_month IS NULL THEN
        v_report_month := DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month');
    ELSE
        v_report_month := DATE_TRUNC('month', p_month);
    END IF;
    
    -- Hitung statistik
    SELECT COUNT(*) INTO v_total_news
    FROM news 
    WHERE DATE_TRUNC('month', created_at) = v_report_month 
      AND status = 'published';
    
    SELECT COUNT(*) INTO v_total_projects
    FROM projects 
    WHERE DATE_TRUNC('month', created_at) = v_report_month 
      AND status IN ('published', '1');
    
    SELECT COUNT(*) INTO v_total_comments
    FROM comments 
    WHERE DATE_TRUNC('month', created_at) = v_report_month 
      AND status = 'approved';
    
    SELECT COUNT(*) INTO v_total_feedback
    FROM feedback 
    WHERE DATE_TRUNC('month', created_at) = v_report_month;
    
    -- Generate report text
    v_report_text := format(
        'Laporan Aktivitas Lab - %s
        =================================
        Total Berita/Kegiatan: %s
        Total Proyek Baru: %s
        Total Komentar Disetujui: %s
        Total Feedback Masuk: %s
        
        Periode: %s sampai %s',
        TO_CHAR(v_report_month, 'Month YYYY'),
        v_total_news,
        v_total_projects,
        v_total_comments,
        v_total_feedback,
        v_report_month,
        v_report_month + INTERVAL '1 month' - INTERVAL '1 day'
    );
    
    -- Tampilkan report (bisa disimpan ke tabel lain jika perlu)
    RAISE NOTICE '%', v_report_text;
    
    -- Refresh materialized views
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_monthly_activity;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_lab_dashboard_stats;
END;
$$;


--
-- Name: get_lab_stats(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_lab_stats() RETURNS TABLE(total_news bigint, total_projects bigint, total_members bigint, total_comments bigint, total_unread_feedback bigint)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN QUERY 
    SELECT 
        s.total_news,
        s.total_projects,
        s.total_members,
        s.total_comments_approved,
        s.total_unread_feedback
    FROM mv_lab_dashboard_stats s;
END;
$$;


--
-- Name: refresh_lab_dashboard_stats(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.refresh_lab_dashboard_stats() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_lab_dashboard_stats;
END;
$$;


--
-- Name: update_entity_rating(character varying, integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.update_entity_rating(IN p_entity_type character varying, IN p_entity_id integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_avg_rating DECIMAL(3,2);
    v_entity_table VARCHAR(50);
BEGIN
    -- Hitung rating rata-rata dari komentar yang approved
    SELECT AVG(rating) INTO v_avg_rating
    FROM comments
    WHERE entity_type = p_entity_type 
      AND entity_id = p_entity_id 
      AND status = 'approved'
      AND rating IS NOT NULL;
    
    -- Update rating di tabel yang sesuai
    IF p_entity_type = 'project' THEN
        UPDATE projects 
        SET rating = ROUND(v_avg_rating) 
        WHERE id = p_entity_id;
    ELSIF p_entity_type = 'media' THEN
        UPDATE media_assets 
        SET rating = ROUND(v_avg_rating) 
        WHERE id = p_entity_id;
    END IF;
    
    -- Refresh materialized views yang terkait
    PERFORM refresh_lab_dashboard_stats();
END;
$$;


--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_logs (
    id integer NOT NULL,
    username character varying(50),
    action character varying(255),
    ip_address character varying(45),
    device_info text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(150) NOT NULL,
    slug character varying(150) NOT NULL
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comments (
    id integer NOT NULL,
    entity_type character varying(50) NOT NULL,
    entity_id integer NOT NULL,
    author_name character varying(100) NOT NULL,
    author_email character varying(255),
    rating integer,
    content text NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    user_id integer,
    CONSTRAINT comments_rating_check CHECK (((rating IS NULL) OR ((rating >= 1) AND (rating <= 5))))
);


--
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.comments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.comments_id_seq OWNED BY public.comments.id;


--
-- Name: feedback; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.feedback (
    id integer NOT NULL,
    nama_lengkap character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    subjek character varying(255) NOT NULL,
    pesan text NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    is_read boolean DEFAULT false
);


--
-- Name: feedback_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.feedback_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.feedback_id_seq OWNED BY public.feedback.id;


--
-- Name: lab_profile; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_profile (
    id bigint NOT NULL,
    visi text,
    misi text,
    sejarah text,
    updated_at timestamp with time zone DEFAULT now(),
    alamat_lab text,
    email_lab character varying(255),
    telepon_lab character varying(50),
    lokasi_lab text,
    fb_link character varying(255),
    x_link character varying(255),
    ig_link character varying(255),
    yt_link character varying(255),
    linkedin character varying(255),
    logo_path character varying(255),
    hero_image_path character varying(255),
    hero_title character varying(255),
    hero_description text,
    about_hero_image character varying(255) DEFAULT 'assets/images/hero.jpg'::character varying,
    news_hero_image character varying(255) DEFAULT 'assets/images/hero.jpg'::character varying,
    project_hero_image character varying(255) DEFAULT 'assets/images/hero.jpg'::character varying,
    gallery_hero_image character varying(255) DEFAULT 'assets/images/hero.jpg'::character varying,
    contact_hero_image character varying(255) DEFAULT 'assets/images/hero.jpg'::character varying,
    about_title character varying(255) DEFAULT 'Profil Laboratorium'::character varying,
    news_title character varying(255) DEFAULT 'Berita & Kegiatan'::character varying,
    project_title character varying(255) DEFAULT 'Katalog Proyek'::character varying,
    gallery_title character varying(255) DEFAULT 'Galeri Multimedia'::character varying,
    contact_title character varying(255) DEFAULT 'Kontak Kami'::character varying,
    footer_desc text,
    copyright_text character varying(255) DEFAULT NULL::character varying,
    struktur_org_path character varying(255)
);


--
-- Name: lab_profile_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_profile_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_profile_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_profile_id_seq OWNED BY public.lab_profile.id;


--
-- Name: media_assets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media_assets (
    id bigint NOT NULL,
    type character varying(50),
    url character varying(2048) NOT NULL,
    caption character varying(512),
    created_at timestamp with time zone DEFAULT now(),
    deskripsi text,
    rating integer,
    event_name character varying(255),
    CONSTRAINT media_assets_rating_check CHECK (((rating IS NULL) OR ((rating >= 1) AND (rating <= 5))))
);


--
-- Name: media_assets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_assets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_assets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_assets_id_seq OWNED BY public.media_assets.id;


--
-- Name: members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.members (
    id bigint NOT NULL,
    name character varying(200) NOT NULL,
    role character varying(150),
    avatar_url character varying(1024),
    linkedin_url character varying(1024),
    tags character varying(255),
    scholar_url character varying(1024),
    youtube character varying(255),
    facebook character varying(255),
    instagram character varying(255)
);


--
-- Name: members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.members_id_seq OWNED BY public.members.id;


--
-- Name: mv_feedback_summary; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.mv_feedback_summary AS
 SELECT (date_trunc('month'::text, feedback.created_at))::date AS month,
    count(*) AS total_feedback,
    count(*) FILTER (WHERE (feedback.is_read = false)) AS unread_feedback,
    count(*) FILTER (WHERE (feedback.is_read = true)) AS read_feedback
   FROM public.feedback
  GROUP BY ((date_trunc('month'::text, feedback.created_at))::date)
  WITH NO DATA;


--
-- Name: news; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.news (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    summary character varying(512),
    content text,
    cover_image character varying(1024),
    status character varying(20) DEFAULT 1,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    user_id integer,
    type character varying(20) DEFAULT 'news'::character varying,
    category character varying(100)
);


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    summary character varying(512),
    description text,
    year integer,
    cover_image character varying(1024),
    repo_url character varying(1024),
    demo_url character varying(1024),
    status character varying(50) DEFAULT 1,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    category_id bigint,
    user_id integer,
    rating integer,
    CONSTRAINT projects_rating_check CHECK (((rating IS NULL) OR ((rating >= 1) AND (rating <= 5))))
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(200) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(50) DEFAULT 'user'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: mv_lab_dashboard_stats; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.mv_lab_dashboard_stats AS
 SELECT ( SELECT count(*) AS count
           FROM public.news
          WHERE ((news.status)::text = 'published'::text)) AS total_news,
    ( SELECT count(*) AS count
           FROM public.news
          WHERE (((news.type)::text = 'kegiatan'::text) AND ((news.status)::text = 'published'::text))) AS total_kegiatan,
    ( SELECT count(*) AS count
           FROM public.projects
          WHERE ((projects.status)::text = ANY (ARRAY[('published'::character varying)::text, ('1'::character varying)::text]))) AS total_projects,
    ( SELECT count(DISTINCT projects.year) AS count
           FROM public.projects
          WHERE (projects.year IS NOT NULL)) AS total_years_active,
    ( SELECT count(*) AS count
           FROM public.members) AS total_members,
    ( SELECT count(*) AS count
           FROM public.media_assets) AS total_media,
    ( SELECT count(*) AS count
           FROM public.media_assets
          WHERE ((media_assets.type)::text = 'video'::text)) AS total_videos,
    ( SELECT count(*) AS count
           FROM public.comments
          WHERE ((comments.status)::text = 'approved'::text)) AS total_comments_approved,
    ( SELECT count(*) AS count
           FROM public.feedback
          WHERE (feedback.is_read = false)) AS total_unread_feedback,
    ( SELECT count(*) AS count
           FROM public.users
          WHERE ((users.role)::text = 'anggota'::text)) AS total_anggota_users,
    ( SELECT count(*) AS count
           FROM public.users
          WHERE ((users.role)::text = 'admin'::text)) AS total_admin_users
  WITH NO DATA;


--
-- Name: mv_monthly_activity; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.mv_monthly_activity AS
 SELECT (date_trunc('month'::text, comments.created_at))::date AS month,
    count(*) FILTER (WHERE ((comments.entity_type)::text = 'news'::text)) AS news_comments,
    count(*) FILTER (WHERE ((comments.entity_type)::text = 'media'::text)) AS media_comments,
    count(*) FILTER (WHERE (((comments.entity_type)::text <> ALL (ARRAY['news'::text, 'media'::text])) OR (comments.entity_type IS NULL))) AS other_comments,
    count(*) AS total_comments
   FROM public.comments
  WHERE ((comments.status)::text = 'approved'::text)
  GROUP BY ((date_trunc('month'::text, comments.created_at))::date)
  WITH NO DATA;


--
-- Name: news_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.news_tags (
    id integer NOT NULL,
    news_id integer NOT NULL,
    tag_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: mv_news_with_stats; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.mv_news_with_stats AS
 SELECT n.id,
    n.title,
    n.slug,
    n.summary,
    n.category,
    n.cover_image,
    n.status,
    n.created_at,
    n.updated_at,
    n.type,
    count(DISTINCT c.id) AS total_comments,
    COALESCE(avg(c.rating), (0)::numeric) AS avg_rating,
    count(DISTINCT nt.tag_id) AS total_tags,
    u.name AS author_name
   FROM (((public.news n
     LEFT JOIN public.comments c ON ((((c.entity_type)::text = 'news'::text) AND (c.entity_id = n.id) AND ((c.status)::text = 'approved'::text))))
     LEFT JOIN public.news_tags nt ON ((n.id = nt.news_id)))
     LEFT JOIN public.users u ON ((n.user_id = u.id)))
  WHERE ((n.status)::text = 'published'::text)
  GROUP BY n.id, n.title, n.slug, n.summary, n.category, n.cover_image, n.status, n.created_at, n.updated_at, n.type, u.name
  WITH NO DATA;


--
-- Name: project_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_members (
    id integer NOT NULL,
    project_id integer NOT NULL,
    member_id integer NOT NULL
);


--
-- Name: mv_project_details; Type: MATERIALIZED VIEW; Schema: public; Owner: -
--

CREATE MATERIALIZED VIEW public.mv_project_details AS
 SELECT p.id,
    p.title,
    p.slug,
    p.summary,
    p.year,
    p.cover_image,
    p.repo_url,
    p.demo_url,
    p.status,
    p.created_at,
    p.rating,
    c.name AS category_name,
    c.slug AS category_slug,
    count(DISTINCT pm.member_id) AS total_members,
    count(DISTINCT cm.id) AS total_comments,
    COALESCE(avg(cm.rating), (0)::numeric) AS avg_rating
   FROM (((public.projects p
     LEFT JOIN public.categories c ON ((p.category_id = c.id)))
     LEFT JOIN public.project_members pm ON ((p.id = pm.project_id)))
     LEFT JOIN public.comments cm ON ((((cm.entity_type)::text = 'project'::text) AND (cm.entity_id = p.id) AND ((cm.status)::text = 'approved'::text))))
  GROUP BY p.id, p.title, p.slug, p.summary, p.year, p.cover_image, p.repo_url, p.demo_url, p.status, p.created_at, p.rating, c.name, c.slug
  WITH NO DATA;


--
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.news_id_seq OWNED BY public.news.id;


--
-- Name: news_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.news_tags_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: news_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.news_tags_id_seq OWNED BY public.news_tags.id;


--
-- Name: project_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_members_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_members_id_seq OWNED BY public.project_members.id;


--
-- Name: project_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_tags (
    project_id bigint NOT NULL,
    tag_id bigint NOT NULL
);


--
-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(128) NOT NULL,
    data text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    name character varying(150) NOT NULL,
    slug character varying(150) NOT NULL
);


--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: feedback id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedback ALTER COLUMN id SET DEFAULT nextval('public.feedback_id_seq'::regclass);


--
-- Name: lab_profile id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_profile ALTER COLUMN id SET DEFAULT nextval('public.lab_profile_id_seq'::regclass);


--
-- Name: media_assets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_assets ALTER COLUMN id SET DEFAULT nextval('public.media_assets_id_seq'::regclass);


--
-- Name: members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.members ALTER COLUMN id SET DEFAULT nextval('public.members_id_seq'::regclass);


--
-- Name: news id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news ALTER COLUMN id SET DEFAULT nextval('public.news_id_seq'::regclass);


--
-- Name: news_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tags ALTER COLUMN id SET DEFAULT nextval('public.news_tags_id_seq'::regclass);


--
-- Name: project_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members ALTER COLUMN id SET DEFAULT nextval('public.project_members_id_seq'::regclass);


--
-- Name: projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activity_logs; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.activity_logs VALUES (1, 'Admin', 'Update Profil Lab', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 22:57:05.635303');
INSERT INTO public.activity_logs VALUES (2, 'admin2', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 07:39:26.92786');
INSERT INTO public.activity_logs VALUES (3, 'admin2', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 00:47:01.117375');
INSERT INTO public.activity_logs VALUES (4, 'Admin', 'Edit Anggota: Rizky Ananda', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 01:10:51.099107');
INSERT INTO public.activity_logs VALUES (5, 'Admin', 'Edit Anggota: Nadia Putri', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 01:11:48.739097');
INSERT INTO public.activity_logs VALUES (6, 'Admin', 'Edit Anggota: Fajar Pratama', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 01:15:18.850646');
INSERT INTO public.activity_logs VALUES (7, 'Admin', 'Edit Anggota: Dewi Lestari', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 01:15:40.26669');
INSERT INTO public.activity_logs VALUES (8, 'Admin', 'Edit Anggota: Fajar Pratama', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 01:17:19.019985');
INSERT INTO public.activity_logs VALUES (9, 'admin2', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:32:29.9271');
INSERT INTO public.activity_logs VALUES (10, 'admin2', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 21:29:21.744338');
INSERT INTO public.activity_logs VALUES (11, 'admin2', 'Logout Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 21:30:01.564164');
INSERT INTO public.activity_logs VALUES (12, 'admin2', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 21:38:14.040979');
INSERT INTO public.activity_logs VALUES (31, 'Admin Lab', 'Login Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-10-09 00:47:15.089928');
INSERT INTO public.activity_logs VALUES (32, 'Admin Lab', 'Update Pengaturan Situs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-10-09 00:51:15.089928');
INSERT INTO public.activity_logs VALUES (33, 'Editor 1', 'Login Berhasil', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15', '2026-10-21 00:47:15.089928');
INSERT INTO public.activity_logs VALUES (34, 'Editor 1', 'Tambah Berita: Kuliah Tamu Big Data', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15', '2026-10-21 00:59:15.089928');
INSERT INTO public.activity_logs VALUES (35, 'Admin Lab', 'Update Profil Lab', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-10-29 00:47:15.089928');
INSERT INTO public.activity_logs VALUES (36, 'Admin Lab', 'Logout Berhasil', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-10-29 01:22:15.089928');


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categories VALUES (1, 'Mobile Development', 'mobile-development');
INSERT INTO public.categories VALUES (2, 'Multimedia', 'multimedia');
INSERT INTO public.categories VALUES (3, 'Artificial Intelligence', 'ai');
INSERT INTO public.categories VALUES (4, 'Internet of Things', 'iot');
INSERT INTO public.categories VALUES (5, 'UI/UX Design', 'ui-ux');
INSERT INTO public.categories VALUES (6, 'Mobile App', 'mobile-app');
INSERT INTO public.categories VALUES (7, 'Game Development', 'game-development');
INSERT INTO public.categories VALUES (8, 'AR/VR', 'ar-vr');
INSERT INTO public.categories VALUES (9, 'Animation', 'animation');
INSERT INTO public.categories VALUES (10, 'Berita', 'berita');
INSERT INTO public.categories VALUES (11, 'Kegiatan', 'kegiatan');
INSERT INTO public.categories VALUES (12, 'Workshop', 'workshop');


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.comments VALUES (4, 'news', 7, 'serigala', '', 2, 'Halo tes tes tes tes', 'approved', '2025-12-01 11:11:37.361597', '2025-12-01 11:11:37.361597', NULL);
INSERT INTO public.comments VALUES (5, 'news', 6, 'serigala', '', 5, 'Halo halo halo jjjjjjj', 'approved', '2025-12-01 11:12:21.685575', '2025-12-01 11:12:21.685575', NULL);
INSERT INTO public.comments VALUES (6, 'media', 7, 'serigala', '', 1, 'Sangat bagus ekali woow wow wow', 'approved', '2025-12-01 11:24:18.103887', '2025-12-01 11:24:18.103887', NULL);
INSERT INTO public.comments VALUES (7, 'media', 7, 'serigala', '', 3, 'tessssssss sbbsbsbsbbs', 'approved', '2025-12-01 13:06:27.53489', '2025-12-01 13:06:27.53489', NULL);
INSERT INTO public.comments VALUES (8, 'news', 7, 'admin2', '', 3, 'tes vvvvvvvvvvvvvv', 'approved', '2025-12-01 14:11:42.046448', '2025-12-01 14:11:42.046448', NULL);
INSERT INTO public.comments VALUES (9, 'news', 7, 'serigala', '', 3, 'full margin', 'approved', '2025-12-02 15:23:28.5802', '2025-12-02 15:23:28.5802', NULL);
INSERT INTO public.comments VALUES (10, 'news', 10, 'admin2', '', 5, 'wihh keren bgtt', 'approved', '2025-12-18 14:00:11.922945', '2025-12-18 14:00:11.922945', NULL);


--
-- Data for Name: feedback; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.feedback VALUES (81, 'Bagus Prasetyo', 'bagus.prasetyo@example.com', 'Kerja sama proyek aplikasi mobile', 'Kami dari komunitas developer Malang ingin mengajak kolaborasi untuk pengembangan aplikasi mobile edukasi. Mohon info kontak yang bisa dihubungi.', '2026-10-20 01:29:24.081031', true);
INSERT INTO public.feedback VALUES (80, 'Rani Kusuma', 'rani.kusuma@example.com', 'Tanya jadwal pelatihan IoT', 'Selamat pagi, apakah pelatihan IoT untuk semester ini dibuka untuk mahasiswa dari jurusan lain? Terima kasih.', '2026-10-27 01:29:24.081031', false);


--
-- Data for Name: lab_profile; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.lab_profile VALUES (1, 'Menjadi laboratorium multimedia dan mobile technology terdepan dalam inovasi dan kreativitas digital', 'Menyelenggarakan pelatihan, penelitian, dan pengembangan produk digital', 'Laboratorium MMT berdiri sebagai sarana praktik mahasiswa Jurusan Teknologi Informasi.', '2025-11-18 10:58:08.336706+07', 'Jl. Soekarno Hatta No. 9, Malang', 'mobilemultimedia@polinema.ac.id', '(0341) 404424', 'Jl. Soekarno Hatta No.9, Jatimulyo, Lowokwaru, Malang', '#', '#', '#', '#', '#', 'assets/images/logo-placeholder.png', 'assets/images/hero.jpg', 'LABORATORIUM MOBILE AND MULTIMEDIA TECH', 'Laboratorium riset dan pengembangan aplikasi mobile serta multimedia di Politeknik Negeri Malang.', 'assets/images/hero.jpg', 'assets/images/hero.jpg', 'assets/images/hero.jpg', 'assets/images/hero.jpg', 'assets/images/hero.jpg', 'Tentang Kami', 'Berita', 'Proyek', 'Galeri', 'Kontak', 'Laboratorium Mobile and Multimedia Technology, Jurusan Teknologi Informasi, Politeknik Negeri Malang.', '© 2026 Laboratorium MMT POLINEMA', 'assets/uploads/struktur_1765123025.png');


--
-- Data for Name: media_assets; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.media_assets VALUES (12, 'video', 'assets/uploads/1765103096_693555f856c25.mp4', 'UI/UX Design Bootcamp', '2025-12-01 12:00:13+07', 'Aktivitas perancangan antarmuka mobile menggunakan Figma, lengkap dengan prototyping', NULL, 'Figma Training');
INSERT INTO public.media_assets VALUES (11, 'foto', 'assets/uploads/1765102640_69355430be4a3.webp', 'Mobile Game Development', '2025-11-11 12:00:22+07', 'Foto  pembuatan game 2D/3D menggunakan Unity yang cocok untuk platform mobile', NULL, 'Global Game Jam');
INSERT INTO public.media_assets VALUES (13, 'foto', 'assets/uploads/1765113355_69357e0b5abb1.jpg', 'Multimedia Creation Session', '2025-12-03 13:15:55+07', 'Kegiatan editing video, efek visual, audio mixing, atau desain grafis', NULL, 'Editing & Digital Art Workshop');
INSERT INTO public.media_assets VALUES (14, 'foto', 'assets/uploads/1765113899_6935802b55a81.jpg', 'AR for Education', '2025-10-15 13:24:59+07', 'Demo aplikasi AR berbasis Android seperti marker tracking, objek 3D, dan AR edukasi.', NULL, 'AR Exhibition Day');
INSERT INTO public.media_assets VALUES (15, 'foto', 'assets/uploads/1765114091_693580ebe72e2.jpeg', 'Virtual Reality Experience', '2025-12-04 13:28:11+07', 'Dokumentasi uji coba VR headset dan demo aplikasi VR untuk pembelajaran.', NULL, 'VR Technology Showcase');
INSERT INTO public.media_assets VALUES (16, 'animasi', 'assets/uploads/1765155226_6936219a7804b.gif', 'Animation Project Showcase', '2025-12-02 00:53:46+07', 'Galeri hasil render animasi karakter, motion graphics, atau animasi edukasi.', NULL, '2D/3D Animation Class');
INSERT INTO public.media_assets VALUES (10, 'foto', 'assets/uploads/1765094545_6935349121048.jpg', 'Pengembangan Aplikasi Mobile', '2025-12-07 15:02:25.138516+07', 'Dokumentasi kegiatan pembuatan aplikasi Android/iOS menggunakan Flutter, Kotlin, atau Java.', NULL, 'Mobile App Development Showcase');


--
-- Data for Name: members; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.members VALUES (1, 'Rizky Ananda', 'Ketua Laboratorium', 'assets/uploads/team_17659950516942f22b15745.jpg', 'https://linkedin.com/in/rizkyananda', 'Ketua', 'https://scholar.google.com/citations?user=alpha123', 'https://youtube.com/@useralpha', 'https://facebook.com/user.alpha', 'https://instagram.com/user.alpha');
INSERT INTO public.members VALUES (2, 'Nadia Putri', 'Wakil Ketua', 'assets/uploads/team_17659951086942f264b2673.jpg', 'https://linkedin.com/in/nadiaputri', 'Wakil', 'https://scholar.google.com/citations?user=beta456', 'https://youtube.com/@betachannel', 'https://facebook.com/beta.profile', 'https://instagram.com/beta.ig');
INSERT INTO public.members VALUES (4, 'Dewi Lestari', 'Sekretaris', 'assets/uploads/team_17659953406942f34c3e6c3.jpg', 'https://linkedin.com/in/dewilestari', 'Sekretaris', 'https://scholar.google.com/citations?user=delta999', 'https://youtube.com/@deltastream', 'https://facebook.com/delta.user', 'https://instagram.com/delta.snap');
INSERT INTO public.members VALUES (3, 'Fajar Pratama', 'Anggota', 'assets/uploads/team_17659954396942f3af032c7.jpg', 'https://linkedin.com/in/fajarpratama', 'Anggota', 'https://scholar.google.com/citations?user=gamma789', 'https://youtube.com/@gammaworld', 'https://facebook.com/gamma.page', 'https://instagram.com/gamma.life');


--
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.news VALUES (10, 'KULIAH TAMU "BUSINESSES & BIG DATA"', 'kuliah-tamu-businesses-big-data', 'Jurusan TI di Polinema mengadakan kuliah tamu BUSINESSES & BIG DATA', 'Jurusan TI di Polinema mengadakan kuliah tamu BUSINESSES & BIG DATA', 'assets/images/news/1764700405_845.jpg', 'published', '2025-12-03 07:19:17+07', '2025-12-07 14:19:17.324301+07', NULL, 'news', 'kegiatan');
INSERT INTO public.news VALUES (11, 'Pelatihan IOT', 'pelatihan-iot', 'Pelatihan IOT', 'Pelatihan IOT untuk Mahasiswa', 'assets/images/news/1764700442_186.jpg', 'published', '2025-12-01 07:20:45+07', '2025-12-07 14:20:45.702673+07', NULL, 'news', 'workshop');
INSERT INTO public.news VALUES (8, 'Juara Gemastik 2024', 'juara-gemastik-2024', 'Sejumlah Mahasiswa Meraih penghargaan di ajang GEMASTIK 2024', 'Mahasiswa Meraih Penghargaan di ajang GEMASTIK 2024', 'assets/images/news/1764699982_428.jpeg', 'published', '2025-12-01 07:21:06+07', '2025-12-07 14:21:06.410781+07', NULL, 'news', 'lomba');
INSERT INTO public.news VALUES (12, 'WORKSHOP UI/UX', 'workshop-ui-ux', 'WORKSHOP UI/UX', 'Mahasiswa mengikuti Workshop UI/UX yg diadakan oleh jurusan', 'assets/images/news/1764701741_699.jpg', 'published', '2025-12-03 07:19:09+07', '2025-12-07 14:19:09.174126+07', NULL, 'news', 'workshop');
INSERT INTO public.news VALUES (9, 'Persiapan menuju GEMASTIK 2025', 'persiapan-menuju-gemastik-2025', 'Persiapan mahasiswa menuju GEMASTIK 2025', 'Persiapan mahasiswa menuju GEMASTIK 2025', 'assets/images/news/1764700166_573.jpg', 'published', '2025-12-03 07:04:11+07', '2025-12-18 14:04:11.599212+07', NULL, 'news', 'berita');


--
-- Data for Name: news_tags; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: project_members; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: project_tags; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: projects; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.projects VALUES (9, 'Sistem AI Pantau Lalu Lintas di Persimpangan Kota', 'sistem-ai-pantau-lalu-lintas-di-persimpangan-kota', 'Dinas Perhubungan sebuah kota besar menerapkan teknologi computer vision berbasis kecerdasan buatan untuk memantau kepadatan lalu lintas secara real-time.', 'Dinas Perhubungan sebuah kota besar menerapkan teknologi computer vision berbasis kecerdasan buatan untuk memantau kepadatan lalu lintas secara real-time. Kamera yang dipasang pada persimpangan utama mampu mendeteksi dan mengklasifikasikan kendaraan secara otomatis melalui kotak hijau yang muncul pada layar. Teknologi ini digunakan untuk mengurangi kemacetan, melakukan analisis pola kendaraan, serta mendukung sistem pengaturan lampu lalu lintas adaptif.', 2025, 'assets/images/projects/1765084591_551.jpg', NULL, NULL, 'published', '2025-12-07 12:16:31.256679+07', '2025-12-07 12:16:31.256679+07', 3, NULL, NULL);
INSERT INTO public.projects VALUES (10, 'Analisis Kompetisi Marketplace Indonesia 2024', 'analisis-kompetisi-marketplace-indonesia-2024', 'Proyek ini mengkaji persaingan antara marketplace besar di Indonesia, menilai strategi, fitur, dan perilaku konsumen dalam dunia e-commerce yang semakin kompetitif.', 'Proyek ini berfokus pada analisis persaingan berbagai marketplace besar di Indonesia seperti Tokopedia, Shopee, Blibli, Lazada, Bukalapak, Orami, JD.ID, MatahariMall, dan Zalora. Melalui pengumpulan data pasar, fitur aplikasi, strategi marketing, jumlah pengguna, serta performa penjualan, proyek ini bertujuan memahami kekuatan dan kelemahan masing-masing platform.', 2025, 'assets/images/projects/1765085912_281.jpg', NULL, NULL, 'published', '2025-12-07 12:38:32.314534+07', '2025-12-07 12:38:32.314534+07', 11, NULL, NULL);
INSERT INTO public.projects VALUES (11, 'Laporan Kelengkapan Data Covid-19 di Provinsi Indonesia', 'laporan-kelengkapan-data-covid-19-di-provinsi-indonesia', 'Grafik nasional merangkum skor kelengkapan data Covid-19 di situs resmi provinsi, menunjukkan ketimpangan kualitas pelaporan data antar daerah.', 'Laporan ini memetakan tingkat kelengkapan data Covid-19 dari berbagai situs pemerintah provinsi di Indonesia. Grafik menunjukkan bahwa sebagian besar provinsi berada pada skor 2 dan 3, sementara hanya satu provinsi memiliki skor paling rendah (1). Selain itu, terdapat enam provinsi dengan status tidak diketahui akibat keterbatasan akses informasi. Data ini digunakan sebagai dasar evaluasi transparansi informasi publik, efektivitas pelaporan kesehatan, dan penyusunan kebijakan nasional yang lebih akurat.', 2025, 'assets/images/projects/1765086807_868.jpg', NULL, NULL, 'published', '2025-12-07 12:53:27.971246+07', '2025-12-07 12:53:27.971246+07', 10, NULL, NULL);
INSERT INTO public.projects VALUES (12, 'Teknologi AR Meningkatkan Pengalaman Belanja Furnitur', 'teknologi-ar-meningkatkan-pengalaman-belanja-furnitur', 'Aplikasi belanja furnitur kini memungkinkan pengguna melihat produk secara virtual di dalam ruangan mereka menggunakan teknologi AR.', 'Platform belanja furnitur modern menghadirkan fitur Augmented Reality yang memungkinkan pengguna mencoba kursi, sofa, atau perabot lain langsung di ruangan mereka menggunakan kamera ponsel. Pengguna dapat memilih warna, bahan, hingga ukuran sebelum melihat obyek 3D muncul secara realistis. Teknologi ini mengurangi risiko kesalahan pembelian sekaligus meningkatkan pengalaman belanja digital. Aplikasi ini menjadi salah satu terobosan penting dalam industri furnitur berbasis e-commerce.', 2025, 'assets/images/projects/1765086951_331.jpg', NULL, NULL, 'published', '2025-12-07 12:55:51.035215+07', '2025-12-07 12:55:51.035215+07', 1, NULL, NULL);
INSERT INTO public.projects VALUES (13, 'Kampus Luncurkan Sistem Akademik Digital Terintegrasi', 'kampus-luncurkan-sistem-akademik-digital-terintegrasi', 'Perguruan tinggi menghadirkan SIAKAD versi baru dengan tampilan modern dan fitur yang lebih terintegrasi untuk mahasiswa dan dosen', 'Sebuah perguruan tinggi resmi merilis Sistem Informasi Akademik (SIAKAD) terbaru yang mengusung desain modern, navigasi lebih rapi, dan keamanan data yang lebih kuat. Melalui platform ini, mahasiswa dapat mengakses KRS, KHS, riwayat mata kuliah, jadwal perkuliahan, serta informasi akademik lainnya dalam satu dashboard. Sistem ini juga mempermudah dosen dalam mengelola nilai, absensi, dan informasi kelas. Pembaruan ini menjadi bagian dari transformasi digital universitas.', 2024, 'assets/images/projects/1765087024_991.jpg', NULL, NULL, 'published', '2025-12-07 12:57:04.100403+07', '2025-12-07 12:57:04.100403+07', 5, NULL, NULL);
INSERT INTO public.projects VALUES (14, 'Proyek Game Edukasi: “Nusantara Quest: Belajar Sejarah Indonesia”', 'proyek-game-edukasi-nusantara-quest-belajar-sejarah-indonesia', '“Nusantara Quest” adalah sebuah game edukasi berbasis mobile dan desktop yang mengajak pemain menjelajahi sejarah Indonesia melalui misi, kuis, mini-game, dan petualangan interaktif. Game ini dirancang untuk siswa, pelajar, dan masyarakat umum agar lebih memahami peristiwa penting, tokoh pahlawan, budaya, geografi, dan warisan nasional Indonesia dengan cara seru dan mudah.', 'Proyek ini bertujuan menciptakan sebuah game edukasi yang menggabungkan story-based adventure, kuis, dan simulasi sejarah untuk memperkenalkan sejarah Indonesia secara interaktif. Pemain akan berperan sebagai “Penjaga Arsip Nusantara”—seorang penjelajah digital yang bertugas memulihkan ingatan sejarah Indonesia yang hilang akibat gangguan dalam sistem arsip virtual.

Dalam game ini, pemain akan memasuki era-era sejarah Indonesia, mulai dari kerajaan kuno hingga era kemerdekaan. Setiap era memiliki dunia dan misi tersendiri', 2024, 'assets/images/projects/1765087178_442.jpg', NULL, 'https://demo.labpolinema.ac.id/game-edukasi', 'published', '2025-12-07 12:59:38.302225+07', '2025-12-07 13:00:32.506479+07', 7, NULL, NULL);
INSERT INTO public.projects VALUES (15, 'Proyek IoT: “Smart Flood Monitoring System – Sistem Pemantau Banjir Berbasis IoT”', 'proyek-iot-smart-flood-monitoring-system-sistem-pemantau-banjir-berbasis-iot', '“Smart Flood Monitoring System” adalah proyek IoT yang menggunakan sensor ultrasonik untuk memantau ketinggian air sungai atau selokan secara real-time. Data dikirim ke aplikasi untuk memberikan peringatan dini banjir kepada masyarakat.', 'Proyek ini mengembangkan sistem pemantauan banjir berbasis IoT yang bekerja secara otomatis 24 jam. Sensor ultrasonik dipasang di atas permukaan sungai/selokan untuk mengukur perubahan ketinggian air. Data dikirimkan melalui internet ke dashboard monitoring. Ketika air mulai naik melewati batas aman, sistem mengirimkan notifikasi ke warga atau petugas BPBD.

Fitur Utama:
1. Monitoring Ketinggian Air Real-Time

Sensor ultrasonik membaca jarak air ke sensor secara berkala. Data langsung tampil dalam bentuk grafik.

2. Peringatan Dini (Early Warning)

Jika air mencapai level berbahaya, sistem akan mengirimkan notifikasi ke smartphone atau sirene otomatis.

3. Dashboard Online

Menampilkan:

Level air

Status aman/siaga/bahaya

Grafik kenaikan air per jam

Riwayat data

4. Sistem Hemat Energi

Menggunakan power-saving mode, cocok untuk dipasang di lapangan.

5. Komunikasi Jarak Jauh

Bisa menggunakan WiFi, LoRa, atau GSM (SIM800L) untuk wilayah tanpa WiFi.', 2023, 'assets/images/projects/1765088344_265.webp', NULL, NULL, 'published', '2025-12-07 13:19:04.642387+07', '2025-12-18 13:45:17.53669+07', 4, NULL, NULL);


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tags VALUES (1, 'Android', 'android');
INSERT INTO public.tags VALUES (2, 'iOS', 'ios');
INSERT INTO public.tags VALUES (3, 'Flutter', 'flutter');
INSERT INTO public.tags VALUES (4, 'Augmented Reality', 'augmented-reality');
INSERT INTO public.tags VALUES (5, 'WebGL', 'webgl');
INSERT INTO public.tags VALUES (6, 'Machine Learning', 'machine-learning');
INSERT INTO public.tags VALUES (7, 'Deep Learning', 'deep-learning');
INSERT INTO public.tags VALUES (8, 'Arduino', 'arduino');
INSERT INTO public.tags VALUES (9, 'Figma', 'figma');
INSERT INTO public.tags VALUES (10, 'React', 'react');


--
-- Name: activity_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.activity_logs_id_seq', 40, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_id_seq', 12, true);


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.comments_id_seq', 11, true);


--
-- Name: feedback_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.feedback_id_seq', 82, true);


--
-- Name: lab_profile_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.lab_profile_id_seq', 2, true);


--
-- Name: media_assets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.media_assets_id_seq', 16, true);


--
-- Name: members_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.members_id_seq', 4, true);


--
-- Name: news_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.news_id_seq', 14, true);


--
-- Name: news_tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.news_tags_id_seq', 1, false);


--
-- Name: project_members_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_members_id_seq', 2, true);


--
-- Name: projects_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.projects_id_seq', 15, true);


--
-- Name: tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tags_id_seq', 10, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 7, true);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_key UNIQUE (slug);


--
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: feedback feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedback
    ADD CONSTRAINT feedback_pkey PRIMARY KEY (id);


--
-- Name: lab_profile lab_profile_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_profile
    ADD CONSTRAINT lab_profile_pkey PRIMARY KEY (id);


--
-- Name: media_assets media_assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_assets
    ADD CONSTRAINT media_assets_pkey PRIMARY KEY (id);


--
-- Name: members members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.members
    ADD CONSTRAINT members_pkey PRIMARY KEY (id);


--
-- Name: news news_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- Name: news news_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_slug_key UNIQUE (slug);


--
-- Name: news_tags news_tags_news_id_tag_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tags
    ADD CONSTRAINT news_tags_news_id_tag_id_key UNIQUE (news_id, tag_id);


--
-- Name: news_tags news_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tags
    ADD CONSTRAINT news_tags_pkey PRIMARY KEY (id);


--
-- Name: project_members project_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_pkey PRIMARY KEY (id);


--
-- Name: project_tags project_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tags
    ADD CONSTRAINT project_tags_pkey PRIMARY KEY (project_id, tag_id);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: projects projects_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_slug_key UNIQUE (slug);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: tags tags_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_slug_key UNIQUE (slug);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: idx_mv_dashboard_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_mv_dashboard_unique ON public.mv_lab_dashboard_stats USING btree (total_news);


--
-- Name: idx_mv_feedback_summary_month; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_mv_feedback_summary_month ON public.mv_feedback_summary USING btree (month);


--
-- Name: idx_mv_monthly_activity_month; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_mv_monthly_activity_month ON public.mv_monthly_activity USING btree (month);


--
-- Name: idx_mv_news_stats_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mv_news_stats_category ON public.mv_news_with_stats USING btree (category);


--
-- Name: idx_mv_news_stats_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mv_news_stats_created ON public.mv_news_with_stats USING btree (created_at DESC);


--
-- Name: idx_mv_news_stats_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_mv_news_stats_unique ON public.mv_news_with_stats USING btree (id);


--
-- Name: idx_mv_project_details_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mv_project_details_id ON public.mv_project_details USING btree (id);


--
-- Name: idx_mv_project_details_rating; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mv_project_details_rating ON public.mv_project_details USING btree (avg_rating DESC);


--
-- Name: idx_mv_project_details_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_mv_project_details_unique ON public.mv_project_details USING btree (id);


--
-- Name: idx_mv_project_details_year; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mv_project_details_year ON public.mv_project_details USING btree (year DESC);


--
-- Name: idx_news_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_news_created_at ON public.news USING btree (created_at);


--
-- Name: idx_news_tags_tag; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_news_tags_tag ON public.news_tags USING btree (tag_id);


--
-- Name: idx_projects_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_created_at ON public.projects USING btree (created_at);


--
-- Name: idx_sessions_last_activity; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sessions_last_activity ON public.sessions USING btree (last_activity);


--
-- Name: comments update_comments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_comments_updated_at BEFORE UPDATE ON public.comments FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: news update_news_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_news_updated_at BEFORE UPDATE ON public.news FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: projects update_projects_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_projects_updated_at BEFORE UPDATE ON public.projects FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: users update_users_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: comments fk_comments_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: news_tags fk_news_tags_news; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tags
    ADD CONSTRAINT fk_news_tags_news FOREIGN KEY (news_id) REFERENCES public.news(id) ON DELETE CASCADE;


--
-- Name: news_tags fk_news_tags_tag; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news_tags
    ADD CONSTRAINT fk_news_tags_tag FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: news fk_news_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT fk_news_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: projects fk_projects_category; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT fk_projects_category FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: project_tags fk_projects_tags_project; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tags
    ADD CONSTRAINT fk_projects_tags_project FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_tags fk_projects_tags_tag; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tags
    ADD CONSTRAINT fk_projects_tags_tag FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: projects fk_projects_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: project_members project_members_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_member_id_fkey FOREIGN KEY (member_id) REFERENCES public.members(id) ON DELETE CASCADE;


--
-- Name: project_members project_members_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: mv_feedback_summary; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.mv_feedback_summary;


--
-- Name: mv_lab_dashboard_stats; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.mv_lab_dashboard_stats;


--
-- Name: mv_monthly_activity; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.mv_monthly_activity;


--
-- Name: mv_news_with_stats; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.mv_news_with_stats;


--
-- Name: mv_project_details; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: -
--

REFRESH MATERIALIZED VIEW public.mv_project_details;


--
-- PostgreSQL database dump complete
--

--
-- Data akun demo
-- Kata sandi disimpan sebagai hash bcrypt (diverifikasi dengan password_verify()).
--   admin@labpolinema.ac.id   / admin123
--   editor1@labpolinema.ac.id / editor123
--   anggota@labpolinema.ac.id / anggota123
--

INSERT INTO public.users (id, name, email, password, role, created_at, updated_at) VALUES
(1, 'Admin Lab', 'admin@labpolinema.ac.id', '$2y$10$urUmF37hHsH8zwxRiflyIu2S02iVUtq45./Gg16UbUmBjcr9xTlri', 'admin', now(), now()),
(2, 'Editor 1', 'editor1@labpolinema.ac.id', '$2y$10$dGnAqRCHCUzepWkl0Wf6pOLl8gDjzs0TjQGUFMOnE/7z4IgRE8E/S', 'editor', now(), now()),
(3, 'Anggota Demo', 'anggota@labpolinema.ac.id', '$2y$10$EeAG7HU17PArGKcT240CPu1OLz6QxSNp75eS59T3CC/DcWVGK2EwC', 'anggota', now(), now());

SELECT setval('public.users_id_seq', (SELECT max(id) FROM public.users));
