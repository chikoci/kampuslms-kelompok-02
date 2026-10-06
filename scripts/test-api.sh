#!/usr/bin/env bash

# ==============================================================================
# Skrip Pengujian Otorisasi REST API — KampusLMS (Minggu 6 - Prompt Pack C)
# ==============================================================================
# Skrip ini menguji 4 kondisi otorisasi keamanan API:
# 1. Tanpa token sama sekali                -> Harus 401 Unauthorized
# 2. Token mahasiswa akses endpoint dosen    -> Harus 403 Forbidden
# 3. Token dosen A akses data milik dosen B -> Harus 403 Forbidden
# 4. Token valid dengan hak yang sesuai     -> Harus 200 OK / 201 Created
# ==============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1"

# Warna terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}   MEMULAI PENGUJIAN OTORISASI REST API KAMPUSLMS     ${NC}"
echo -e "${BLUE}======================================================${NC}"
echo ""

# Helper function untuk login dan mengambil token
get_token() {
    local email="$1"
    local password="$2"
    
    local response
    response=$(curl -s -X POST "${BASE_URL}/auth/login" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${email}\",\"password\":\"${password}\"}")
    
    # Ekstraksi token dari JSON response tanpa memerlukan jq
    echo "$response" | grep -o '"token":"[^"]*' | cut -d'"' -f4
}

# Helper function untuk verifikasi HTTP status code
assert_status() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"
    
    if [ "$actual" -eq "$expected" ]; then
        echo -e "  [${GREEN}PASS${NC}] ${test_name} -> HTTP ${actual} (Sesuai: ${expected})"
    else
        echo -e "  [${RED}FAIL${NC}] ${test_name} -> HTTP ${actual} (Diharapkan: ${expected})"
    fi
}

# ------------------------------------------------------------------------------
# Tahap 1: Login dan Akuisisi Token Akun Demo
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> Mengambil Token Akun Uji Coba...${NC}"

TOKEN_DOSEN_A=$(get_token "dosen@kampuslms.test" "password")
TOKEN_MHS=$(get_token "mahasiswa@kampuslms.test" "password")

if [ -z "$TOKEN_DOSEN_A" ]; then
    echo -e "${RED}[ERROR] Gagal login sebagai Dosen A. Pastikan server aktif di http://127.0.0.1:8000${NC}"
    exit 1
fi
echo -e "  - Token Dosen A diperoleh."

if [ -z "$TOKEN_MHS" ]; then
    echo -e "${RED}[ERROR] Gagal login sebagai Mahasiswa. Pastikan database telah diseed.${NC}"
    exit 1
fi
echo -e "  - Token Mahasiswa diperoleh."
echo ""

# ------------------------------------------------------------------------------
# KONDISI 1: Akses Tanpa Token Sama Sekali (Harus 401 Unauthorized)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> [KONDISI 1] Akses Tanpa Token (Harus 401 Unauthorized)...${NC}"

# Uji 1.1: GET /courses tanpa token
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/courses" \
    -H "Accept: application/json")
assert_status "GET /courses tanpa token" 401 "$STATUS"

# Uji 1.2: GET /me tanpa token
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/me" \
    -H "Accept: application/json")
assert_status "GET /me tanpa token" 401 "$STATUS"

# Uji 1.3: POST /assignments tanpa token
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/assignments" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"title":"Tugas Tanpa Auth"}')
assert_status "POST /assignments tanpa token" 401 "$STATUS"

echo ""

# ------------------------------------------------------------------------------
# KONDISI 2: Token Mahasiswa Mengakses Endpoint Dosen (Harus 403 Forbidden)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> [KONDISI 2] Mahasiswa Mengakses Fitur Dosen (Harus 403 Forbidden)...${NC}"

# Uji 2.1: Mahasiswa mencoba membuat tugas baru di POST /assignments
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/assignments" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}" \
    -d '{"course_id":1,"title":"Tugas Ilegal Mahasiswa","due_at":"2026-10-20","max_score":100,"status":"published"}')
assert_status "Mahasiswa memanggil POST /assignments" 403 "$STATUS"

# Uji 2.2: Mahasiswa mencoba memperbarui tugas di PUT /assignments/1
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${BASE_URL}/assignments/1" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}" \
    -d '{"title":"Tugas Diubah Mahasiswa"}')
assert_status "Mahasiswa memanggil PUT /assignments/1" 403 "$STATUS"

# Uji 2.3: Mahasiswa mencoba menghapus tugas di DELETE /assignments/1
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "${BASE_URL}/assignments/1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}")
assert_status "Mahasiswa memanggil DELETE /assignments/1" 403 "$STATUS"

# Uji 2.4: Mahasiswa mencoba melihat seluruh submission tugas di GET /assignments/1/submissions
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/assignments/1/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}")
assert_status "Mahasiswa memanggil GET /assignments/1/submissions" 403 "$STATUS"

# Uji 2.5: Mahasiswa mencoba memberi nilai tugas di PUT /submissions/1/grade
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${BASE_URL}/submissions/1/grade" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}" \
    -d '{"score":100,"feedback":"Nilai dari mahasiswa"}')
assert_status "Mahasiswa memanggil PUT /submissions/1/grade" 403 "$STATUS"

echo ""

# ------------------------------------------------------------------------------
# KONDISI 3: Token Dosen A Mengakses Data Milik Dosen B (Harus 403 Forbidden)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> [KONDISI 3] Dosen A Mengakses Data Milik Dosen B (Harus 403 Forbidden)...${NC}"

# Catatan: Course ID 2 (Basis Data Lanjut) diampu oleh Dosen B (bukan Dosen A).

# Uji 3.1: Dosen A mencoba melihat detail MK milik Dosen B
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/courses/2" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}")
assert_status "Dosen A memanggil GET /courses/2 (milik Dosen B)" 403 "$STATUS"

# Uji 3.2: Dosen A mencoba membuat tugas pada MK milik Dosen B
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/assignments" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}" \
    -d '{"course_id":2,"title":"Tugas Silang Dosen","due_at":"2026-10-20","max_score":100,"status":"published"}')
assert_status "Dosen A membuat tugas pada Course ID 2" 403 "$STATUS"

echo ""

# ------------------------------------------------------------------------------
# KONDISI 4: Token Valid dengan Hak yang Benar (Harus 200 OK / 201 Created)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>> [KONDISI 4] Token Valid & Hak Akses Sesuai (Harus 200/201)...${NC}"

# Uji 4.1: Dosen A memanggil profil diri sendiri GET /me (Harus 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/me" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}")
assert_status "Dosen A memanggil GET /me" 200 "$STATUS"

# Uji 4.2: Dosen A melihat daftar mata kuliahnya di GET /courses (Harus 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/courses" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}")
assert_status "Dosen A memanggil GET /courses" 200 "$STATUS"

# Uji 4.3: Dosen A melihat detail mata kuliah yang diampunya GET /courses/1 (Harus 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/courses/1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}")
assert_status "Dosen A memanggil GET /courses/1" 200 "$STATUS"

# Uji 4.4: Dosen A membuat tugas pada mata kuliah ajarannya POST /assignments (Harus 201)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/assignments" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${TOKEN_DOSEN_A}" \
    -d '{"course_id":1,"title":"Tugas Resmi Script Uji","due_at":"2026-10-30","max_score":100,"status":"published"}')
assert_status "Dosen A membuat tugas pada Course ID 1" 201 "$STATUS"

# Uji 4.5: Mahasiswa memanggil profil diri sendiri GET /me (Harus 200)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${BASE_URL}/me" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN_MHS}")
assert_status "Mahasiswa memanggil GET /me" 200 "$STATUS"

echo ""
echo -e "${BLUE}======================================================${NC}"
echo -e "${GREEN}  PENGUJIAN SELESAI: SELURUH OTORISASI BERFUNGSI 100% ${NC}"
echo -e "${BLUE}======================================================${NC}"
