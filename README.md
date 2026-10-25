# 🎬 FilmKu — Diary & Manajemen Film

FilmKu adalah aplikasi web untuk mencatat dan mengelola film yang telah ditonton. Terinspirasi dari Letterboxd, aplikasi ini memungkinkan pengguna menyimpan daftar film, memberikan rating, menulis ulasan, dan menandai film favorit.

Project ini dikembangkan untuk memenuhi tugas UTS mata kuliah **Pemrograman Lanjut**.

## ✨ Fitur Utama

- **Autentikasi pengguna** — Login dan logout menggunakan token.
- **Manajemen film (CRUD)** — Menambah, melihat, mengubah, dan menghapus catatan film.
- **Rating film** — Memberikan rating bintang 0,5–5.
- **Ulasan film** — Menulis ulasan untuk film yang ditonton.
- **Film favorit** — Menandai film favorit.
- **Penyimpanan database** — Data disimpan di MySQL dan tetap tersedia setelah container dimulai ulang.
- **RESTful API** — Backend menyediakan API dengan respons JSON dan kode HTTP yang sesuai.
- **Pengujian API** — Koleksi Postman tersedia untuk menguji autentikasi, CRUD, validasi, dan akses tanpa token.
- **CI/CD otomatis** — GitHub Actions menjalankan pengujian backend, build frontend, dan publikasi image Docker ke GHCR.

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12, PHP |
| Autentikasi API | Laravel Sanctum |
| Frontend | Vue.js, JavaScript |
| HTTP client | Axios |
| Database | MySQL |
| Web server frontend | Nginx |
| Runtime backend | FrankenPHP |
| Containerization | Docker, Docker Compose |
| API testing | Postman |
| CI/CD | GitHub Actions |
| Container registry | GitHub Container Registry (GHCR) |

## 📁 Struktur Repository

```text
uts-pl-544463/
├── .github/
│   └── workflows/
│       └── ci.yml
├── backend/
│   ├── app/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── Dockerfile
│   ├── composer.json
│   └── composer.lock
├── frontend/
│   ├── src/
│   ├── Dockerfile
│   ├── nginx.conf
│   ├── package.json
│   └── package-lock.json
├── postman/
│   └── FilmKu.postman_collection.json
├── .dockerignore
├── .env.example
├── docker-compose.yml
└── README.md
```

## 🚀 Menjalankan Aplikasi

### Prasyarat

Pastikan perangkat sudah memiliki:

- Docker Engine atau Docker Desktop.
- Docker Compose v2.
- Git.

### 1. Clone repository

```bash
git clone https://github.com/titoalla17/uts-pl-544463.git
cd uts-pl-544463
```

### 2. Konfigurasi environment

Buat file `.env` di root project dengan menyalin `.env.example`.

**Windows PowerShell:**

```powershell
Copy-Item .env.example .env
```

**Linux/macOS:**

```bash
cp .env.example .env
```

Sesuaikan nilai environment yang diperlukan, terutama `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `DEMO_EMAIL`, dan `DEMO_PASSWORD`.

Untuk membuat application key, jalankan:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Masukkan hasilnya sebagai nilai `APP_KEY` di `.env`. Gunakan password yang aman dan jangan membagikan kredensial tersebut.

> **Penting:** File `.env` hanya untuk konfigurasi lokal dan tidak boleh di-commit ke Git. Gunakan `.env.example` sebagai referensi konfigurasi.

### 3. Build dan jalankan container

Dari direktori root project, jalankan:

```bash
docker compose up -d --build
```

Perintah tersebut membangun image yang diperlukan dan menjalankan tiga layanan utama:

- **Frontend** — Antarmuka Vue.js yang dilayani Nginx.
- **Backend** — RESTful API Laravel yang berjalan dengan FrankenPHP.
- **Database** — MySQL untuk menyimpan data aplikasi.

Periksa status container:

```bash
docker compose ps
```

Pastikan ketiga layanan berjalan dengan baik.

### 4. Akses aplikasi

| Layanan | Alamat |
|---|---|
| Frontend | http://localhost:8080 |
| API login | `POST http://localhost:8000/api/login` |

Gunakan kredensial demo sesuai konfigurasi `DEMO_EMAIL` dan `DEMO_PASSWORD` di file `.env`.

Setelah login, coba tambahkan film, ubah data film, hapus film, dan muat ulang halaman untuk memverifikasi fungsionalitas aplikasi.

### 5. Memverifikasi persistensi data

Data disimpan pada named volume MySQL sehingga tidak hilang hanya karena container dihentikan dan dijalankan kembali.

Untuk menguji persistensi:

```bash
docker compose down
docker compose up -d
docker compose ps
```

Buka kembali aplikasi dan periksa data yang sebelumnya disimpan.

> Jangan menggunakan `docker compose down -v` saat menguji persistensi karena opsi `-v` dapat menghapus named volume database.

## 🔌 RESTful API

Backend Laravel menyediakan API untuk autentikasi dan pengelolaan data film.

| Operasi | Keterangan |
|---|---|
| Login | Memperoleh token autentikasi |
| Logout | Mengakhiri sesi autentikasi melalui API |
| List film | Menampilkan daftar film |
| Detail film | Mengambil data satu film |
| Create film | Menambahkan film baru |
| Update film | Mengubah data film |
| Delete film | Menghapus data film |

Endpoint yang memerlukan autentikasi harus menyertakan token pada request. API menggunakan format JSON serta kode HTTP yang sesuai, termasuk `201 Created`, `401 Unauthorized`, dan `422 Unprocessable Entity` untuk kondisi yang relevan.

Detail request, parameter, dan contoh respons tersedia pada koleksi Postman di `postman/FilmKu.postman_collection.json`.

## 🧪 Pengujian

### Pengujian backend

Pastikan dependensi backend sudah tersedia, kemudian jalankan dari direktori `backend/`:

```bash
php artisan test
```

Pengujian mencakup perilaku API dan dapat digunakan untuk memverifikasi fungsionalitas backend.

### Pengujian menggunakan Postman

1. Buka Postman.
2. Impor `postman/FilmKu.postman_collection.json`.
3. Sesuaikan Collection Variables `base_url`, `email`, dan `password`.
4. Jalankan request login untuk memperoleh token.
5. Uji daftar film, penambahan, detail, pembaruan, validasi, penghapusan, dan logout.
6. Jalankan request akses tanpa token untuk memverifikasi respons `401 Unauthorized`.

Jangan menyimpan token autentikasi atau password pribadi di dalam collection yang di-commit ke repository.

## 🐳 Arsitektur Docker

Aplikasi menggunakan Docker Compose untuk menjalankan frontend, backend, dan database dalam container terpisah.

```text
Browser
   |
   v
Frontend: Vue.js + Nginx
   |  localhost:8080
   |
   | /api/* melalui reverse proxy
   v
Backend: FrankenPHP + Laravel
   |  API, autentikasi, dan CRUD
   |
   v
Database: MySQL
   |  db:3306
   v
Named Volume: mysql_data
```

Frontend menggunakan Axios untuk mengirim request ke API. Konfigurasi alamat API terdapat pada `frontend/src/services/api.js` melalui variabel `VITE_API_BASE_URL`.

Nginx pada container frontend meneruskan request `/api/*` ke layanan backend melalui jaringan internal Docker. Dengan demikian, browser tidak perlu mengakses hostname internal Docker secara langsung.

MySQL menggunakan named volume untuk mempertahankan data ketika container dihentikan atau dibuat ulang.

## ⚙️ CI/CD dengan GitHub Actions

Workflow tersedia di `.github/workflows/ci.yml`.

### Continuous Integration (CI)

GitHub Actions menjalankan dua job utama:

- **`backend-test`** — Memasang dependensi backend dan menjalankan `php artisan test`.
- **`frontend-build`** — Memasang dependensi frontend menggunakan `npm ci` dan memvalidasi build menggunakan `npm run build`.

Workflow digunakan untuk memeriksa perubahan sebelum Pull Request dapat digabungkan.

### Continuous Delivery (CD)

Job `deploy` hanya berjalan pada push ke branch `main` setelah job pengujian dan build berhasil.

Job ini membangun dan memublikasikan dua image Docker ke GitHub Container Registry (GHCR):

```text
ghcr.io/<owner>/<repo>-backend:sha-<commit>
ghcr.io/<owner>/<repo>-frontend:sha-<commit>
```

Tag image menggunakan commit SHA agar setiap image dapat ditelusuri ke versi source code yang membangunnya.

GitHub Actions menggunakan GitHub Secrets untuk konfigurasi sensitif, termasuk `APP_KEY` dan `DB_PASSWORD`. Nilai rahasia tidak disimpan langsung di workflow atau repository.

**Catatan:** Workflow CD memublikasikan image ke GHCR. Publikasi image ini tidak dengan sendirinya berarti aplikasi sudah dijalankan pada server hosting publik.

## 🌿 Alur Pengembangan Git

Pengembangan menggunakan feature branch, Pull Request, dan branch utama yang dilindungi.

```text
feat/*
   |
   | Pull Request
   v
  dev
   |
   | Pull Request
   v
  main
   |
   v
GitHub Actions: Build & Test
   |
   v
Publikasi image Docker ke GHCR
```

Ketentuan alur kerja:

1. Fitur baru dikembangkan pada branch `feat/*`.
2. Perubahan diajukan melalui Pull Request dari feature branch ke `dev`.
3. CI harus berhasil sebelum perubahan digabungkan.
4. Perubahan yang siap dirilis diajukan melalui Pull Request dari `dev` ke `main`.
5. Push ke `main` memicu proses CD jika job CI berhasil.
6. Branch `main` dan `dev` menggunakan perlindungan branch sesuai konfigurasi repository.

## 🔐 Catatan Keamanan

- Jangan commit file `.env`, password, application key, atau token autentikasi.
- Gunakan GitHub Secrets untuk konfigurasi sensitif pada workflow.
- Token autentikasi frontend disimpan dalam `sessionStorage` untuk kebutuhan demonstrasi tugas.
- Implementasi ini ditujukan untuk pembelajaran. Penggunaan produksi memerlukan peninjauan keamanan tambahan, HTTPS, rate limiting, dan pengelolaan kredensial yang sesuai.

## 👨‍🎓 Informasi Project

- **Nama aplikasi:** FilmKu
- **Mata kuliah:** Pemrograman Lanjut
- **Jenis project:** Ujian Tengah Semester (UTS)
- **Repository:** [titoalla17/uts-pl-544463](https://github.com/titoalla17/uts-pl-544463)

---

*FilmKu — Catat film yang ditonton, beri rating, tulis ulasan, dan simpan film favoritmu.*
