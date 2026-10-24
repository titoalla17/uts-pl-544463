#!/bin/sh
set -eu
# MySQL sudah healthy, tetapi koneksi DB bisa sesaat belum siap.
php -r '
$dsn = "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE");
for ($attempt=1; $attempt<=40; $attempt++) {
    try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT=>3]); exit(0); }
    catch (Throwable $e) { sleep(2); }
}
fwrite(STDERR, "Database not available after retries.\n"); exit(1);
'
php artisan migrate --force
php artisan db:seed --force
exec frankenphp run --config /etc/caddy/Caddyfile
