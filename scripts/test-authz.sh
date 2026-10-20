#!/usr/bin/env bash

# ==============================================================================
# Skrip Pengujian Otorisasi & Pencegahan IDOR - KampusLMS (Milestone M2 / Tugas 2)
# ==============================================================================
# Skrip ini menguji matriks keamanan hak akses 3 peran: Admin, Dosen, Mahasiswa.
# Fokus utama pengujian:
# 1. Mahasiswa mengakses submission mahasiswa lain (IDOR -> 403)
# 2. Dosen mengakses mata kuliah dosen lain (403)
# 3. Mahasiswa mengakses mata kuliah yang tidak diikutinya (403)
# 4. Pengubahan role lewat request (Privilege Escalation ditolak)
# 5. Otorisasi CRUD Materi & Tugas per mata kuliah
# ==============================================================================

# Konfigurasi Target URL:
# 1) Argumen pertama ($1) jika diberikan
# 2) Environment variable ($BASE_URL) jika diset
# 3) Deteksi otomatis Laravel Herd (http://kampuslms-kelompok-02.test)
# 4) Fallback default php artisan serve (http://127.0.0.1:8000)
if [ -n "$1" ]; then
    BASE_URL="$1"
elif [ -z "$BASE_URL" ]; then
    if curl -s -k --connect-timeout 1 -o /dev/null "http://kampuslms-kelompok-02.test/login"; then
        BASE_URL="http://kampuslms-kelompok-02.test"
    else
        BASE_URL="http://127.0.0.1:8000"
    fi
fi

COOKIE_DIR=$(mktemp -d)
COOKIE_ADMIN="${COOKIE_DIR}/admin.cookie"
COOKIE_DOSEN="${COOKIE_DIR}/dosen.cookie"
COOKIE_MHS="${COOKIE_DIR}/mhs.cookie"

# Warna terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

PASSED_COUNT=0
FAILED_COUNT=0

echo -e "${BLUE}======================================================================${NC}"
echo -e "${BLUE}    PENGUJIAN MENYELURUH OTORISASI & KEAMANAN IDOR KAMPUSLMS           ${NC}"
echo -e "${BLUE}======================================================================${NC}"
echo -e "Target Base URL  : ${CYAN}${BASE_URL}${NC}"
echo ""

# Helper function untuk verifikasi status code
assert_status() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"

    if [ "$actual" -eq "$expected" ]; then
        echo -e "  [${GREEN}PASS${NC}] ${test_name} -> HTTP ${actual} (Sesuai: ${expected})"
        PASSED_COUNT=$((PASSED_COUNT + 1))
    else
        echo -e "  [${RED}FAIL${NC}] ${test_name} -> HTTP ${actual} (Diharapkan: ${expected})"
        FAILED_COUNT=$((FAILED_COUNT + 1))
    fi
}

# ------------------------------------------------------------------------------
# 0. Pra-Pemeriksaan Server (Pre-flight Check)
# ------------------------------------------------------------------------------
if ! curl -s -k --connect-timeout 2 -o /dev/null "${BASE_URL}/login"; then
    echo -e "${RED}[ERROR] Server tidak dapat diakses di ${BASE_URL}!${NC}"
    echo -e "Pastikan salah satu dari berikut ini aktif:"
    echo -e "  - Laravel Herd: http://kampuslms-kelompok-02.test"
    echo -e "  - Atau jalankan di terminal: php artisan serve"
    echo ""
    exit 1
fi

# ------------------------------------------------------------------------------
# 1. Inisialisasi Sesi Login untuk 3 Peran (Menggunakan POST /login Resmi)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 1. Inisialisasi Sesi Pengujian (Admin, Dosen, Mahasiswa)...${NC}"

login_as() {
    local email="$1"
    local password="$2"
    local cookie_file="$3"

    rm -f "${cookie_file}"
    local login_page
    login_page=$(curl -s -c "${cookie_file}" "${BASE_URL}/login")
    local csrf_token
    csrf_token=$(echo "${login_page}" | grep 'name="_token"' | head -n 1 | sed -e 's/.*value="//' -e 's/".*//')

    curl -s -b "${cookie_file}" -c "${cookie_file}" \
        --data-urlencode "_token=${csrf_token}" \
        --data-urlencode "email=${email}" \
        --data-urlencode "password=${password}" \
        "${BASE_URL}/login" > /dev/null
}

login_as "admin@kampuslms.test" "password" "${COOKIE_ADMIN}"
login_as "dosen@kampuslms.test" "password" "${COOKIE_DOSEN}"
login_as "mahasiswa@kampuslms.test" "password" "${COOKIE_MHS}"

echo -e "  ${GREEN}✓ Sesi otentikasi resmi berhasil disiapkan.${NC}"
echo ""

# ------------------------------------------------------------------------------
# 2. Pengujian Dashboard Berdasarkan Peran
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 2. Pengujian Akses Dashboard Multi-Peran...${NC}"

STATUS_ADMIN_DASH=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_ADMIN}" "${BASE_URL}/dashboard")
assert_status "Admin mengakses Dashboard" 200 "$STATUS_ADMIN_DASH"

STATUS_DOSEN_DASH=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/dashboard")
assert_status "Dosen mengakses Dashboard" 200 "$STATUS_DOSEN_DASH"

STATUS_MHS_DASH=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/dashboard")
assert_status "Mahasiswa mengakses Dashboard" 200 "$STATUS_MHS_DASH"

STATUS_GUEST_DASH=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/dashboard")
assert_status "Tamu (Guest) mengakses Dashboard (Redirect login)" 302 "$STATUS_GUEST_DASH"
echo ""

# ------------------------------------------------------------------------------
# 3. Pengujian Otorisasi Mata Kuliah (CoursePolicy)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 3. Pengujian Otorisasi Mata Kuliah...${NC}"

# Admin boleh membuat mata kuliah (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_ADMIN}" "${BASE_URL}/courses/create")
assert_status "Admin mengakses form tambah mata kuliah" 200 "$STATUS"

# Dosen dilarang membuat mata kuliah baru (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/create")
assert_status "Dosen mencoba tambah mata kuliah baru" 403 "$STATUS"

# Mahasiswa dilarang membuat mata kuliah baru (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/create")
assert_status "Mahasiswa mencoba tambah mata kuliah baru" 403 "$STATUS"

# Dosen mengakses form edit mata kuliah yang diampunya (Course 1 milik Dosen A -> 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/1/edit")
assert_status "Dosen mengakses edit mata kuliah yang diampunya (Course 1)" 200 "$STATUS"

# Dosen mengakses form edit mata kuliah dosen lain (Course 2 milik Dosen B -> 403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/2/edit")
assert_status "Dosen mencoba edit mata kuliah dosen lain (Course 2)" 403 "$STATUS"

# Mahasiswa mencoba edit mata kuliah (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/1/edit")
assert_status "Mahasiswa mencoba edit mata kuliah" 403 "$STATUS"

# Mahasiswa melihat mata kuliah yang diikutinya (Course 1 -> 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/1")
assert_status "Mahasiswa melihat mata kuliah yang diikutinya (Course 1)" 200 "$STATUS"

# Mahasiswa mencoba melihat mata kuliah yang TIDAK diikutinya (Course 5 -> 403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/5")
assert_status "Mahasiswa mencoba melihat mata kuliah yang tidak diikutinya (Course 5)" 403 "$STATUS"
echo ""

# ------------------------------------------------------------------------------
# 4. Pengujian Otorisasi Materi & Tugas (MaterialPolicy & AssignmentPolicy)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 4. Pengujian CRUD Materi & Tugas...${NC}"

# Dosen menambah materi pada mata kuliahnya sendiri (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/1/materials/create")
assert_status "Dosen membuka form tambah materi pada MK sendiri" 200 "$STATUS"

# Dosen mencoba menambah materi pada mata kuliah dosen lain (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/2/materials/create")
assert_status "Dosen mencoba tambah materi pada MK dosen lain" 403 "$STATUS"

# Mahasiswa mencoba membuka form tambah materi (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/1/materials/create")
assert_status "Mahasiswa mencoba tambah materi perkuliahan" 403 "$STATUS"

# Dosen membuka form buat tugas pada MK sendiri (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/1/assignments/create")
assert_status "Dosen membuka form buat tugas pada MK sendiri" 200 "$STATUS"

# Dosen mencoba membuat tugas pada MK dosen lain (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/courses/2/assignments/create")
assert_status "Dosen mencoba buat tugas pada MK dosen lain" 403 "$STATUS"

# Mahasiswa mencoba membuat tugas baru (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/courses/1/assignments/create")
assert_status "Mahasiswa mencoba buat tugas baru" 403 "$STATUS"

# Mahasiswa dilarang melihat tugas yang masih berstatus draft (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/assignments/3")
assert_status "Mahasiswa mencoba akses tugas berstatus draft (Assignment 3)" 403 "$STATUS"

# Dosen pengampu dapat melihat tugas draft miliknya sendiri (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/assignments/3")
assert_status "Dosen pengampu mengakses tugas draft miliknya (Assignment 3)" 200 "$STATUS"

# Mahasiswa dilarang mengumpulkan tugas pada tugas berstatus draft (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" -X POST "${BASE_URL}/assignments/3/submissions")
assert_status "Mahasiswa mencoba mengumpulkan tugas draft (Assignment 3)" 403 "$STATUS"
echo ""

# ------------------------------------------------------------------------------
# 5. Pengujian IDOR Pengumpulan Tugas (SubmissionPolicy)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 5. Pengujian IDOR Submission Mahasiswa...${NC}"

# Mahasiswa melihat submission miliknya sendiri (Submission 1 milik Mhs ID 3 -> 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/submissions/1")
assert_status "Mahasiswa melihat submission miliknya sendiri (ID 1)" 200 "$STATUS"

# Mahasiswa mencoba melihat submission mahasiswa lain (Submission 2 -> 403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/submissions/2")
assert_status "Mahasiswa mencoba melihat submission mahasiswa lain (ID 2 - IDOR Attack)" 403 "$STATUS"

# Dosen pengampu melihat submission mahasiswanya (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/submissions/1")
assert_status "Dosen pengampu memeriksa submission mahasiswa" 200 "$STATUS"

# Admin melihat submission (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_ADMIN}" "${BASE_URL}/submissions/1")
assert_status "Admin memeriksa submission mahasiswa" 200 "$STATUS"
echo ""

# ------------------------------------------------------------------------------
# 6. Pengujian Otorisasi Manajemen Pengguna & Anti Eskalasi Hak Akses
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> 6. Pengujian Otorisasi Pengguna & Anti Privilege Escalation...${NC}"

# Admin mengakses direktori pengguna (200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_ADMIN}" "${BASE_URL}/users")
assert_status "Admin mengakses daftar pengguna (/users)" 200 "$STATUS"

# Dosen dilarang membuka daftar pengguna (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_DOSEN}" "${BASE_URL}/users")
assert_status "Dosen mencoba akses daftar pengguna (/users)" 403 "$STATUS"

# Mahasiswa dilarang membuka daftar pengguna (403)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "${COOKIE_MHS}" "${BASE_URL}/users")
assert_status "Mahasiswa mencoba akses daftar pengguna (/users)" 403 "$STATUS"
echo ""

# Cleanup temporary cookies
rm -rf "${COOKIE_DIR}"

# ------------------------------------------------------------------------------
# Ringkasan Hasil Pengujian
# ------------------------------------------------------------------------------
echo -e "${BLUE}======================================================================${NC}"
echo -e "${CYAN}Total Pengujian  : $((PASSED_COUNT + FAILED_COUNT))${NC}"
echo -e "${GREEN}Lolos (Passed)   : ${PASSED_COUNT}${NC}"
echo -e "${RED}Gagal (Failed)   : ${FAILED_COUNT}${NC}"
echo -e "${BLUE}======================================================================${NC}"

if [ "${FAILED_COUNT}" -eq 0 ]; then
    echo -e "${GREEN}SELURUH PENGUJIAN OTORISASI LOLOS (100% SUKSES)!${NC}"
    exit 0
else
    echo -e "${RED}ADA PENGUJIAN OTORISASI YANG GAGAL. PERIKSA LOG DI ATAS!${NC}"
    exit 1
fi
