# Lab MMT POLINEMA — Website Laboratorium

Website profil dan sistem informasi **Laboratorium Mobile and Multimedia Technology
(Lab MMT), Jurusan Teknologi Informasi, Politeknik Negeri Malang**.

Proyek ini awalnya dibuat untuk tugas Project Based Learning, kemudian dirapikan
menjadi aplikasi siap pakai dan siap deploy.

## Fitur

**Halaman publik**

- Beranda dengan ringkasan berita, proyek, dan galeri terbaru
- Profil laboratorium: visi, misi, sejarah, struktur organisasi, dan tim
- Berita/kegiatan dengan halaman detail dan komentar bersistem rating
- Proyek mahasiswa dengan filter kategori dan tahun
- Galeri foto dan video
- Formulir kontak dengan validasi di sisi server
- Login anggota dengan CAPTCHA, pembatasan percobaan, dan sesi aman

**Panel admin (CMS)**

- Dashboard statistik dari `get_lab_stats()` dan materialized view
- CRUD berita, proyek, galeri, dan anggota tim
- Pengaturan tampilan situs (logo, hero image, judul halaman, kontak, sosial media)
- Kotak masuk pesan dari formulir kontak
- Riwayat aktivitas pengguna (`activity_logs`)
- Laporan aktivitas bulanan dan manajemen stored procedure
  (refresh view, generate laporan, backup, cleanup)

**Teknis**

- PostgreSQL: foreign key, trigger, function, stored procedure, materialized view
- Password disimpan sebagai hash bcrypt (`password_hash` / `password_verify`)
- Sesi login disimpan di tabel `sessions` agar tetap berlaku di beberapa instance
- Proteksi CSRF pada seluruh formulir, guard otorisasi pada semua endpoint admin
- Validasi unggahan file berdasarkan ekstensi, ukuran, dan tipe MIME
- Konfigurasi database lewat environment variable (aman untuk hosting)

## Teknologi

| Bagian        | Teknologi                                   |
| ------------- | ------------------------------------------- |
| Backend       | PHP 8.3 (tanpa framework), PDO              |
| Database      | PostgreSQL 15+ (Neon, Supabase, atau lokal) |
| Frontend      | HTML, CSS, JavaScript murni                 |
| Hosting       | Vercel (runtime `vercel-php`)               |

## Struktur Folder

```
├── api/index.php            # Front controller untuk Vercel
├── config/                  # Koneksi DB, sesi, CSRF, helper
├── database/
│   ├── schema.sql           # Skema + data awal (dipakai untuk deploy)
│   └── patch.sql            # Patch objek tambahan (opsional, sudah termasuk di schema.sql)
├── admin/                   # Panel admin
│   ├── api/                 # Endpoint JSON admin
│   └── components/          # Layout admin
├── menu/                    # Halaman publik
│   ├── components/          # Navbar & footer
│   └── menu-detail-*/       # Halaman detail berita/proyek/galeri
├── components/              # Komponen tambahan
├── assets/                  # CSS, JS, gambar, unggahan
├── DOKUMEN PBL/             # Dokumen tugas (tidak dipakai aplikasi)
└── vercel.json              # Konfigurasi deploy
```

## Menjalankan di Lokal

Prasyarat: PHP 8.1+ dengan ekstensi `pdo_pgsql` dan `gd`, serta PostgreSQL.

1. **Siapkan database**

   ```bash
   createdb db_pbl
   psql -d db_pbl -f database/schema.sql
   ```

2. **Salin konfigurasi**

   ```bash
   copy .env.example .env     # Windows
   cp .env.example .env       # Linux/macOS
   ```

   Sesuaikan `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` di `.env`.

3. **Jalankan server**

   ```bash
   php -S localhost:8000
   ```

   Buka <http://localhost:8000>.

   Untuk menguji perilaku yang sama seperti di Vercel (semua request lewat
   satu entry point):

   ```bash
   php -S localhost:8001 api/index.php
   ```

## Deploy ke Vercel

1. **Buat database PostgreSQL gratis** di [Neon](https://neon.tech), lalu salin
   connection string-nya (formatnya `postgresql://user:pass@host/db?sslmode=require`).

2. **Impor skema** ke database Neon:

   ```bash
   psql "postgresql://user:pass@host/db?sslmode=require" -f database/schema.sql
   ```

3. **Push repository** ini ke GitHub.

4. **Impor proyek di Vercel**, lalu tambahkan Environment Variable:

   | Nama           | Nilai                                   |
   | -------------- | --------------------------------------- |
   | `DATABASE_URL` | connection string Neon dari langkah 1   |

   Bisa juga memakai variabel terpisah: `DB_HOST`, `DB_PORT`, `DB_NAME`,
   `DB_USER`, `DB_PASSWORD`, `DB_SSLMODE=require`.

5. **Deploy.** Vercel otomatis memakai `vercel.json`
   (runtime `vercel-php@0.7.4` dengan PHP 8.3, sehingga versi PHP-nya sama
   dengan yang dipakai saat pengujian lokal) dan mengarahkan semua request ke
   `api/index.php`. Tidak perlu Build Command atau Output Directory.

### Catatan saat berjalan di Vercel

- Folder proyek bersifat **read-only**. Fitur unggah file tetap ada tetapi akan
  menampilkan pesan bahwa penyimpanan tidak tersedia (mode demo). Backup juga
  dikirim sebagai tautan unduhan, bukan disimpan ke disk.
- Sesi login disimpan di tabel `sessions` pada database, jadi pengguna tidak
  ter-logout saat request dilayani instance serverless yang berbeda.
- CAPTCHA digambar sebagai PNG bila ekstensi GD tersedia. Di Vercel (tanpa GD)
  gambar dibuat dalam format SVG dengan aturan yang sama.
- File media besar (video) dilayani sebagai aset statis, bukan lewat function.

## Akun Demo

| Peran   | Email                       | Kata sandi   |
| ------- | --------------------------- | ------------ |
| Admin   | `admin@labpolinema.ac.id`   | `admin123`   |
| Editor  | `editor1@labpolinema.ac.id` | `editor123`  |
| Anggota | `anggota@labpolinema.ac.id` | `anggota123` |

Panel admin ada di `/admin`. Ganti kata sandi akun demo sebelum dipakai publik.

## Lisensi

Dibuat untuk keperluan akademik di Politeknik Negeri Malang.
