#!/usr/bin/env bash
# Menentukan bash sebagai interpreter shell; jika baris shebang ini dihilangkan, sistem operasi mungkin mengeksekusi skrip menggunakan shell yang tidak kompatibel (misal sh biasa) sehingga fitur-fitur bash gagal berjalan.

set -e
# Menginstruksikan shell untuk segera menghentikan eksekusi skrip pada perintah pertama yang gagal (exit code non-zero); jika baris ini dihilangkan, skrip akan terus mengeksekusi perintah berikutnya meskipun migrasi database atau composer gagal, menyebabkan aplikasi rusak berada dalam kondisi setengah terdeploy.

export DEPLOY_SECRET="kampuslms-preflight-check"
# Menyimpan token rahasia sementara untuk bypass maintenance mode; jika dihilangkan, proses pengujian verifikasi HTTP di akhir tidak dapat menembus mode maintenance untuk memeriksa kesehatan aplikasi sebelum dibuka ke publik.

php artisan down --render="errors::503" --secret="${DEPLOY_SECRET}" || true
# Mengaktifkan mode maintenance dengan tampilan 503 dan kunci rahasia agar user tidak mengakses data yang sedang diubah; jika dihilangkan, pengguna yang sedang submit tugas di tengah deployment akan mengalami kehilangan data atau melihat layar error crash.

git pull origin main
# Menarik commit kode terbaru dari branch utama repositori GitHub ke server produksi; jika dihilangkan, server akan terus menjalankan kode versi lama dan pembaruan fitur serta perbaikan bug tidak akan pernah terpasang.

composer install --no-dev --optimize-autoloader --no-interaction
# Memasang dependensi PHP produksi tanpa paket dev (testing) dan mengoptimalkan autoloader classmap; jika dihilangkan, class baru tidak akan ditemukan (Class not found) atau paket dev yang berat akan memboroskan memori RAM server.

npm ci
# Memasang dependensi Node.js secara bersih dan deterministik sesuai package-lock.json; jika dihilangkan, versi library frontend bisa tidak konsisten sehingga proses kompilasi aset berpotensi menghasilkan error.

npm run build
# Mengompilasi dan mem-bundle seluruh aset CSS Tailwind dan JavaScript Vite ke folder public/build; jika dihilangkan, tampilan website produksi akan hancur tanpa styling CSS dan komponen interaktif tidak berfungsi.

php artisan migrate --force
# Menjalankan pembaruan skema tabel database secara otomatis dengan flag --force wajib di produksi; jika dihilangkan, kolom atau tabel baru tidak akan pernah dibuat di database sehingga query SQL aplikasi melempar exception "Base table or view not found".

php artisan config:cache
# Menggabungkan seluruh file konfigurasi menjadi satu file cache terkompresi; jika dihilangkan, Laravel terpaksa membaca puluhan file config setiap kali request masuk sehingga waktu respons aplikasi menjadi jauh lebih lambat.

php artisan route:cache
# Mengompilasi seluruh pendaftaran rute URL ke dalam satu berkas cache serialisasi cepat; jika dihilangkan, proses pencocokan route HTTP menjadi lambat terutama saat jumlah endpoint aplikasi semakin banyak.

php artisan view:cache
# Mengompilasi seluruh template tampilan Blade menjadi kode PHP murni di awal; jika dihilangkan, pengguna pertama yang mengakses tiap halaman akan mengalami loading lama karena template baru dikompilasi saat request terjadi.

php artisan event:cache
# Mengompilasi registrasi event dan event listener ke dalam cache sistem; jika dihilangkan, Laravel harus memindai dan memetakan listener secara dinamis setiap kali sebuah event dipicu di dalam aplikasi.

php artisan queue:restart
# Memberi instruksi graceful restart kepada worker background agar memuat ulang kode PHP baru ke dalam memori proses; jika dihilangkan, worker daemon akan terus mengeksekusi job (seperti pengiriman notifikasi nilai) menggunakan logika kode versi lama yang tersimpan di memori RAM.

APP_BASE_URL="${APP_URL:-http://127.0.0.1:8000}"
# Menentukan target URL aplikasi dengan fallback ke localhost jika variabel APP_URL belum diset; jika dihilangkan, variabel URL menjadi kosong sehingga pengecekan kesehatan server di baris berikutnya gagal dijalankan.

HTTP_PROBE=$(curl -s -o /dev/null -w "%{http_code}" --cookie "laravel_maintenance=${DEPLOY_SECRET}" "${APP_BASE_URL}/" || echo "000")
# Melakukan request uji coba HTTP menggunakan token secret untuk memastikan aplikasi merespons dengan benar di balik maintenance mode; jika dihilangkan, kita tidak memiliki cara untuk mengetahui apakah aplikasi sehat atau mengalami fatal error 500 sebelum membuka kembali sistem ke publik.

if [ "$HTTP_PROBE" = "500" ]; then echo "Deploy dibatalkan karena aplikasi mengalami HTTP 500!"; exit 1; fi
# Memvalidasi status respons server dan membatalkan proses jika terdeteksi error 500; jika dihilangkan, aplikasi yang mengalami crash akan langsung dibuka ke publik dan seluruh pengguna akan melihat halaman error.

php artisan up
# Menonaktifkan mode maintenance dan membuka kembali akses penuh aplikasi untuk seluruh pengguna; jika dihilangkan, aplikasi akan selamanya terkunci pada status pemeliharaan (503 Service Unavailable) bagi pengunjung umum.

echo ">>> DEPLOYMENT KAMPUSLMS SELESAI DENGAN SUKSES!"
# Mencetak pesan konfirmasi keberhasilan seluruh tahapan deployment ke log konsol; jika dihilangkan, administrator server atau pipeline automasi tidak memiliki indikator visual yang jelas bahwa seluruh proses telah selesai dengan sukses.

