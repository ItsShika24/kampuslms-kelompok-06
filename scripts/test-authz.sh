#!/usr/bin/env bash
# ==============================================================================
# Skrip Pengujian Otorisasi Menyeluruh (Web & API) — KampusLMS (Minggu 7)
# ==============================================================================
# Sesuai Modul 03 Prompt D & Milestone M2 (Tugas 2):
# Menguji otorisasi multi-role (Admin, Dosen, Mahasiswa) dan mitigasi IDOR:
# 1. Tamu (Unauthenticated) -> Web redirect 302, API 401
# 2. Otorisasi Mahasiswa (IDOR Prevention submission, no course create, no grade) -> 403
# 3. Otorisasi Dosen (Cross-Course boundary, no admin access, no delete) -> 403
# 4. Otorisasi Administrator (Full access to users & courses) -> 200
# 5. Keamanan Session & Autentikasi (Session Fixation & Generic Error Message)
# ==============================================================================

BASE_URL="${1:-http://127.0.0.1:8000}"
API_URL="${BASE_URL}/api/v1"

COOKIE_DIR="storage/framework/testing_cookies"
mkdir -p "$COOKIE_DIR"
COOKIE_ADMIN="${COOKIE_DIR}/admin.txt"
COOKIE_DOSEN_A="${COOKIE_DIR}/dosen_a.txt"
COOKIE_DOSEN_B="${COOKIE_DIR}/dosen_b.txt"
COOKIE_MHS="${COOKIE_DIR}/mhs.txt"
COOKIE_TEMP="${COOKIE_DIR}/temp.txt"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}   KAMPUSLMS — AUTHORIZATION & SECURITY TEST SUITE (MINGGU 7)   ${NC}"
echo -e "${BLUE}   Target Web: ${BASE_URL}                                      ${NC}"
echo -e "${BLUE}   Target API: ${API_URL}                                   ${NC}"
echo -e "${BLUE}================================================================${NC}\n"

check_status() {
    local expected="$1"
    local actual="$2"
    local desc="$3"

    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if [ "$actual" -eq "$expected" ]; then
        echo -e "  [${GREEN}PASS${NC}] Expected: $expected | Actual: $actual — $desc"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "  [${RED}FAIL${NC}] Expected: $expected | Actual: $actual — $desc"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

login_web() {
    local email="$1"
    local password="$2"
    local cookie_jar="$3"

    rm -f "$cookie_jar"
    local login_page
    login_page=$(curl -s -c "$cookie_jar" "${BASE_URL}/login")
    local csrf_token
    csrf_token=$(echo "$login_page" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)

    if [ -z "$csrf_token" ]; then
        return 1
    fi

    local code
    code=$(curl -s -b "$cookie_jar" -c "$cookie_jar" -X POST "${BASE_URL}/login" \
        -d "_token=${csrf_token}" \
        -d "email=${email}" \
        -d "password=${password}" \
        -o /dev/null -w "%{http_code}")

    if [ "$code" -eq 302 ]; then
        return 0
    else
        return 1
    fi
}

echo -e "${YELLOW}>>> 0. INISIALISASI SESSION & DISCOVERY DATA DINAMIS <<<${NC}"

# Ambil ID data dinamis dari database aktif melalui artisan tinker
DB_IDS=$(php artisan tinker --execute="echo json_encode([
    'course_a'   => \App\Models\Course::where('lecturer_id', \App\Models\User::where('email', 'dosen@kampuslms.test')->first()?->id)->first()?->id ?? 1,
    'course_b'   => \App\Models\Course::where('lecturer_id', \App\Models\User::where('email', 'siti.rahmawati@kampuslms.test')->first()?->id)->first()?->id ?? 2,
    'sub_mhs'    => \App\Models\Submission::where('user_id', \App\Models\User::where('email', 'mahasiswa@kampuslms.test')->first()?->id)->first()?->id ?? 1,
    'sub_other'  => \App\Models\Submission::where('user_id', '!=', \App\Models\User::where('email', 'mahasiswa@kampuslms.test')->first()?->id)->first()?->id ?? 92,
    'asg_draft'  => \App\Models\Assignment::where('status', 'draft')->first()?->id ?? 3
]);" 2>/dev/null)

COURSE_A_ID=$(echo "$DB_IDS" | grep -o '"course_a":[0-9]*' | cut -d':' -f2)
COURSE_B_ID=$(echo "$DB_IDS" | grep -o '"course_b":[0-9]*' | cut -d':' -f2)
SUB_MHS_ID=$(echo "$DB_IDS" | grep -o '"sub_mhs":[0-9]*' | cut -d':' -f2)
SUB_OTHER_ID=$(echo "$DB_IDS" | grep -o '"sub_other":[0-9]*' | cut -d':' -f2)
ASG_DRAFT_ID=$(echo "$DB_IDS" | grep -o '"asg_draft":[0-9]*' | cut -d':' -f2)

COURSE_A_ID=${COURSE_A_ID:-1}
COURSE_B_ID=${COURSE_B_ID:-2}
SUB_MHS_ID=${SUB_MHS_ID:-1}
SUB_OTHER_ID=${SUB_OTHER_ID:-92}
ASG_DRAFT_ID=${ASG_DRAFT_ID:-3}

echo -e "  > Target Course Dosen A : ID ${COURSE_A_ID}"
echo -e "  > Target Course Dosen B : ID ${COURSE_B_ID}"
echo -e "  > Submission Mahasiswa  : ID ${SUB_MHS_ID}"
echo -e "  > Submission Mahasiswa Lain (IDOR target): ID ${SUB_OTHER_ID}"
echo -e "  > Tugas Draft           : ID ${ASG_DRAFT_ID}"

# Login Web Session
echo -e "  > Melakukan autentikasi Web Session (Login)..."
login_web "admin@kampuslms.test" "password" "$COOKIE_ADMIN" && echo -e "    ${GREEN}✓ Admin login web session berhasil.${NC}" || echo -e "    ${RED}✗ Admin login gagal.${NC}"
login_web "dosen@kampuslms.test" "password" "$COOKIE_DOSEN_A" && echo -e "    ${GREEN}✓ Dosen A login web session berhasil.${NC}" || echo -e "    ${RED}✗ Dosen A login gagal.${NC}"
login_web "siti.rahmawati@kampuslms.test" "password" "$COOKIE_DOSEN_B" && echo -e "    ${GREEN}✓ Dosen B login web session berhasil.${NC}" || echo -e "    ${RED}✗ Dosen B login gagal.${NC}"
login_web "mahasiswa@kampuslms.test" "password" "$COOKIE_MHS" && echo -e "    ${GREEN}✓ Mahasiswa login web session berhasil.${NC}" || echo -e "    ${RED}✗ Mahasiswa login gagal.${NC}"

# Ambil Token API Sanctum untuk integrasi API
LOGIN_MHS_API=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@kampuslms.test","password":"password","device_name":"test-suite"}')
TOKEN_MHS=$(echo "$LOGIN_MHS_API" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

LOGIN_DOSEN_A_API=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"dosen@kampuslms.test","password":"password","device_name":"test-suite"}')
TOKEN_DOSEN_A=$(echo "$LOGIN_DOSEN_A_API" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

echo ""


# ==============================================================================
# KONDISI 1: PENGGUNA BELUM LOGIN / TAMU (GUEST)
# ==============================================================================
echo -e "${YELLOW}>>> KONDISI 1: PENGGUNA TAMU / UNAUTHENTICATED (HARUS REDIRECT 302 / 401) <<<${NC}"

CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/dashboard")
check_status 302 "$CODE" "Tamu mengakses GET /dashboard (Redirect ke /login)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah")
check_status 302 "$CODE" "Tamu mengakses GET /mata-kuliah (Redirect ke /login)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/users")
check_status 302 "$CODE" "Tamu mengakses GET /admin/users (Redirect ke /login)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/submissions")
check_status 302 "$CODE" "Tamu mengakses GET /submissions (Redirect ke /login)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/me" -H "Accept: application/json")
check_status 401 "$CODE" "Tamu memanggil API GET /api/v1/me (Harus 401 Unauthorized)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses" -H "Accept: application/json")
check_status 401 "$CODE" "Tamu memanggil API GET /api/v1/courses (Harus 401 Unauthorized)"
echo ""


# ==============================================================================
# KONDISI 2: OTORISASI MAHASISWA & MITIGASI IDOR
# ==============================================================================
echo -e "${YELLOW}>>> KONDISI 2: OTORISASI MAHASISWA & MITIGASI IDOR (HARUS 403 FORBIDDEN) <<<${NC}"

# IDOR CHECK: Mahasiswa membuka submission mahasiswa lain
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -o /dev/null -w "%{http_code}" "${BASE_URL}/submissions/${SUB_OTHER_ID}")
check_status 403 "$CODE" "IDOR TEST: Mahasiswa mencoba membuka submission mahasiswa lain (GET /submissions/${SUB_OTHER_ID})"

# Legitimate Check: Mahasiswa membuka submission miliknya sendiri
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -o /dev/null -w "%{http_code}" "${BASE_URL}/submissions/${SUB_MHS_ID}")
check_status 200 "$CODE" "Mahasiswa membuka submission miliknya sendiri (GET /submissions/${SUB_MHS_ID})"

# Mahasiswa mencoba membuka form tambah mata kuliah
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah/create")
check_status 403 "$CODE" "Mahasiswa mencoba membuka halaman tambah MK (GET /mata-kuliah/create)"

# Mahasiswa mencoba POST tambah mata kuliah
CSRF_MHS=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" "${BASE_URL}/dashboard" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -X POST "${BASE_URL}/mata-kuliah" \
  -d "_token=${CSRF_MHS}" \
  -d "code=HACK101" -d "name=Mata Kuliah Palsu" -d "sks=3" -d "status=active" \
  -o /dev/null -w "%{http_code}")
check_status 403 "$CODE" "Mahasiswa mencoba POST membuat mata kuliah (POST /mata-kuliah)"

# Mahasiswa mencoba akses User Management (Admin area)
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/users")
check_status 403 "$CODE" "Mahasiswa mencoba mengakses modul pengguna (GET /admin/users)"

# Mahasiswa mencoba menilai submission
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -X POST "${BASE_URL}/submissions/${SUB_MHS_ID}/grade" \
  -d "_token=${CSRF_MHS}" -d "score=100" -d "feedback=Curang" \
  -o /dev/null -w "%{http_code}")
check_status 403 "$CODE" "Mahasiswa mencoba memberi nilai pada submission (POST /submissions/{id}/grade)"

# Mahasiswa mencoba melihat tugas yang masih draft
CODE=$(curl -s -b "$COOKIE_MHS" -c "$COOKIE_MHS" -o /dev/null -w "%{http_code}" "${BASE_URL}/tugas/${ASG_DRAFT_ID}")
check_status 403 "$CODE" "Mahasiswa mencoba melihat tugas draft (GET /tugas/${ASG_DRAFT_ID})"

# API: Mahasiswa mencoba menilai tugas via API
if [ -n "$TOKEN_MHS" ]; then
    CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${API_URL}/submissions/${SUB_MHS_ID}/grade" \
      -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_MHS" \
      -H "Content-Type: application/json" -d '{"score":100}')
    check_status 403 "$CODE" "API: Mahasiswa mencoba menilai tugas via API PUT /submissions/{id}/grade"
fi
echo ""


# ==============================================================================
# KONDISI 3: OTORISASI DOSEN (CROSS-COURSE BOUNDARY & SCOPE)
# ==============================================================================
echo -e "${YELLOW}>>> KONDISI 3: OTORISASI DOSEN (CROSS-COURSE PROTECTION) (HARUS 403 FORBIDDEN) <<<${NC}"

# Legitimate Check: Dosen A membuka detail MK miliknya
CODE=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah/${COURSE_A_ID}")
check_status 200 "$CODE" "Dosen A membuka detail mata kuliah miliknya (GET /mata-kuliah/${COURSE_A_ID})"

# IDOR Check: Dosen A mencoba mengedit mata kuliah milik Dosen B
CODE=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah/${COURSE_B_ID}/edit")
check_status 403 "$CODE" "IDOR TEST: Dosen A mencoba form edit MK milik Dosen B (GET /mata-kuliah/${COURSE_B_ID}/edit)"

# IDOR Check: Dosen A mencoba PUT update ke mata kuliah milik Dosen B
CSRF_DOSEN_A=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" "${BASE_URL}/dashboard" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)
CODE=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -X PUT "${BASE_URL}/mata-kuliah/${COURSE_B_ID}" \
  -d "_token=${CSRF_DOSEN_A}" \
  -d "code=SI201" -d "name=Pengambilalihan Ilegal" -d "sks=3" -d "status=active" \
  -o /dev/null -w "%{http_code}")
check_status 403 "$CODE" "IDOR TEST: Dosen A mencoba PUT update MK milik Dosen B (PUT /mata-kuliah/${COURSE_B_ID})"

# Dosen A mencoba mengakses User Management (Admin area)
CODE=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/users")
check_status 403 "$CODE" "Dosen A mencoba mengakses modul pengguna admin (GET /admin/users)"

# Dosen A mencoba DELETE mata kuliah (Hanya Admin yang berhak)
CODE=$(curl -s -b "$COOKIE_DOSEN_A" -c "$COOKIE_DOSEN_A" -X DELETE "${BASE_URL}/mata-kuliah/${COURSE_A_ID}" \
  -d "_token=${CSRF_DOSEN_A}" \
  -o /dev/null -w "%{http_code}")
check_status 403 "$CODE" "Dosen A mencoba DELETE mata kuliah (Hanya Admin yang berhak hapus)"

# API: Dosen A mencoba membuka detail MK Dosen B via API
if [ -n "$TOKEN_DOSEN_A" ]; then
    CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses/${COURSE_B_ID}" \
      -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_DOSEN_A")
    check_status 403 "$CODE" "API: Dosen A membuka detail MK milik Dosen B via API (GET /courses/{id_B})"
fi
echo ""


# ==============================================================================
# KONDISI 4: OTORISASI ADMINISTRATOR (SUPERUSER ACCESS)
# ==============================================================================
echo -e "${YELLOW}>>> KONDISI 4: OTORISASI ADMINISTRATOR (HARUS 200 OK) <<<${NC}"

# Admin membuka daftar pengguna
CODE=$(curl -s -b "$COOKIE_ADMIN" -c "$COOKIE_ADMIN" -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/users")
check_status 200 "$CODE" "Admin mengakses manajemen pengguna (GET /admin/users)"

# Admin membuka detail mata kuliah apa pun (termasuk milik Dosen A dan Dosen B)
CODE=$(curl -s -b "$COOKIE_ADMIN" -c "$COOKIE_ADMIN" -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah/${COURSE_A_ID}")
check_status 200 "$CODE" "Admin membuka mata kuliah Dosen A (GET /mata-kuliah/${COURSE_A_ID})"

CODE=$(curl -s -b "$COOKIE_ADMIN" -c "$COOKIE_ADMIN" -o /dev/null -w "%{http_code}" "${BASE_URL}/mata-kuliah/${COURSE_B_ID}")
check_status 200 "$CODE" "Admin membuka mata kuliah Dosen B (GET /mata-kuliah/${COURSE_B_ID})"

# Admin membuka pusat pengumpulan tugas
CODE=$(curl -s -b "$COOKIE_ADMIN" -c "$COOKIE_ADMIN" -o /dev/null -w "%{http_code}" "${BASE_URL}/submissions")
check_status 200 "$CODE" "Admin membuka daftar seluruh submission (GET /submissions)"
echo ""


# ==============================================================================
# KONDISI 5: KEAMANAN AUTENTIKASI & SESSION
# ==============================================================================
echo -e "${YELLOW}>>> KONDISI 5: KEAMANAN AUTENTIKASI & SESSION (FIXATION & ENUMERATION) <<<${NC}"

# 1. Proteksi Session Fixation: Memastikan Session ID berubah sebelum vs sesudah login
rm -f "$COOKIE_TEMP"
INITIAL_HTML=$(curl -s -c "$COOKIE_TEMP" "${BASE_URL}/login")
INITIAL_CSRF=$(echo "$INITIAL_HTML" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)
PRE_SESSION_ID=$(grep -i "session" "$COOKIE_TEMP" | awk '{print $NF}')

curl -s -b "$COOKIE_TEMP" -c "$COOKIE_TEMP" -X POST "${BASE_URL}/login" \
  -d "_token=${INITIAL_CSRF}" \
  -d "email=admin@kampuslms.test" \
  -d "password=password" \
  -o /dev/null

POST_SESSION_ID=$(grep -i "session" "$COOKIE_TEMP" | awk '{print $NF}')

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -n "$PRE_SESSION_ID" ] && [ -n "$POST_SESSION_ID" ] && [ "$PRE_SESSION_ID" != "$POST_SESSION_ID" ]; then
    echo -e "  [${GREEN}PASS${NC}] Session Fixation Protection: Session ID berubah setelah login (Regenerated)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "  [${RED}FAIL${NC}] Session Fixation Protection: Session ID TIDAK berubah setelah login"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# 2. Pesan Error Generik: Email salah vs Password salah memberikan pesan identik (Cegah User Enumeration)
rm -f "$COOKIE_TEMP"
LOGIN_PAGE=$(curl -s -c "$COOKIE_TEMP" "${BASE_URL}/login")
CSRF_ERR=$(echo "$LOGIN_PAGE" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)

# Kirim kredensial salah ke form login web
curl -s -b "$COOKIE_TEMP" -c "$COOKIE_TEMP" -X POST "${BASE_URL}/login" \
  -d "_token=${CSRF_ERR}" \
  -d "email=nonexistent_user_999@test.com" \
  -d "password=wrongpassword_xyz" \
  -o /dev/null

# Dapatkan halaman login hasil redirect dengan flash error session
LOGIN_PAGE_REDIRECT=$(curl -s -b "$COOKIE_TEMP" -c "$COOKIE_TEMP" "${BASE_URL}/login")

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if echo "$LOGIN_PAGE_REDIRECT" | grep -qi "Email atau kata sandi yang Anda masukkan tidak sesuai"; then
    echo -e "  [${GREEN}PASS${NC}] Generic Auth Error: Pesan error generik aktif, mencegah user enumeration"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "  [${RED}FAIL${NC}] Generic Auth Error: Pesan error membocorkan informasi keberadaan user"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Pembersihan file cookie pengujian
rm -rf "$COOKIE_DIR"
echo ""

# ==============================================================================
# HASIL AKHIR (SUMMARY)
# ==============================================================================
echo -e "${BLUE}================================================================${NC}"
if [ "$FAILED_TESTS" -eq 0 ]; then
    echo -e "${GREEN}  HASIL AKHIR: SELURUH PENGUJIAN OTORISASI LOLOS! (100% PASS)  ${NC}"
    echo -e "${GREEN}  Total: ${TOTAL_TESTS} | Lolos: ${PASSED_TESTS} | Gagal: ${FAILED_TESTS}                     ${NC}"
else
    echo -e "${RED}  HASIL AKHIR: ADA PENGUJIAN YANG GAGAL!                      ${NC}"
    echo -e "${RED}  Total: ${TOTAL_TESTS} | Lolos: ${PASSED_TESTS} | Gagal: ${FAILED_TESTS}                     ${NC}"
fi
echo -e "${BLUE}================================================================${NC}"

exit "$FAILED_TESTS"
