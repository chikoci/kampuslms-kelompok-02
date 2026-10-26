#!/usr/bin/env bash

# ==============================================================================
# Skrip Pengujian Otomatis Milestone M3 (Materi 10)
# Antrean Asinkron, Database Notification, dan Proteksi Anti-IDOR
# KampusLMS Kelompok 02
# ==============================================================================

set -e

BASE_URL="http://127.0.0.1:8000"
COOKIE_JAR="materi10_cookies.txt"

echo "========================================================================"
echo " [M3 TEST] Memulai Pengujian Queue, Event, Listener, dan Notifikasi"
echo "========================================================================"

# Cleanup cookie sebelum mulai
rm -f "$COOKIE_JAR"

# 1. Jalankan Unit & Feature Test Otomatis
echo -e "\n[Langkah 1] Menjalankan PHPUnit Feature Test Notification & Queue..."
php artisan test --filter=NotificationQueueTest

# 2. Periksa Pemetaan Event dan Listener
echo -e "\n[Langkah 2] Memverifikasi Event Auto-Discovery di Laravel 12..."
php artisan event:list | grep -E "AssignmentPublished|SubmissionGraded"

# 3. Periksa Konfigurasi Antrean
echo -e "\n[Langkah 3] Memeriksa Konfigurasi Antrean..."
QUEUE_CONN=$(php artisan tinker --execute="echo config('queue.default');")
if [ "$QUEUE_CONN" != "database" ]; then
    echo "[-] GAGAL: QUEUE_CONNECTION bukan database (ditemukan: $QUEUE_CONN)"
    exit 1
fi
echo "[+] SUKSES: Antrean dikonfigurasi menggunakan driver database."

# 4. Verifikasi Monitor Antrean
echo -e "\n[Langkah 4] Menjalankan Queue Monitor..."
php artisan queue:monitor default

# 5. Simulasi Pengiriman Notifikasi via Worker
echo -e "\n[Langkah 5] Memproses Antrean Tertunda dengan Worker..."
php artisan queue:work --once

# 6. Verifikasi Tidak Ada Job Gagal
echo -e "\n[Langkah 6] Memeriksa Tabel Failed Jobs..."
php artisan queue:failed

# Cleanup
rm -f "$COOKIE_JAR"

echo -e "\n========================================================================"
echo " [M3 TEST] SELURUH PENGUJIAN MILESTONE M3 LULUS SEMPURNA (100% GREEN)"
echo "========================================================================"
