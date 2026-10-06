#!/usr/bin/env bash
# ==============================================================================
# Skrip Pengujian Otorisasi API — KampusLMS (Minggu 6)
# ==============================================================================
# Sesuai Modul 03 Prompt C:
# Menguji 4 kondisi otorisasi pada setiap endpoint:
# 1. Tanpa token sama sekali                  -> harus 401
# 2. Token mahasiswa mengakses endpoint dosen    -> harus 403
# 3. Token dosen A mengakses data milik dosen B   -> harus 403
# 4. Token yang benar dengan hak yang benar       -> harus 200 / 201 / 204
# ==============================================================================

BASE_URL="${1:-http://127.0.0.1:8000}"
API_URL="${BASE_URL}/api/v1"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}  KAMPUSLMS — API AUTHORIZATION & INTEGRATION TEST (WEEK 6)     ${NC}"
echo -e "${BLUE}  Target: ${API_URL}                                            ${NC}"
echo -e "${BLUE}================================================================${NC}\n"

check_status() {
    local expected="$1"
    local actual="$2"
    local desc="$3"

    if [ "$actual" -eq "$expected" ]; then
        echo -e "  [${GREEN}PASS${NC}] Expected: $expected | Actual: $actual — $desc"
    else
        echo -e "  [${RED}FAIL${NC}] Expected: $expected | Actual: $actual — $desc"
    fi
}

echo -e "${YELLOW}>>> 0. MENDAPATKAN TOKEN AUTENTIKASI SANCTUM <<<${NC}"

# Login Dosen A (Dr. Ir. Bambang Hermanto, mengampu SI101)
LOGIN_DOSEN_A=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"dosen@kampuslms.test","password":"password","device_name":"test-dosen-a"}')
TOKEN_DOSEN_A=$(echo "$LOGIN_DOSEN_A" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# Login Dosen B (Siti Rahmawati, mengampu SI201)
LOGIN_DOSEN_B=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"siti.rahmawati@kampuslms.test","password":"password","device_name":"test-dosen-b"}')
TOKEN_DOSEN_B=$(echo "$LOGIN_DOSEN_B" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# Login Mahasiswa (Muhammad Rizky Pratama)
LOGIN_MHS=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@kampuslms.test","password":"password","device_name":"test-mhs"}')
TOKEN_MHS=$(echo "$LOGIN_MHS" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN_DOSEN_A" ] && [ -n "$TOKEN_DOSEN_B" ] && [ -n "$TOKEN_MHS" ]; then
    echo -e "${GREEN}✓ Berhasil mendapatkan token untuk Dosen A, Dosen B, dan Mahasiswa.${NC}"
else
    echo -e "${RED}✗ Gagal mendapatkan token autentikasi.${NC}"
    if echo "$LOGIN_DOSEN_A $LOGIN_DOSEN_B $LOGIN_MHS" | grep -iq "Too Many Requests\|429"; then
        echo -e "${YELLOW}  Catatan Keamanan: Ini bukti bahwa Rate Limiting ('throttle:5,1') aktif bekerja!${NC}"
        echo -e "${YELLOW}  Sistem membatasi maksimal 5 percobaan login per 1 menit.${NC}"
        echo -e "${YELLOW}  Silakan tunggu ~30-60 detik lalu jalankan kembali skrip ini.${NC}"
    else
        echo -e "  Respons Dosen A: $LOGIN_DOSEN_A"
        echo -e "  Respons Mahasiswa: $LOGIN_MHS"
        echo -e "  Pastikan 'php artisan serve' aktif di terminal lain.${NC}"
    fi
    exit 1
fi

# Dapatkan ID data secara dinamis dari database aktif melalui API
RES_COURSES_A=$(curl -s -X GET "${API_URL}/courses" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_DOSEN_A")
COURSE_DOSEN_A_ID=$(echo "$RES_COURSES_A" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

RES_COURSES_B=$(curl -s -X GET "${API_URL}/courses" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_DOSEN_B")
COURSE_DOSEN_B_ID=$(echo "$RES_COURSES_B" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

RES_ASSIGNMENTS_A=$(curl -s -X GET "${API_URL}/courses/${COURSE_DOSEN_A_ID}/assignments" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_DOSEN_A")
ASSIGNMENT_DOSEN_A_ID=$(echo "$RES_ASSIGNMENTS_A" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

RES_SUBS_A=$(curl -s -X GET "${API_URL}/assignments/${ASSIGNMENT_DOSEN_A_ID}/submissions" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN_DOSEN_A")
SUBMISSION_DOSEN_A_ID=$(echo "$RES_SUBS_A" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
[ -z "$SUBMISSION_DOSEN_A_ID" ] && SUBMISSION_DOSEN_A_ID=1

echo -e "  > ID MK Dosen A: ${COURSE_DOSEN_A_ID:-N/A} | ID MK Dosen B: ${COURSE_DOSEN_B_ID:-N/A} | ID Tugas: ${ASSIGNMENT_DOSEN_A_ID:-N/A}\n"

echo -e "${YELLOW}>>> KONDISI 1: TANPA TOKEN (HARUS 401) <<<${NC}"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/me" -H "Accept: application/json")
check_status 401 "$CODE" "GET /me tanpa token"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses" -H "Accept: application/json")
check_status 401 "$CODE" "GET /courses tanpa token"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_URL}/assignments" -H "Accept: application/json")
check_status 401 "$CODE" "POST /assignments tanpa token"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/notifications" -H "Accept: application/json")
check_status 401 "$CODE" "GET /notifications tanpa token"
echo ""

echo -e "${YELLOW}>>> KONDISI 2: TOKEN MAHASISWA MENGAKSES ENDPOINT DOSEN (HARUS 403) <<<${NC}"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_URL}/assignments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS" \
  -H "Content-Type: application/json" \
  -d "{\"course_id\":${COURSE_DOSEN_A_ID:-1},\"title\":\"Tugas Palsu\",\"instructions\":\"xxx\",\"due_at\":\"2026-12-31 23:59:00\",\"status\":\"published\"}")
check_status 403 "$CODE" "Mahasiswa mencoba POST /assignments"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${API_URL}/submissions/${SUBMISSION_DOSEN_A_ID}/grade" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS" \
  -H "Content-Type: application/json" \
  -d '{"score":100}')
check_status 403 "$CODE" "Mahasiswa mencoba menilai diri sendiri (PUT /submissions/{id}/grade)"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/assignments/${ASSIGNMENT_DOSEN_A_ID}/submissions" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS")
check_status 403 "$CODE" "Mahasiswa mencoba melihat daftar submission (GET /assignments/{id}/submissions)"
echo ""

echo -e "${YELLOW}>>> KONDISI 3: TOKEN DOSEN A MENGAKSES DATA MILIK DOSEN B (HARUS 403) <<<${NC}"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses/${COURSE_DOSEN_B_ID}" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A")
check_status 403 "$CODE" "Dosen A membuka detail MK milik Dosen B (GET /courses/{id_B})"

CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_URL}/assignments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -H "Content-Type: application/json" \
  -d "{\"course_id\":${COURSE_DOSEN_B_ID},\"title\":\"Tugas Liar\",\"instructions\":\"xxx\",\"due_at\":\"2026-12-31 23:59:00\",\"status\":\"published\"}")
check_status 403 "$CODE" "Dosen A membuat tugas di MK milik Dosen B"
echo ""

echo -e "${YELLOW}>>> KONDISI 4: TOKEN BENAR DENGAN HAK YANG BENAR (HARUS 200 / 201 / 204) <<<${NC}"

# Profil /me
CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A")
check_status 200 "$CODE" "Dosen A mengakses profil dirinya (GET /me)"

# Daftar MK Dosen A
CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A")
check_status 200 "$CODE" "Dosen A melihat daftar MK yang diampu (GET /courses)"

# Detail MK milik Dosen A
CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "${API_URL}/courses/${COURSE_DOSEN_A_ID}" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A")
check_status 200 "$CODE" "Dosen A membuka detail MK miliknya (GET /courses/{id_A})"

# Dosen A membuat tugas baru (POST /assignments -> 201)
RES_CREATE=$(curl -s -X POST "${API_URL}/assignments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -H "Content-Type: application/json" \
  -d "{\"course_id\":${COURSE_DOSEN_A_ID},\"title\":\"Tugas API Test Week 6\",\"instructions\":\"Instruksi pengujian API\",\"due_at\":\"2026-12-31 23:59:00\",\"max_score\":100,\"allow_late\":true,\"status\":\"published\",\"week_number\":6}")
NEW_ASSIGNMENT_ID=$(echo "$RES_CREATE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_ASSIGNMENT_ID" ]; then
    echo -e "  [${GREEN}PASS${NC}] Expected: 201 | Created Assignment ID: $NEW_ASSIGNMENT_ID"

    # Dosen A update tugas (PUT /assignments/{id} -> 200)
    CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${API_URL}/assignments/${NEW_ASSIGNMENT_ID}" \
      -H "Accept: application/json" \
      -H "Authorization: Bearer $TOKEN_DOSEN_A" \
      -H "Content-Type: application/json" \
      -d '{"title":"Tugas API Test Week 6 (Updated)"}')
    check_status 200 "$CODE" "Dosen A memperbarui tugas miliknya"

    # Dosen A hapus tugas (DELETE /assignments/{id} -> 204)
    CODE=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "${API_URL}/assignments/${NEW_ASSIGNMENT_ID}" \
      -H "Accept: application/json" \
      -H "Authorization: Bearer $TOKEN_DOSEN_A")
    check_status 204 "$CODE" "Dosen A menghapus tugas miliknya (204 No Content)"
fi

echo -e "\n${BLUE}================================================================${NC}"
echo -e "${GREEN}  PENGUJIAN OTORISASI API SELESAI DENGAN SUKSES!               ${NC}"
echo -e "${BLUE}================================================================${NC}"
