# FilmKu - Template UTS Pemrograman Lanjut

**Nama repository akhir:** `uts-pl-NIM` (ganti NIM Anda)  
**Tema:** Diary & Manajemen Film (terinspirasi Letterboxd) — catat film yang ditonton, beri rating bintang 0.5–5, tulis ulasan, dan tandai favorit.

Ini adalah **starter project yang perlu di-bootstrap sekali** karena kerangka Laravel,
`backend/composer.lock`, dan `frontend/package-lock.json` dihasilkan di komputer
Anda. **Jangan kirim repository untuk penilaian sebelum bootstrap, dua lockfile,
CI, dan Compose selesai diuji.**

## Prasyarat

- PHP >= 8.2 dan Composer 2; Node.js 22 + npm; Git; Docker Engine + Compose v2.
- Internet saat `./bootstrap.sh` untuk mengunduh Laravel, Sanctum, Vue, dan dependensinya.
- Akun GitHub publik dan Postman.

## 1. Bootstrap proyek (sekali di laptop, sebelum push)

```bash
chmod +x bootstrap.sh
./bootstrap.sh
```

Bash script membuat Laravel 12 di folder sementara, menjalankan `php artisan install:api`,
menyalin kerangka yang belum ada ke folder `backend/` sambil mempertahankan file
aplikasi UTS, lalu menjalankan `npm install` di `frontend/`. Keduanya menghasilkan
**`backend/composer.lock` dan `frontend/package-lock.json`**, yang WAJIB di-commit.

Jika menggunakan Windows tanpa Bash, gunakan WSL Ubuntu atau Git Bash + PHP/Composer/npm.

## 2. Buat .env root (jangan pernah commit)

```bash
cp .env.example .env
php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

Masukkan hasil perintah kedua sebagai `APP_KEY` di `.env`. **Ubah password contoh**
`DB_PASSWORD`, `DB_ROOT_PASSWORD` dan `DEMO_PASSWORD`. Akun demo yang disemai:
`DEMO_EMAIL` + `DEMO_PASSWORD`. Jangan mengunggah screenshot yang menampilkan nilai password.

## 3. Jalankan dan verifikasi

```bash
docker compose up -d --build
docker compose ps
docker compose logs -f backend
```

- Frontend: **http://localhost:8080**
- API login: **http://localhost:8000/api/login** (POST JSON)
- Akun login: sesuai `DEMO_EMAIL` dan `DEMO_PASSWORD` dalam `.env` root.
- Login/tambah/ubah/hapus via frontend lalu refresh browser.

Sebelum screenshot volume, buat data lalu:

```bash
docker compose down
docker compose up -d
docker compose ps
```

Data harus tetap ada. **Jangan memakai `docker compose down -v`**, karena flag `-v`
menghapus named volume data.

## 4. Tes backend

```bash
cd backend
php artisan test
```

Atau pada komputer host tanpa ekstensi SQLite, gunakan GitHub Actions yang
memasang `pdo_sqlite`. CI menjalankan `composer install`, `php artisan test`,
`npm ci`, `npm run build`.

## 5. Postman

Impor `postman/FilmKu.postman_collection.json`; ubah Collection Variables:
`base_url`, `email`, dan `password`. Eksekusi Login untuk menyimpan `token`.
Lanjutkan daftar / tambah (menyimpan `film_id`) / detail / update / validasi / hapus /
logout; request 09 menguji akses tanpa token menghasilkan 401.

## 6. GitHub, branch, PR, status checks

1. Buat repository **public** `uts-pl-NIM` via GitHub UI dengan initial README.
2. Buat branch `dev` dari `main` lewat GitHub UI. Setel Git remote lokal.
3. Buat branch fitur dari dev: `git switch -c feature/film-crud`; commit semua
   source **setelah bootstrap**, kemudian `git push -u origin feature/film-crud`.
4. Buat PR `feature/film-crud` → `dev`, tunggu dua check hijau, lalu merge.
5. **Sebelum merge ke main**, siapkan GitHub Secrets `APP_KEY` dan `DB_PASSWORD`
   (nilainya dari `.env` lokal; jangan commit nilainya).
6. Buat PR `dev` → `main`, tunggu dua check hijau, lalu merge.
7. Aktifkan Rulesets (Settings → Rules) untuk `main` dan `dev`: Require PR,
   require status checks (`backend-test`, `frontend-build`), blokir force push,
   jangan beri bypass kepada diri sendiri bila tersedia.
8. Untuk memenuhi aturan PR sejak awal, atur rules sesegera mungkin. Status
   checks baru bisa dipilih setelah workflow berhasil berjalan paling tidak sekali.
   Screenshot aturan final kedua branch wajib dibuat setelah konfigurasi benar.
9. Jika repo memakai pembatasan izin paket, pastikan Actions mempunyai akses
   write packages. Pastikan kedua Secrets sudah dibuat sebelum deploy pertama.

Workflow `.github/workflows/ci.yml` melakukan build+test pada PR/dev/fitur, tapi
job `deploy` hanya dieksekusi pada **push `main` setelah kedua job lolos**.
Dua image dikirim ke `ghcr.io/<owner>/<repo>-backend:sha-<commit>` dan
`ghcr.io/<owner>/<repo>-frontend:sha-<commit>`. Job tersebut **publikasi image**
ke GHCR, bukan otomatis menjalankan container di server publik.

## 7. Menyusun laporan PDF

Gunakan `LAPORAN_TEMPLATE.md`, isi nama/NIM, tautan repository dan Actions, kendala
nyata dan screenshot hasil tes. Ekspor sebagai PDF dan unggah ke Classroom.
Checklist bukti: Postman 200/201/401/422, fitur Vue, tes Laravel, PR,
branch protections, CI hijau, deploy hijau/main, deploy skipped/dev, dua Packages,
Dockerfiles, `docker compose ps`, volume tetap, log FrankenPHP, dan CRUD dari browser.

## Jalur aliran data

```
Browser -> Nginx Vue (frontend:80, publik 8080)
              | /api/* reverse proxy ke backend:80
              v
         FrankenPHP -> Laravel Sanctum & CRUD
              |
              v
          MySQL (db:3306) -> named volume mysql_data
```

**Alasan proxy:** browser tidak mengenal hostname internal Docker `backend`,
sehingga browser meminta `/api` ke origin frontend yang sama. Nginx di container
frontend-lah yang menghubungi service `backend`. Base URL Axios dikonfigurasi
di `frontend/src/services/api.js` dari variabel `VITE_API_BASE_URL`.

## Catatan keamanan

- `.env` serta password dan token nyata tidak dimasukkan ke Git.
- Token disimpan `sessionStorage` (untuk demonstrasi tugas); untuk produk nyata,
  peninjauan keamanan autentikasi dan cookie HttpOnly tetap diperlukan.
- Seeder demo memperbarui password akun dari ENV saat service dinyalakan ulang.
- HTTPS, rate limiting, rotasi secrets, dan pengelolaan akses diperlukan jika akan
  dipakai secara publik, bukan hanya lokal UTS.

# \## Alur Pengembangan

# 

# 1\. Fitur baru dikerjakan pada branch `feat/\*`.

# 2\. Perubahan fitur diajukan melalui Pull Request ke branch `dev`.

# 3\. Perubahan yang siap dirilis diajukan melalui Pull Request dari `dev` ke `main`.

# 4\. GitHub Actions menjalankan pengujian backend dan build frontend sebelum perubahan digabungkan.

