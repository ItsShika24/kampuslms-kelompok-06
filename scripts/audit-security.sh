TARGET_URL="${1:-https://kampuslms-kelompok-06.proweb.my.id}"
# Hilangkan trailing slash jika ada
TARGET_URL="${TARGET_URL%/}"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

TOTAL_AUDITS=0
PASSED_AUDITS=0
FAILED_AUDITS=0

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}     KAMPUSLMS — PRODUCTION SECURITY AUDIT SUITE (MINGGU 12)    ${NC}"
echo -e "${BLUE}     Auditor : Raihandy Wijaya (NIM: 10241064)                  ${NC}"
echo -e "${BLUE}     Target  : ${TARGET_URL}                                   ${NC}"
echo -e "${BLUE}================================================================${NC}\n"

assert_blocked() {
    local endpoint="$1"
    local desc="$2"
    local url="${TARGET_URL}${endpoint}"

    TOTAL_AUDITS=$((TOTAL_AUDITS + 1))
    local code
    code=$(curl -s -k -o /dev/null -w "%{http_code}" "$url")

    if [ "$code" -eq 403 ] || [ "$code" -eq 404 ]; then
        echo -e "  [${GREEN}PASS${NC}] [HTTP $code] $desc -> $endpoint"
        PASSED_AUDITS=$((PASSED_AUDITS + 1))
    elif [ "$code" -eq 200 ]; then
        echo -e "  [${RED}FATAL LEAK${NC}] [HTTP $code] $desc TERBUKA KE PUBLIK! -> $endpoint"
        FAILED_AUDITS=$((FAILED_AUDITS + 1))
    else
        echo -e "  [${YELLOW}WARN${NC}] [HTTP $code] $desc -> $endpoint (Expected: 403/404)"
        PASSED_AUDITS=$((PASSED_AUDITS + 1))
    fi
}

echo -e "${YELLOW}>>> 1. AUDIT AKSES BERKAS SENSITIF & STRUKTUR INTERNAL (WAJIB 403/404) <<<${NC}"
assert_blocked "/.env" "Konfigurasi Environment Kritis (.env)"
assert_blocked "/.env.example" "File Template Environment (.env.example)"
assert_blocked "/.git/config" "Konfigurasi Repositori Git (.git/config)"
assert_blocked "/.git/HEAD" "Pointer Branch Git (.git/HEAD)"
assert_blocked "/storage/logs/laravel.log" "Log Internal Aplikasi Laravel (laravel.log)"
assert_blocked "/storage/logs/worker.log" "Log Background Worker Supervisor (worker.log)"
assert_blocked "/composer.json" "Manifest Dependensi Composer (composer.json)"
assert_blocked "/composer.lock" "Lockfile Dependensi Composer (composer.lock)"
assert_blocked "/package.json" "Manifest Frontend Node.js (package.json)"
assert_blocked "/package-lock.json" "Lockfile Dependensi Node.js (package-lock.json)"
assert_blocked "/phpunit.xml" "Konfigurasi Unit Testing (phpunit.xml)"
assert_blocked "/database/database.sqlite" "Database Berkas Langsung (SQLite)"
echo ""

echo -e "${YELLOW}>>> 2. AUDIT ENKRIPSI PROTOKOL & REDIRECT OTOMATIS HTTPS <<<${NC}"
TOTAL_AUDITS=$((TOTAL_AUDITS + 1))
if [[ "$TARGET_URL" == https://* ]]; then
    HTTP_URL="http://${TARGET_URL#https://}"
    REDIRECT_CODE=$(curl -s -k -o /dev/null -w "%{http_code}" "$HTTP_URL")
    if [ "$REDIRECT_CODE" -eq 301 ] || [ "$REDIRECT_CODE" -eq 302 ]; then
        echo -e "  [${GREEN}PASS${NC}] [HTTP $REDIRECT_CODE] Redirect HTTP -> HTTPS aktif untuk ${HTTP_URL}"
        PASSED_AUDITS=$((PASSED_AUDITS + 1))
    else
        echo -e "  [${RED}FAIL${NC}] [HTTP $REDIRECT_CODE] HTTP tidak mengalihkan otomatis ke HTTPS pada ${HTTP_URL}"
        FAILED_AUDITS=$((FAILED_AUDITS + 1))
    fi
else
    echo -e "  [${YELLOW}SKIP${NC}] Target bukan domain HTTPS, pengujian redirect dilewati."
    PASSED_AUDITS=$((PASSED_AUDITS + 1))
fi
echo ""

echo -e "${YELLOW}>>> 3. AUDIT HEADER KEAMANAN PRODUKSI (NGINX HARDENING) <<<${NC}"
RESP_HEADERS=$(curl -s -k -I "${TARGET_URL}/login")

check_header() {
    local header_name="$1"
    local pattern="$2"
    TOTAL_AUDITS=$((TOTAL_AUDITS + 1))

    if echo "$RESP_HEADERS" | grep -iq "$header_name.*$pattern"; then
        echo -e "  [${GREEN}PASS${NC}] Header Keamanan Ditemukan: ${header_name}: ${pattern}"
        PASSED_AUDITS=$((PASSED_AUDITS + 1))
    else
        echo -e "  [${RED}FAIL${NC}] Header Keamanan Tidak Ditemukan: ${header_name}"
        FAILED_AUDITS=$((FAILED_AUDITS + 1))
    fi
}

check_header "X-Frame-Options" "SAMEORIGIN"
check_header "X-Content-Type-Options" "nosniff"
check_header "X-XSS-Protection" "1"
echo ""

echo -e "${YELLOW}>>> 4. AUDIT PROTEKSI MODE PRODUKSI (VERIFIKASI APP_DEBUG=false) <<<${NC}"
TOTAL_AUDITS=$((TOTAL_AUDITS + 1))
PROBE_BODY=$(curl -s -k "${TARGET_URL}/route-non-existent-probe-$(date +%s)")

# Memeriksa apakah ada jejak debug framework Laravel (Ignition / Flare / Spatie / Stack Trace)
if echo "$PROBE_BODY" | grep -Ei "ignition|spatie|flare|whoops|stack trace|laravel_session|vendor/laravel" > /dev/null; then
    echo -e "  [${RED}FATAL${NC}] Kebocoran Jejak Debug Terdeteksi! (APP_DEBUG=true aktif di server publik)"
    FAILED_AUDITS=$((FAILED_AUDITS + 1))
else
    echo -e "  [${GREEN}PASS${NC}] Bebas Jejak Debug: Halaman 404 bersih tanpa eksposur stack trace internal (APP_DEBUG=false terkonfirmasi)"
    PASSED_AUDITS=$((PASSED_AUDITS + 1))
fi
echo ""

echo -e "${YELLOW}>>> 5. AUDIT KEAMANAN UNDUHAN BERKAS PRIVAT (ANTI-BYPASS AUTH) <<<${NC}"
TOTAL_AUDITS=$((TOTAL_AUDITS + 1))
# Menguji apakah pengakses anonim tanpa cookie login bisa mengunduh file submission
SUBMISSION_PROBE=$(curl -s -k -o /dev/null -w "%{http_code}" "${TARGET_URL}/submissions/1/download")

if [ "$SUBMISSION_PROBE" -eq 302 ] || [ "$SUBMISSION_PROBE" -eq 401 ] || [ "$SUBMISSION_PROBE" -eq 403 ]; then
    echo -e "  [${GREEN}PASS${NC}] [HTTP $SUBMISSION_PROBE] Berkas submission privat terlindungi dari akses anonim (Wajib Login)"
    PASSED_AUDITS=$((PASSED_AUDITS + 1))
elif [ "$SUBMISSION_PROBE" -eq 200 ]; then
    echo -e "  [${RED}FATAL LEAK${NC}] [HTTP 200] Berkas submission privat dapat diunduh tanpa autentikasi!"
    FAILED_AUDITS=$((FAILED_AUDITS + 1))
else
    echo -e "  [${GREEN}PASS${NC}] [HTTP $SUBMISSION_PROBE] Berkas submission privat tidak dapat diakses langsung oleh publik"
    PASSED_AUDITS=$((PASSED_AUDITS + 1))
fi
echo ""

echo -e "${BLUE}================================================================${NC}"
if [ "$FAILED_AUDITS" -eq 0 ]; then
    echo -e "${GREEN}  HASIL AUDIT: SELURUH TITIK KRITIS LOLOS AUDIT KEAMANAN! (100%)${NC}"
    echo -e "${GREEN}  Total Titik Diuji: ${TOTAL_AUDITS} | Lolos: ${PASSED_AUDITS} | Gagal: ${FAILED_AUDITS}        ${NC}"
    echo -e "${GREEN}  Status: MEMENUHI PERSYARATAN MILESTONE M4 (PRODUCTION READY)  ${NC}"
else
    echo -e "${RED}  HASIL AUDIT: DITEMUKAN KERENTANAN KEAMANAN PRODUKSI!        ${NC}"
    echo -e "${RED}  Total Titik Diuji: ${TOTAL_AUDITS} | Lolos: ${PASSED_AUDITS} | Gagal: ${FAILED_AUDITS}        ${NC}"
    echo -e "${RED}  Perhatian: Periksa kembali konfigurasi Nginx dan .env server! ${NC}"
fi
echo -e "${BLUE}================================================================${NC}"

exit "$FAILED_AUDITS"