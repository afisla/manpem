#!/bin/bash
# File: server.sh
# Deskripsi: Script untuk merestart database MariaDB & PHP built-in web server, lalu otomatis membuka browser.

echo "==========================================="
echo "⚙️  Menghentikan server & proses sebelumnya..."
echo "==========================================="

# 1. Hentikan PHP built-in server jika sedang berjalan
PID_PHP=$(pgrep -f "php -S 127.0.0.1:8080")
if [ ! -z "$PID_PHP" ] || pgrep -f "php -S 127.0.0.1:8080" > /dev/null; then
    echo "🛑 Menghentikan PHP Web Server (PID: $PID_PHP)..."
    pkill -f "php -S 127.0.0.1:8080"
    sleep 1
fi

echo "🔄 Menjalankan database MariaDB..."
# 2. Start layanan MariaDB/MySQL
sudo service mariadb start 2>/dev/null || sudo systemctl start mariadb 2>/dev/null || mariadbd-safe --skip-grant-tables &

echo "==========================================="
echo "🚀 Menjalankan kembali server..."
echo "==========================================="

# 3. Jalankan PHP Built-in Server di latar belakang
# Output dialihkan ke server.log agar rapi
php -S 127.0.0.1:8080 -t "/home/ubuntu/project" > "/home/ubuntu/project/server.log" 2>&1 &
sleep 2

# Cek apakah server berhasil jalan
if pgrep -f "php -S 127.0.0.1:8080" > /dev/null; then
    echo "✅ PHP Web Server berhasil berjalan di http://127.0.0.1:8080"
else
    echo "❌ Gagal menjalankan PHP Web Server!"
    exit 1
fi

echo "🎉 Selesai!"
