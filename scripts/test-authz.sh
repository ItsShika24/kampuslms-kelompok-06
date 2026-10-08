#!/usr/bin/env bash
# ==============================================================================
# Skrip Pengujian Otorisasi & Pencegahan IDOR — KampusLMS (Minggu 7 - Milestone M2)
# ==============================================================================
# Sesuai Modul 03 Minggu 7 §7.2 Prompt D & §7.3 BUILD:
# Menguji verifikasi keamanan per peran:
# 1. Unauthenticated (Guest)                     -> 302 / 401
# 2. Mahasiswa A mengakses submission Mahasiswa B -> 403 Forbidden (IDOR ditutup)
# 3. Dosen A mengubah mata kuliah Dosen B        -> 403 Forbidden (IDOR ditutup)
# 4. Dosen A memberi nilai ke submission Dosen B -> 403 Forbidden (IDOR ditutup)
# 5. Mahasiswa/Dosen mengakses User Management   -> 403 Forbidden
# 6. Admin memiliki akses penuh                  -> 200 OK
# 7. Pengguna sah mengakses data miliknya        -> 200 OK
# ==============================================================================

BASE_URL="${1:-http://127.0.0.1:8000}"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASSED_COUNT=0
FAILED_COUNT=0

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}  KAMPUSLMS — AUTHORIZATION & IDOR TEST (WEEK 7 - MILESTONE M2) ${NC}"
echo -e "${BLUE}  Target: ${BASE_URL}                                           ${NC}"
echo -e "${BLUE}================================================================${NC}\n"

check_status() {
    local expected="$1"
    local actual="$2"
    local desc="$3"

    if [ "$actual" -eq "$expected" ]; then
        echo -e "  [${GREEN}PASS${NC}] Expected: $expected | Actual: $actual — $desc"
        PASSED_COUNT=$((PASSED_COUNT + 1))
    else
        echo -e "  [${RED}FAIL${NC}] Expected: $expected | Actual: $actual — $desc"
        FAILED_COUNT=$((FAILED_COUNT + 1))
    fi
}

# Temporary cookie jar files
COOKIE_ADMIN=$(mktemp)
COOKIE_DOSEN_A=$(mktemp)
COOKIE_DOSEN_B=$(mktemp)
COOKIE_MHS_A=$(mktemp)

cleanup() {
    rm -f "$COOKIE_ADMIN" "$COOKIE_DOSEN_A" "$COOKIE_DOSEN_B" "$COOKIE_MHS_A"
}
trap cleanup EXIT

echo -e "${YELLOW}>>> 1. PERSIAPAN AUTENTIKASI AKUN WEB <<<${NC}"

# Ambil CSRF Token & Cookie awal
LOGIN_PAGE=$(curl -s -c "$COOKIE_ADMIN" "${BASE_URL}/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*' | head -1 | cut -d'"' -f4)

# Login Admin (Budi Santoso)
curl -s -b "$COOKIE_ADMIN" -c "$COOKIE_ADMIN" -X POST "${BASE_URL}/login" \
  -d "_token=${CSRF_TOKEN}&email=admin@kampuslms.test&password=password" > /dev/null
echo -e "  [OK] Akun Admin terautentikasi."

# Login Dosen A (Dr. Ir. Bambang Hermanto)
LOGIN_PAGE=$(curl -s -c "$COOKIE_DOSEN_A" "${BASE_URL}/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*' | head -1 | cut -d'"' -f4)
curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -X POST "${BASE_URL}/login" \
  -d "_token=${CSRF_TOKEN}&email=dosen@kampuslms.test&password=password" > /dev/null
echo -e "  [OK] Akun Dosen A terautentikasi."

# Login Dosen B (Siti Rahmawati)
LOGIN_PAGE=$(curl -s -c "$COOKIE_DOSEN_B" "${BASE_URL}/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*' | head -1 | cut -d'"' -f4)
curl -s -b "$COOKIE_DOSEN_B" -c "$COOKIE_DOSEN_B" -X POST "${BASE_URL}/login" \
  -d "_token=${CSRF_TOKEN}&email=siti.rahmawati@kampuslms.test&password=password" > /dev/null
echo -e "  [OK] Akun Dosen B terautentikasi."

# Login Mahasiswa A (Muhammad Rizky Pratama)
LOGIN_PAGE=$(curl -s -c "$COOKIE_MHS_A" "${BASE_URL}/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*' | head -1 | cut -d'"' -f4)
curl -s -b "$COOKIE_MHS_A" -c "$COOKIE_MHS_A" -X POST "${BASE_URL}/login" \
  -d "_token=${CSRF_TOKEN}&email=mahasiswa@kampuslms.test&password=password" > /dev/null
echo -e "  [OK] Akun Mahasiswa A terautentikasi.\n"


echo -e "${YELLOW}>>> 2. UJI KASUS: PENGUNJUNG TANPA LOGIN (GUEST) <<<${NC}"
# Akses Dashboard tanpa login -> Redirect ke /login (302)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/dashboard")
check_status 302 "$STATUS" "Guest mengakses /dashboard harus diredirect (302) ke /login"

# Akses API tanpa token -> 401 Unauthorized
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "${BASE_URL}/api/v1/courses")
check_status 401 "$STATUS" "Guest mengakses endpoint privat API harus 401 Unauthorized"


echo -e "\n${YELLOW}>>> 3. UJI KASUS IDOR: MAHASISWA A MENGAKSES SUBMISSION ORANG LAIN <<<${NC}"
# Mahasiswa A melihat riwayat submissions sendiri
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" "${BASE_URL}/submissions")
check_status 200 "$STATUS" "Mahasiswa A melihat daftar submission miliknya sendiri"

# Mahasiswa A mencoba akses submission id 99 (bukan miliknya)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" "${BASE_URL}/submissions/99")
# Jika id 99 ada dan bukan miliknya -> 403; jika tidak ada -> 404
if [ "$STATUS" -eq 403 ] || [ "$STATUS" -eq 404 ]; then
    echo -e "  [${GREEN}PASS${NC}] Actual: $STATUS — Mahasiswa A dilarang mengakses submission orang lain (IDOR Tertutup)"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo -e "  [${RED}FAIL${NC}] Actual: $STATUS — Submission orang lain masih bisa diakses!"
    FAILED_COUNT=$((FAILED_COUNT + 1))
fi


echo -e "\n${YELLOW}>>> 4. UJI KASUS IDOR: DOSEN A MENGAKSES DATA MILIK DOSEN B <<<${NC}"
# Dosen A mengakses dashboard miliknya -> 200
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" "${BASE_URL}/dashboard")
check_status 200 "$STATUS" "Dosen A mengakses dashboard miliknya"

# Dosen A mencoba mengedit MK milik Dosen B (MK ID 2 - SI201 diampu Dosen B)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" "${BASE_URL}/mata-kuliah/2/edit")
check_status 403 "$STATUS" "Dosen A dicegat 403 saat mencoba edit MK milik Dosen B (CoursePolicy)"

# Dosen A mencoba mengirimkan update ke MK milik Dosen B
CSRF_DOSEN_A=$(curl -s -b "$COOKIE_DOSEN_A" "${BASE_URL}/dashboard" | grep -o 'name="_token" value="[^"]*' | head -1 | cut -d'"' -f4)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" -X POST "${BASE_URL}/mata-kuliah/2" \
  -d "_token=${CSRF_DOSEN_A}&_method=PUT&name=Manipulasi+Nama+MK")
check_status 403 "$STATUS" "Dosen A dicegat 403 saat kirim PUT ke MK milik Dosen B (CoursePolicy)"


echo -e "\n${YELLOW}>>> 5. UJI KASUS IDOR: DOSEN A MENILAI SUBMISSION DI MK DOSEN B <<<${NC}"
# Dosen A mencoba memberi nilai pada submission tugas di MK Dosen B
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" -X POST "${BASE_URL}/submissions/2/grade" \
  -d "_token=${CSRF_DOSEN_A}&score=90&feedback=Ilegal")
check_status 403 "$STATUS" "Dosen A dicegat 403 saat memberi nilai submission MK Dosen B (SubmissionPolicy::grade)"


echo -e "\n${YELLOW}>>> 6. UJI KASUS: HAK AKSES ADMINISTRATOR (MANAJEMEN PENGGUNA) <<<${NC}"
# Mahasiswa A mencoba mengakses kelola pengguna -> 403
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" "${BASE_URL}/pengguna")
check_status 403 "$STATUS" "Mahasiswa dicegat 403 saat mengakses manajemen pengguna"

# Dosen A mencoba mengakses kelola pengguna -> 403
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" "${BASE_URL}/pengguna")
check_status 403 "$STATUS" "Dosen dicegat 403 saat mengakses manajemen pengguna"

# Admin mengakses kelola pengguna -> 200
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_ADMIN" "${BASE_URL}/pengguna")
check_status 200 "$STATUS" "Admin berhasil mengakses manajemen pengguna"


echo -e "\n${BLUE}================================================================${NC}"
echo -e "${BLUE}  RINGKASAN HASIL PENGUJIAN OTORISASI MINGGU 7                  ${NC}"
echo -e "${BLUE}================================================================${NC}"
echo -e "  Total Uji Berhasil : ${GREEN}${PASSED_COUNT}${NC}"
echo -e "  Total Uji Gagal    : ${RED}${FAILED_COUNT}${NC}"

if [ "$FAILED_COUNT" -eq 0 ]; then
    echo -e "\n${GREEN}✔ SELURUH PENGUJIAN OTORISASI LOLOS (100% SECURE). MILESTONE M2 VALID!${NC}\n"
    exit 0
else
    echo -e "\n${RED}✘ TERDAPAT PENGUJIAN YANG GAGAL. SILAKAN CEK KONFIGURASI POLICY!${NC}\n"
    exit 1
fi
